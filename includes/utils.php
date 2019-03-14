<?php
/**
 * Utility functions
 *
 * @package distributor
 */

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
 * Determine if Gutenberg is being used.
 *
 * There are several possible variations that need to be accounted for:
 *
 *  - WordPress 4.9, Gutenberg plugin is not active.
 *  - WordPress 4.9, Gutenberg plugin is active.
 *  - WordPress 5.0, block editor by default.
 *  - WordPress 5.0, Classic editor plugin active, using classic editor.
 *  - WordPress 5.0, Classic editor plugin active, using the block editor.
 *
 * @since  1.2
 *
 * @param object $post The post object.
 * @return boolean
 */
function is_using_gutenberg( $post ) {
	global $wp_version;
	$gutenberg_available = function_exists( 'the_gutenberg_project' );
	$version_5_plus      = version_compare( $wp_version, '5', '>=' );

	if ( ! $gutenberg_available && ! $version_5_plus ) {
		return false;
	}

	// We have to use the function here instead of the filter due to differences in the way certain plugins implement this.
	if ( ! function_exists( 'use_block_editor_for_post' ) ) {
		include_once ABSPATH . 'wp-admin/includes/post.php';
	}

	// Previous to Gutenberg 5.0, `use_block_editor_for_post` was named `gutenberg_can_edit_post`.
	if ( ! function_exists( 'use_block_editor_for_post' ) ) {
		if ( function_exists( 'gutenberg_can_edit_post' ) ) {
			return gutenberg_can_edit_post( $post );
		}
		return false;
	}

	/**
	 * WordPress 5.1 will do a check_admin_referrer() inside the use_block_editor_for_posts(),
	 * and this call would fail and returns a 404 if there's custom meta box.
	 *
	 * Unsetting the 'meta-box-loader' in the global request would bypass that check.
	 */
	if ( isset( $_GET['meta-box-loader'] ) ) {
		$meta_box_loader = $_GET['meta-box-loader'];
		unset( $_GET['meta-box-loader'] );
	}

	$use_block_editor = use_block_editor_for_post( $post );

	/**
	 * Set the $meta_box_loader back to the request, if it exists
	 * so other areas that rely on it would still work.
	 */
	if ( isset( $meta_box_loader ) ) {
		$_GET['meta-box-loader'] = $meta_box_loader;
	}

	return use_block_editor_for_post( $post );
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
		'media_handling'         => 'featured',
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
		'email'         => '',
		'license_key'   => '',
		'valid_license' => null,
	];

	$settings = get_site_option( 'dt_settings', [] );
	$settings = wp_parse_args( $settings, $defaults );

	return $settings;
}

/**
 * Hit license API to see if key/email is valid
 *
 * @param  string $email Email address.
 * @param  string $license_key License key.
 * @since  1.2
 * @return bool
 */
function check_license_key( $email, $license_key ) {

	$request = wp_remote_post(
		'https://distributorplugin.com/wp-json/distributor-theme/v1/validate-license',
		[
			'timeout' => 10,
			'body'    => [
				'license_key' => $license_key,
				'email'       => $email,
			],
		]
	);

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
 * Given an array of meta, set meta to another post.
 *
 * Don't copy in blacklisted (Distributor) meta.
 *
 * @param int   $post_id Post ID.
 * @param array $meta Array of meta as key => value
 */
function set_meta( $post_id, $meta ) {
	$existing_meta    = get_post_meta( $post_id );
	$blacklisted_meta = blacklisted_meta();

	foreach ( $meta as $meta_key => $meta_values ) {
		if ( in_array( $meta_key, $blacklisted_meta, true ) ) {
			continue;
		}

		foreach ( (array) $meta_values as $meta_placement => $meta_value ) {
			$has_prev_value = isset( $existing_meta[ $meta_key ] )
								&& is_array( $existing_meta[ $meta_key ] )
								&& array_key_exists( $meta_placement, $existing_meta[ $meta_key ] )
								? true : false;
			if ( $has_prev_value ) {
				$prev_value = maybe_unserialize( $existing_meta[ $meta_key ][ $meta_placement ] );
			}

			if ( ! is_array( $meta_value ) ) {
				$meta_value = maybe_unserialize( $meta_value );
			}

			if ( $has_prev_value ) {
				update_post_meta( $post_id, $meta_key, $meta_value, $prev_value );
			} else {
				add_post_meta( $post_id, $meta_key, $meta_value );
			}
		}
	}

	/**
	 * Fires after Distributor sets post meta.
	 *
	 * Note: All sent meta is included in the `$meta` array, including blacklisted keys.
	 * Take care to continue to filter out blacklisted keys in any further meta setting.
	 *
	 * @param array $meta          All received meta for the post
	 * @param array $existing_meta Existing meta for the post
	 * @param int   $post_id       Post ID
	 *
	 * @since 1.3.8
	 */
	do_action( 'dt_after_set_meta', $meta, $existing_meta, $post_id );
}

/**
 * Get post types available for pulling.
 *
 * This will compare the public post types from a remote site
 * against the public post types from the origin site and return
 * an array of post types supported on both.
 *
 * @param \Distributor\Connection $connection Connection object
 * @param string                  $type Connection type
 * @since 1.3
 * @return array
 */
function available_pull_post_types( $connection, $type ) {
	$post_types        = array();
	$remote_post_types = $connection->get_post_types();

	if ( ! empty( $remote_post_types ) && ! is_wp_error( $remote_post_types ) ) {
		$local_post_types     = array_diff_key( get_post_types( [ 'public' => true ], 'objects' ), array_flip( [ 'attachment', 'dt_ext_connection', 'dt_subscription' ] ) );
		$available_post_types = array_intersect_key( $remote_post_types, $local_post_types );

		if ( ! empty( $available_post_types ) ) {
			foreach ( $available_post_types as $post_type ) {
				$post_types[] = array(
					'name' => 'external' === $type ? $post_type['name'] : $post_type->label,
					'slug' => 'external' === $type ? $post_type['slug'] : $post_type->name,
				);
			}
		}
	}

	/**
	 * Filter the post types that should be available for pull.
	 *
	 * Helpful for sites that want to pull custom post type content from another site into a different existing post type on the receiving end.
	 *
	 * @since 1.3.5
	 *
	 * @param array                   $post_types        Post types available for pull with name and slug
	 * @param array                   $remote_post_types Post types available from the remote connection
	 * @param array                   $local_post_types  Post types registered as public on the local site
	 * @param \Distributor\Connection $connection        Distributor connection object
	 * @param string                  $type              Distributor connection type
	 */
	return apply_filters( 'dt_available_pull_post_types', $post_types, $remote_post_types, $local_post_types, $connection, $type );
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

	/**
	 * Filter post types that are distributable.
	 *
	 * @since 1.0.0
	 * @param array Post types that are distributable. Default 'all post types except dt_ext_connection and dt_subscription'.
	 */
	return apply_filters( 'distributable_post_types', array_diff( $post_types, [ 'dt_ext_connection', 'dt_subscription' ] ) );
}

/**
 * Return post statuses that are allowed to be distributed.
 *
 * @since  1.0
 * @return array
 */
function distributable_post_statuses() {

	/**
	 * Filter the post statuses that are allowed to be distributed.
	 *
	 * By default only published posts can be distributed.
	 *
	 * @param array Post statuses.
	 */
	return apply_filters( 'dt_distributable_post_statuses', array( 'publish' ) );
}

/**
 * Returns list of blacklisted meta keys
 *
 * @since  1.2
 * @return array
 */
function blacklisted_meta() {
	/**
	 * Filter meta keys that are blacklisted.
	 *
	 * @since 1.0.0
	 *
	 * @param array Blacklisted meta keys. Default 'dt_unlinked, dt_connection_map, dt_subscription_update, dt_subscriptions, dt_subscription_signature, dt_original_post_id, dt_original_post_url, dt_original_blog_id, dt_syndicate_time, _wp_attached_file, _wp_attachment_metadata, _edit_lock, _edit_last, _wp_old_slug, _wp_old_date.
	 */
	return apply_filters(
		'dt_blacklisted_meta',
		[
			'dt_unlinked',
			'dt_connection_map',
			'dt_subscription_update',
			'dt_subscriptions',
			'dt_subscription_signature',
			'dt_original_post_id',
			'dt_original_post_url',
			'dt_original_blog_id',
			'dt_syndicate_time',
			'_wp_attached_file',
			'_wp_attachment_metadata',
			'_edit_lock',
			'_edit_last',
			'_wp_old_slug',
			'_wp_old_date',
		]
	);
}

/**
 * Prepare meta for consumption
 *
 * @param  int $post_id Post ID.
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
				$meta_value = maybe_unserialize( $meta_value );
				if ( false === apply_filters( 'dt_sync_meta', true, $meta_key, $meta_value, $post_id ) ) {
					continue;
				}
				$prepared_meta[ $meta_key ][] = $meta_value;
			}
		}
	}

	return $prepared_meta;
}

/**
 * Format media items for consumption
 *
 * @param  int $post_id Post ID.
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
 * @param  int $post_id Post ID.
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
	 * @param array  $taxonomies  Associative array list of taxonomies supported by current post
	 * @param object $post        The Post Object
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
 * @param int   $post_id Post ID.
 * @param array $taxonomy_terms Array with taxonomy as key and array of terms as values.
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
			/**
			 * Filter whether missing terms should be created.
			 *
			 * @since 1.0.0
			 *
			 * @param bool true Controls whether missing terms should be created. Default 'true'.
			 */
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
		/**
		 * Filter whether term hierarchy should be updated.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   true      Controls whether term hierarchy should be updated. Default 'true'.
		 * @param string $taxonomy The taxonomy slug for the current term.
		 */
		$update_term_hierachy = apply_filters( 'dt_update_term_hierarchy', true, $taxonomy );

		if ( ! empty( $update_term_hierachy ) ) {
			foreach ( $terms as $term_array ) {
				if ( ! is_array( $term_array ) ) {
					$term_array = (array) $term_array;
				}

				if ( ! empty( $term_array['parent'] ) ) {
					wp_update_term(
						$term_id_mapping[ $term_array['term_id'] ],
						$taxonomy,
						[
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
 * @param int   $post_id Post ID.
 * @param array $media Array of media posts.
 * @since 1.0
 */
function set_media( $post_id, $media ) {
	$settings            = get_settings(); // phpcs:ignore
	$current_media_posts = get_attached_media( get_allowed_mime_types(), $post_id );
	$current_media       = [];

	// Create mapping so we don't create duplicates
	foreach ( $current_media_posts as $media_post ) {
		$original                   = get_post_meta( $media_post->ID, 'dt_original_media_url', true );
		$current_media[ $original ] = $media_post->ID;
	}

	$found_featured_image = false;

	// If we only want to process the featured image, remove all other media
	if ( 'featured' === $settings['media_handling'] ) {
		$featured_keys = wp_list_pluck( $media, 'featured' );

		// Note: this is not a strict search because of issues with typecasting in some setups
		$featured_key = array_search( true, $featured_keys ); // @codingStandardsIgnoreLine Ignore strict search requirement.

		$media = ( false !== $featured_key ) ? array( $media[ $featured_key ] ) : array();
	}

	foreach ( $media as $media_item ) {

		// Delete duplicate if it exists (unless filter says otherwise)
		/**
		 * Filter whether media should be deleted and replaced if it already exists.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   true     Controls whether pre-existing media should be deleted and replaced. Default 'true'.
		 * @param int    $post_id The post ID.
		 */
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

		// Exit if the image ID is not valid.
		if ( ! $image_id ) {
			continue;
		}

		update_post_meta( $image_id, 'dt_original_media_url', $media_item['source_url'] );
		update_post_meta( $image_id, 'dt_original_media_id', $media_item['id'] );

		if ( $media_item['featured'] ) {
			$found_featured_image = true;
			set_post_thumbnail( $post_id, $image_id );
		}

		// Transfer all meta
		if ( isset( $media_item['meta'] ) ) {
			set_meta( $image_id, $media_item['meta'] );
		}

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
 * @param  \WP_Post $media_post Media post.
 * @since  1.0
 * @return array
 */
function format_media_post( $media_post ) {
	$media_item = array(
		'id'    => $media_post->ID,
		'title' => $media_post->post_title,
	);

	$media_item['featured'] = false;

	if ( (int) get_post_thumbnail_id( $media_post->post_parent ) === $media_post->ID ) {
		$media_item['featured'] = true;
	}

	$media_item['description'] = array(
		'raw'      => $media_post->post_content,
		'rendered' => get_processed_content( $media_post->post_content ),
	);

	$media_item['caption'] = array(
		'raw' => $media_post->post_excerpt,
	);

	$media_item['alt_text']      = get_post_meta( $media_post->ID, '_wp_attachment_image_alt', true );
	$media_item['media_type']    = wp_attachment_is_image( $media_post->ID ) ? 'image' : 'file';
	$media_item['mime_type']     = $media_post->post_mime_type;
	$media_item['media_details'] = apply_filters( 'dt_get_media_details', wp_get_attachment_metadata( $media_post->ID ), $media_post->ID );
	$media_item['post']          = $media_post->post_parent;
	$media_item['source_url']    = wp_get_attachment_url( $media_post->ID );
	$media_item['meta']          = \Distributor\Utils\prepare_meta( $media_post->ID );

	return apply_filters( 'dt_media_item_formatted', $media_item, $media_post->ID );
}

/**
 * Simple function for sideloading media and returning the media id
 *
 * @param  string $url URL of media.
 * @param  int    $post_id Post ID.
 * @since  1.0
 * @return int|bool
 */
function process_media( $url, $post_id ) {

	/**
	 * Filter allowed media extensions to be processed
	 *
	 * @since 1.3.7
	 *
	 * @param array $allowed_extensions Allowed extensions array.
	 * @param string $url Media url.
	 * @param int $post_id Post ID.
	 */
	$allowed_extensions = apply_filters( 'dt_allowed_media_extensions', array( 'jpg', 'jpeg', 'jpe', 'gif', 'png' ), $url, $post_id );
	preg_match( '/[^\?]+\.(' . implode( '|', $allowed_extensions ) . ')\b/i', $url, $matches );
	if ( ! $matches ) {
		$media_name = null;
	} else {
		$media_name = basename( $matches[0] );
	}

	/**
	 * Filter name of the processing media.
	 *
	 * @since 1.3.7
	 *
	 * @param string $media_name  Name of the processing media.
	 * @param string $url Media url.
	 * @param int $post_id Post ID.
	 */
	$media_name = apply_filters( 'dt_media_processing_filename', $media_name, $url, $post_id );

	if ( is_null( $media_name ) ) {
		return false;
	}

	$file_array         = array();
	$file_array['name'] = $media_name;

	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	// Allows to pull media from local IP addresses
	// Uses a "magic number" for priority so we only unhook our call, just in case
	add_filter( 'http_request_host_is_external', '__return_true', 88 );

	// Download file to temp location.
	$file_array['tmp_name'] = download_url( $url );

	remove_filter( 'http_request_host_is_external', '__return_true', 88 );

	// If error storing temporarily, return the error.
	if ( is_wp_error( $file_array['tmp_name'] ) ) {

		// Distributor is in debug mode, display the issue, could be storage related.
		if ( is_dt_debug() ) {
			error_log( sprintf( 'Distributor: %s', $file_array['tmp_name']->get_error_message() ) ); // @codingStandardsIgnoreLine
		}

		return false;
	}

	// Do the validation and storage stuff.
	$result = media_handle_sideload( $file_array, $post_id );
	if ( is_wp_error( $result ) ) {

		// Distributor is in debug mode, display the issue, could be storage related.
		if ( is_dt_debug() ) {
			error_log( sprintf( 'Distributor: %s', $file_array['tmp_name']->get_error_message() ) ); // @codingStandardsIgnoreLine
		}

		return false;
	}
	return (int) $result;
}

/**
 * Return whether a post type is compatible with the block editor.
 *
 * The block editor depends on the REST API, and if the post type is not shown in the
 * REST API, then it won't work with the block editor.
 *
 * @source WordPress 5.0.0
 *
 * @param string $post_type The post type.
 * @return bool Whether the post type can be edited with the block editor.
 */
function dt_use_block_editor_for_post_type( $post_type ) {
	if ( ! post_type_exists( $post_type ) ) {
		return false;
	}

	if ( ! post_type_supports( $post_type, 'editor' ) ) {
		return false;
	}

	$post_type_object = get_post_type_object( $post_type );
	if ( $post_type_object && ! $post_type_object->show_in_rest ) {
		return false;
	}

	/**
	 * Filter whether a post is able to be edited in the block editor.
	 *
	 * @since 5.0.0
	 *
	 * @param bool   $use_block_editor  Whether the post type can be edited or not. Default true.
	 * @param string $post_type         The post type being checked.
	 */
	return apply_filters( 'use_block_editor_for_post_type', true, $post_type );
}

/**
 * Helper function to process post content.
 *
 * @param string $post_content The post content.
 *
 * @return string $post_content The processed post content.
 */
function get_processed_content( $post_content ) {

	global $wp_embed;
	/**
	 * Remove autoembed filter so that actual URL will be pushed and not the generated markup.
	 */
	remove_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );
	$post_content = apply_filters( 'the_content', $post_content );
	add_filter( 'the_content', [ $wp_embed, 'autoembed' ], 8 );

	return $post_content;
}
