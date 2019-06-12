<?php
/**
 * Handle distributed post specific content saving
 *
 * @package Distributor
 */

namespace Distributor\PostSpecificContentHandler;

/**
 * Setup actions
 */
function setup() {
	add_action(
		'init',
		function () {
			add_action( 'dt_process_distributor_attributes', __NAMESPACE__ . '\save_post_specific_content', 10, 2 );
			add_action( 'dt_process_subscription_attributes', __NAMESPACE__ . '\save_post_specific_content', 10, 2 );
		}
	);
}

/**
 * Handle post site specific content
 *
 * @param WP_Post         $post    Inserted or updated post object.
 * @param WP_REST_Request $request Request object.
 */
function save_post_specific_content( $post, $request ) {

	$new_post = get_post( $post->ID );
	$content  = $new_post->post_content;

	// Replace with the website local images
	$content = preg_replace_callback(
		'|<img [^>]+>|',
		__NAMESPACE__ . '\localize_images',
		$content
	);

	// Handle shortcode distribution
	// We need just to get all shortcodes in post content regardless their type or hierarchy
	$pattern = "/\[(\[?)([^\]]+)(\]?)\]/";

	$content = preg_replace_callback(
		$pattern,
		function ( $matches ) {
			$shortcode = $matches[0];

			/**
			 * Filters the the shortcode tags in the post content
			 *
			 * @param string $shortcode Whole shortcode tag including wrapped content, if there is any
			 * @param array  $matches the array of matches
			 */
			$shortcode = apply_filters( 'dt_post_content_shortcode_tags', $shortcode, $matches );

			return $shortcode;
		},
		$content
	);

	wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_content' => $content,
		)
	);
}

/**
 * Reference distributed post images to local attachments
 *
 * @param array<string> $matches Matches array
 *
 * @return string
 */
function localize_images( $matches ) {
	// Convert <img ... /> string to array consisting attributes' names as keys and their values as array of values
	$exploded = preg_split( '/(?<=<img)\s+|(?<=")\s+/', $matches[0] );

	$attrs = array();
	foreach ( $exploded as $value ) {
		$t              = explode( '=', $value );
		$attrs[ $t[0] ] = isset( $t[1] ) ? explode( ' ', $t[1] ) : array();
	}

	/**
	 * Filters the <img> attributes before modifications
	 *
	 * @param array<string,array> $attrs Attributes array
	 */
	$attrs = apply_filters( 'dt_post_attachment_attributes_before_modify', $attrs );

	// Get attachment size and original id
	$orig_img_id = 0;
	$img_size    = '';
	if ( ! empty( $attrs['class'] ) ) {
		foreach ( $attrs['class'] as $c ) {
			preg_match( '/wp-image-(\d+)/', $c, $m );
			if ( ! empty( $m[1] ) ) {
				$orig_img_id = $m[1];
			}

			preg_match( '/size-(\w+)/', $c, $m2 );
			if ( ! empty( $m2 ) ) {
				$img_size = $m2[1];
			}
		}
	}

	if ( 0 === $orig_img_id ) {
		return $matches[0];
	}

	// Get the mapped image id
	$mapped_img_id = get_distributed_image_id( $orig_img_id );

	if ( null === $mapped_img_id ) {
		return $matches[0];
	}

	if ( '' === $img_size ) {
		$img_src = wp_get_attachment_url( $mapped_img_id );
	} else {
		$img_src = wp_get_attachment_image_url( $mapped_img_id, $img_size );
	}

	// Replace attachment url
	if ( ! empty( $attrs['src'] ) ) {
		$attrs['src'][0] = $img_src;
	}

	// Replace image id with the appropriate one in the class
	foreach ( $attrs['class'] as &$v ) {
		if ( strpos( $v, 'wp-image-' ) !== false ) {
			$v = 'wp-image-' . $mapped_img_id;
		}
	}
	unset( $v ); // break the reference with the last element

	/**
	 * Filters the <img> attributes after implemented modifications
	 *
	 * @param array<string,array> $attrs Attributes array
	 */
	$attrs = apply_filters( 'dt_post_attachment_attributes_after_modify', $attrs );

	// Re-assemble the <img ... /> tag
	$img_tag = '';
	foreach ( $attrs as $attr_name => $attr_values ) {
		$img_tag .= $attr_name;
		if ( ! empty( $attr_values ) ) {
			$img_tag .= '="';
			foreach ( $attr_values as $attr_value ) {
				$img_tag .= trim( $attr_value, '"' ) . ' ';
			}
			$img_tag  = rtrim( $img_tag );
			$img_tag .= '"';
		}

		$img_tag .= ' ';
	}

	return rtrim( $img_tag );
}

/**
 * Get distributed attachment id by original attachment id
 *
 * @param int $id Image remote original id.
 *
 * @return null|int
 */
function get_distributed_image_id( $id ) {
	global $wpdb;
	$result = $wpdb->get_col( $wpdb->prepare( "SELECT post_id from $wpdb->postmeta WHERE meta_key = 'dt_original_media_id' AND meta_value = %d", $id ) );

	if ( 1 === count( $result ) ) {
		return $result[0];
	}

	if ( count( $result ) > 1 ) {
		// We have a trashed post, dodge it
		return $wpdb->get_var(
			$wpdb->prepare(
				" SELECT post_id 
				                                        FROM $wpdb->postmeta 
				                                          INNER JOIN `$wpdb->posts` 
				                                            ON `$wpdb->posts`.`ID` = `$wpdb->postmeta`.`post_id` 
				                                            AND `$wpdb->posts`.`post_status` != 'trash' 
				                                        WHERE meta_key = 'dt_original_media_id' 
				                                          AND meta_value = %d 
				                                        LIMIT 1",
				$id
			)
		);
	}

	return null;
}
