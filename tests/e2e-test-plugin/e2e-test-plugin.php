<?php
/**
 * Plugin name: Distributor E2E Test Plugin
 * Description: Plugin for testing Distributor E2E tests.
 * requires: distributor
 *
 * @package Distributor/E2E Test Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DT_VERSION' ) ) {
	return;
}

use function Distributor\Utils\get_attachment_id_by_original_data;
use function Distributor\Utils\process_media;

// Register the shortcode.
add_action(
	'init',
	function() {
		add_shortcode(
			'dt_term_shortcode',
			function( $atts ) {
				// Extract attributes and set default values if necessary
				$atts = shortcode_atts(
					array(
						'id' => 0, // Default value if no ID is provided
					),
					$atts,
					'dt_term_shortcode'
				);

				// Use the ID for logic or display
				return 'Selected TERM ID: ' . esc_html( $atts['id'] );
			}
		);
	}
);

$registered_data_with_type = (bool) get_option( 'distributor_registered_data_with_type', false );
if ( true === $registered_data_with_type ) {
	// Distributor data registration for the related post data stored in post meta.
	distributor_register_data(
		'related_post_data',
		array(
			'location'           => 'post_meta',
			'attributes'         => array(
				'meta_key' => 'related_post_id',
			),
			'type'               => 'post',
		)
	);

	// Distributor data registration for the cover block.
	distributor_register_data(
		'cover_block_data',
		array(
			'location'           => 'post_content',
			'attributes'         => array(
				'block_name'      => 'core/cover',
				'block_attribute' => 'id',
			),
			'type'               => 'media',
		)
	);

	// Distributor data registration for the shortcode.
	distributor_register_data(
		'term_shortcode_data',
		array(
			'location'           => 'post_content',
			'attributes'         => array(
				'shortcode'           => 'dt_term_shortcode',
				'shortcode_attribute' => 'id',
			),
			'type'               => 'term',
		)
	);
} else {
	// Distributor data registration for the related post data stored in post meta.
	distributor_register_data(
		'related_post_data',
		array(
			'location'           => 'post_meta',
			'attributes'         => array(
				'meta_key' => 'related_post_id',
			),
			'pre_distribute_cb'  => function( $related_post_id ) {
				if ( ! $related_post_id ) {
					return array();
				}

				$post = get_post( $related_post_id );
				if ( ! $post ) {
					return array();
				}

				return array(
					'post_type'    => $post->post_type,
					'post_title'   => $post->post_title,
					'post_status'  => $post->post_status,
					'post_content' => $post->post_content,
				);
			},
			'post_distribute_cb' => function( $extra_data, $source_post_id, $post_data ) {
				if ( ! isset( $extra_data['post_type'] ) && ! isset( $extra_data['post_title'] ) ) {
					return $source_post_id;
				}

				$posts = get_posts(
					array(
						'post_type'              => $extra_data['post_type'],
						'title'                  => $extra_data['post_title'],
						'post_status'            => 'any',
						'fields'                 => 'ids',
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'update_post_term_cache' => false,
						'numberposts'            => 1,
						'orderby'                => 'post_date ID',
						'order'                  => 'ASC',
					)
				);

				if ( ! empty( $posts ) ) {
					return $posts[0];
				}

				// Create the post.
				$post_id = wp_insert_post( $extra_data );

				if ( is_wp_error( $post_id ) ) {
					return;
				}

				return $post_id;
			},
		)
	);

	// Distributor data registration for the cover block.
	distributor_register_data(
		'cover_block_data',
		array(
			'location'           => 'post_content',
			'attributes'         => array(
				'block_name'      => 'core/cover',
				'block_attribute' => [ 'id', 'url' ],
			),
			'pre_distribute_cb'  => function( $data, $post_id ) {
				return distributor_image_pre_distribute_cb( $data['id'] );
			},
			'post_distribute_cb' => function( $extra_data, $source_data, $post_data ) {
				if ( ! isset( $extra_data['image_url'] ) ) {
					return;
				}

				// Do something after sending the data.
				$image_id = distributor_image_post_distribute_cb( $extra_data, $source_data['id'], $post_data );
				return array(
					'id'                         => $image_id,
					'url'                        => wp_get_attachment_image_url( $image_id, 'full' ),
					'inner_content_replacements' => array(
						array(
							'search'  => 'wp-image-' . $source_data['id'],
							'replace' => 'wp-image-' . $image_id,
						),
						array(
							'search'  => $source_data['url'],
							'replace' => wp_get_attachment_image_url( $image_id, 'full' ),
						),
					),
				);
			},
		)
	);

	// Distributor data registration for the shortcode.
	distributor_register_data(
		'term_shortcode_data',
		array(
			'location'           => 'post_content',
			'attributes'         => array(
				'shortcode'           => 'dt_term_shortcode',
				'shortcode_attribute' => 'id',
			),
			'pre_distribute_cb'  => function( $data ) {
				$term = get_term_by( 'id', $data, 'category' );
				if ( ! $term ) {
					return array();
				}

				return array(
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description,
					'taxonomy'    => $term->taxonomy,
				);
			},
			'post_distribute_cb' => function( $extra_data, $source_data, $post_data ) {
				if ( ! isset( $extra_data['name'] ) && ! isset( $extra_data['taxonomy'] ) ) {
					return;
				}

				$existing_term = get_term_by( 'name', $extra_data['name'], $extra_data['taxonomy'] );
				if ( $existing_term ) {
					return $existing_term->term_id;
				}

				// Create the term.
				$term = wp_insert_term(
					$extra_data['name'],
					$extra_data['taxonomy'],
					array(
						'description' => $extra_data['description'],
						'slug'        => $extra_data['slug'],
					)
				);

				if ( is_wp_error( $term ) ) {
					return;
				}

				return $term['term_id'];
			},
		)
	);
}


/**
 * Pre-distribute callback for the cover block data.
 *
 * @param mixed $image_id           Background image ID.
 * @return array{image_url: string} Extra data to be added to the request.
 */
function distributor_image_pre_distribute_cb( $image_id ) {
	if ( ! $image_id ) {
		return array();
	}

	$image_url = wp_get_attachment_image_url( $image_id, 'full' );
	return array(
		'image_url' => $image_url,
	);
}

/**
 * Post-distribute callback for the cover block data.
 *
 * @param array $extra_data  Extra data related to the image.
 * @param mixed $source_data Source data.
 * @param array $post_data   Post data.
 * @return bool|int
 */
function distributor_image_post_distribute_cb( $extra_data, $source_data, $post_data ) {
	if ( ! isset( $extra_data['image_url'] ) ) {
		return;
	}

	$source_image_url = $extra_data['image_url'];
	$source_image_id  = $source_data;

	// Check if the media is already existing on the site. If it is, return the media ID.
	$image_id = get_attachment_id_by_original_data( $source_image_id, $source_image_url );

	if ( ! $image_id ) {
		$image_id = process_media( $source_image_url, 0, [] );
		update_post_meta( $image_id, 'dt_original_media_id', wp_slash( $source_image_id ) );
		update_post_meta( $image_id, 'dt_original_media_url', wp_slash( $source_image_url ) );
	}

	return $image_id;
}
