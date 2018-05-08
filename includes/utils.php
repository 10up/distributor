<?php

namespace Distributor\Utils;

/**
 * Determine if we are on VIP
 *
 * @since  1.0
 * @return boolean
 */
function is_vip_com() {
	return ( defined( 'WPCOM_IS_VIP_ENV' ) && WPCOM_IS_VIP_ENV );
}

/**
 * Determine if Gutenberg is being used
 *
 * @since  1.2
 * @return boolean
 */
function is_using_gutenberg() {
	return ( function_exists( 'the_gutenberg_project' ) );
}

/**
 * Get Distributor settings with defaults
 *
 * @since  1.0
 * @return array
 */
function get_settings() {
	$defaults = [
		'override_author_byline' => true,
		'email'                  => '',
		'license_key'            => '',
		'valid_license'          => null,
	];

	$settings = get_option( 'dt_settings', [] );
	$settings = wp_parse_args( $settings, $defaults );

	return $settings;
}

/**
 * Get Distributor network settings with defaults
 *
 * @since  1.2
 * @return array
 */
function get_network_settings() {
	$defaults = [
		'email'                  => '',
		'license_key'            => '',
		'valid_license'          => null,
	];

	$settings = get_site_option( 'dt_settings', [] );
	$settings = wp_parse_args( $settings, $defaults );

	return $settings;
}

/**
 * Hit license API to see if key/email is valid
 * @param  string $email
 * @param  string $license_key
 * @since  1.2
 * @return bool
 */
function check_license_key( $email, $license_key ) {

	$request = wp_remote_post( 'https://distributorplugin.com/wp-json/distributor-theme/v1/validate-license', [
		'timeout' => 10,
		'body'    => [
			'license_key' => $license_key,
			'email'       => $email,
		],
	] );

	if ( is_wp_error( $request ) ) {
		return false;
	}

	if ( 200 === wp_remote_retrieve_response_code( $request ) ) {
		return true;
	}

	return false;
}

/**
 * Determine if plugin is in debug mode or not
 *
 * @since  1.0
 * @return boolean
 */
function is_dt_debug() {
	return ( defined( 'DISTRIBUTOR_DEBUG' ) && DISTRIBUTOR_DEBUG );
}

/**
 * Given an array of meta, set meta to another post. Don't copy in blackisted (Distributor) meta.
 *
 * @param int   $post_id
 * @param array $meta
 */
function set_meta( $post_id, $meta ) {
	$blacklisted_meta = blacklisted_meta();

	foreach ( $meta as $meta_key => $meta_value ) {
		if ( is_string( $meta_value ) ) {
			if ( ! in_array( $meta_key, $blacklisted_meta, true ) ) {
				$meta_value = maybe_unserialize( $meta_value );
				update_post_meta( $post_id, $meta_key, $meta_value );
			}
		} else {
			$meta_array = (array) $meta_value;
			foreach ( $meta_array as $meta_item_value ) {
				if ( ! in_array( $meta_key, $blacklisted_meta, true ) ) {
					$meta_item_value = maybe_unserialize( $meta_item_value );
					update_post_meta( $post_id, $meta_key, $meta_item_value );
				}
			}
		}
	}
}

/**
 * Return post types that are allowed to be distributed
 *
 * @since  1.0
 * @return array
 */
function distributable_post_types() {
	$post_types = get_post_types( [ 'public' => true ] );

	if ( ! empty( $post_types['attachment'] ) ) {
		unset( $post_types['attachment'] );
	}

	return apply_filters( 'distributable_post_types', array_diff( $post_types, [ 'dt_ext_connection', 'dt_subscription' ] ) );
}

function blacklisted_meta() {
	return apply_filters( 'dt_blacklisted_meta', [ 'dt_unlinked', 'dt_connection_map', 'dt_subscription_update', 'dt_subscriptions', 'dt_subscription_signature', 'dt_original_post_id', 'dt_original_post_url', 'dt_original_blog_id', 'dt_syndicate_time', '_wp_attached_file', '_edit_lock', '_edit_last' ] );
}

/**
 * Prepare meta for consumption
 *
 * @param  int $post_id
 * @since  1.0
 * @return array
 */
function prepare_meta( $post_id ) {
	$meta          = get_post_meta( $post_id );
	$prepared_meta = array();

	$blacklisted_meta = blacklisted_meta();

	// Transfer all meta
	foreach ( $meta as $meta_key => $meta_array ) {
		foreach ( $meta_array as $meta_value ) {
			if ( ! in_array( $meta_key, $blacklisted_meta, true ) ) {
				$meta_value                 = maybe_unserialize( $meta_value );
				$prepared_meta[ $meta_key ] = $meta_value;
			}
		}
	}

	return $prepared_meta;
}

/**
 * Format media items for consumption
 *
 * @param  int $post_id
 * @since  1.0
 * @return array
 */
function prepare_media( $post_id ) {
	$raw_media   = get_attached_media( get_allowed_mime_types(), $post_id );
	$media_array = array();

	$featured_image_id = get_post_thumbnail_id( $post_id );
	$found_featured    = false;

	foreach ( $raw_media as $media_post ) {
		$media_item = format_media_post( $media_post );

		if ( $media_item['featured'] ) {
			$found_featured = true;
		}

		$media_array[] = $media_item;
	}

	if ( ! empty( $featured_image_id ) && ! $found_featured ) {
		$featured_image             = format_media_post( get_post( $featured_image_id ) );
		$featured_image['featured'] = true;

		$media_array[] = $featured_image;
	}

	return $media_array;
}

/**
 * Format taxonomy terms for consumption
 *
 * @param  int $post_id
 * @since  1.0
 * @return array
 */
function prepare_taxonomy_terms( $post_id ) {
	$post = get_post( $post_id );

	$taxonomy_terms = [];
	$taxonomies     = get_object_taxonomies( $post );

	/**
	 * Filters the taxonomies that should be synced.
	 *
	 * @since 1.0
	 *
	 * @param array taxonomies  Associative array list of taxonomies supported by current post
	 * @param object $post      The Post Object
	 */
	$taxonomies = apply_filters( 'dt_syncable_taxonomies', $taxonomies, $post );

	foreach ( $taxonomies as $taxonomy ) {
		$taxonomy_terms[ $taxonomy ] = wp_get_object_terms( $post_id, $taxonomy );
	}

	return $taxonomy_terms;
}

/**
 * Given an array of terms by taxonomy, set those terms to another post. This function will cleverly merge
 * terms into the post and create terms that don't exist.
 *
 * @param int   $post_id
 * @param array $taxonomy_terms
 * @since 1.0
 */
function set_taxonomy_terms( $post_id, $taxonomy_terms ) {
	// Now let's add the taxonomy/terms to syndicated post
	foreach ( $taxonomy_terms as $taxonomy => $terms ) {
		// Continue if taxonomy doesnt exist
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$term_ids        = [];
		$term_id_mapping = [];

		foreach ( $terms as $term_array ) {
			if ( ! is_array( $term_array ) ) {
				$term_array = (array) $term_array;
			}

			$term = get_term_by( 'slug', $term_array['slug'], $taxonomy );

			// Create terms on remote site if they don't exist
			$create_missing_terms = apply_filters( 'dt_create_missing_terms', true );

			if ( empty( $term ) ) {

				// Bail if terms shouldn't be created
				if ( false === $create_missing_terms ) {
					continue;
				}

				$term = wp_insert_term( $term_array['name'], $taxonomy );

				if ( ! is_wp_error( $term ) ) {
					$term_id_mapping[ $term_array['term_id'] ] = $term['term_id'];
					$term_ids[]                                = $term['term_id'];
				}
			} else {
				$term_id_mapping[ $term_array['term_id'] ] = $term->term_id;
				$term_ids[]                                = $term->term_id;
			}
		}

		// Handle hierarchical terms if they exist
		$update_term_hierachy = apply_filters( 'dt_update_term_hierarchy', true );

		if ( ! empty( $update_term_hierachy ) ) {
			foreach ( $terms as $term_array ) {
				if ( ! is_array( $term_array ) ) {
					$term_array = (array) $term_array;
				}

				if ( ! empty( $term_array['parent'] ) ) {
					wp_update_term(
						$term_id_mapping[ $term_array['term_id'] ], $taxonomy, [
							'parent' => $term_id_mapping[ $term_array['parent'] ],
						]
					);
				}
			}
		}

		wp_set_object_terms( $post_id, $term_ids, $taxonomy );
	}
}


/**
 * Given an array of media, set the media to a new post. This function will cleverly merge media into the
 * new post deleting duplicates. Meta and featured image information for each image will be copied as well.
 *
 * @param int   $post_id
 * @param array $media
 * @since 1.0
 */
function set_media( $post_id, $media ) {
	$current_media_posts = get_attached_media( get_allowed_mime_types(), $post_id );
	$current_media       = [];

	// Create mapping so we don't create duplicates
	foreach ( $current_media_posts as $media_post ) {
		$original                   = get_post_meta( $media_post->ID, 'dt_original_media_url', true );
		$current_media[ $original ] = $media_post->ID;
	}

	$found_featured_image = false;

	foreach ( $media as $media_item ) {

		// Delete duplicate if it exists (unless filter says otherwise)
		if ( apply_filters( 'dt_sync_media_delete_and_replace', true, $post_id ) ) {
			if ( ! empty( $current_media[ $media_item['source_url'] ] ) ) {
				wp_delete_attachment( $current_media[ $media_item['source_url'] ], true );
			}

			$image_id = process_media( $media_item['source_url'], $post_id );
		} else {
			if ( ! empty( $current_media[ $media_item['source_url'] ] ) ) {
				$image_id = $current_media[ $media_item['source_url'] ];
			} else {
				$image_id = process_media( $media_item['source_url'], $post_id );
			}
		}

		update_post_meta( $image_id, 'dt_original_media_url', $media_item['source_url'] );
		update_post_meta( $image_id, 'dt_original_media_id', $media_item['id'] );

		if ( $media_item['featured'] ) {
			$found_featured_image = true;
			update_post_meta( $post_id, '_thumbnail_id', $image_id );
		}

		// Transfer all meta
		set_meta( $image_id, $media_item['meta'] );

		// Transfer post properties
		wp_update_post(
			[
				'ID'           => $image_id,
				'post_title'   => $media_item['title'],
				'post_content' => $media_item['description']['raw'],
				'post_excerpt' => $media_item['caption']['raw'],
			]
		);
	}

	if ( ! $found_featured_image ) {
		delete_post_meta( $post_id, '_thumbnail_id' );
	}
}

/**
 * This is a helper function for transporting/formatting data about a media post
 *
 * @param  WP_Post $media_post
 * @since  1.0
 * @return array
 */
function format_media_post( $media_post ) {
	$media_item = array(
		'id'    => $media_post->ID,
		'title' => $media_post->post_title,
	);

	$media_item['featured'] = false;

	if ( $media_post->ID === (int) get_post_thumbnail_id( $media_post->post_parent ) ) {
		$media_item['featured'] = true;
	}

	$media_item['description'] = array(
		'raw'      => $media_post->post_content,
		'rendered' => apply_filters( 'the_content', $media_post->post_content ),
	);

	$media_item['caption'] = array(
		'raw' => $media_post->post_excerpt,
	);

	$media_item['alt_text']      = get_post_meta( $media_post->ID, '_wp_attachment_image_alt', true );
	$media_item['media_type']    = wp_attachment_is_image( $media_post->ID ) ? 'image' : 'file';
	$media_item['mime_type']     = $media_post->post_mime_type;
	$media_item['media_details'] = wp_get_attachment_metadata( $media_post->ID );
	$media_item['post']          = $media_post->post_parent;
	$media_item['source_url']    = wp_get_attachment_url( $media_post->ID );
	$media_item['meta']          = get_post_meta( $media_post->ID );

	return $media_item;
}

/**
 * Simple function for sideloading media and returning the media id
 *
 * @param  string $url
 * @param  int    $post_id
 * @since  1.0
 * @return int|bool
 */
function process_media( $url, $post_id ) {
	preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $url, $matches );
	if ( ! $matches ) {
		return false;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$file_array         = array();
	$file_array['name'] = basename( $matches[0] );

	// Download file to temp location.
	$file_array['tmp_name'] = download_url( $url );

	// If error storing temporarily, return the error.
	if ( is_wp_error( $file_array['tmp_name'] ) ) {
		return false;
	}

	// Do the validation and storage stuff.
	return media_handle_sideload( $file_array, $post_id );
}
