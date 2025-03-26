<?php

use function Distributor\Utils\prepare_registered_data_term;
use function Distributor\Utils\process_registered_data_term;
/**
 * Functions in the global namespace.
 *
 * These functions are required in the global namespace.
 *
 * @package distributor
 */

/**
 * Sanitizes a connection array passed in from the client.
 *
 * @param array $connection The connection array to sanitize.
 * @return array The sanitized connection array.
 */
function distributor_sanitize_connection( $connection ) {
	$type = $connection['type'];
	if ( ! in_array( $type, array( 'internal', 'external' ), true ) ) {
		return array();
	}

	$url = esc_url_raw( $connection['url'] );

	/*
	 * Internal URLs are stored without a scheme but external URLs include the scheme.
	 *
	 * As esc_url_raw() adds a scheme to internal URLs, we need to remove it.
	 */
	if ( 'internal' === $type ) {
		$url = preg_replace( '#^https?://#', '', $url );
	}
	// Put in a "safe" variable.
	$safe_url_do_not_change_edit_url_above = $url;

	$id = (int) $connection['id'];
	if ( empty( $id ) ) {
		return array();
	}
	// Put in a "safe" variable.
	$safe_id_do_not_change_edit_id_above = $id;

	$sanitized_connection = array(
		'type'       => sanitize_key( $connection['type'] ),
		'url'        => $safe_url_do_not_change_edit_url_above,
		'id'         => $safe_id_do_not_change_edit_id_above,
		'name'       => sanitize_text_field( $connection['name'] ),
		'syndicated' => sanitize_text_field( $connection['syndicated'] ),
	);
	return $sanitized_connection;
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Polyfill for `str_contains()` function added in PHP 8.0/WP 5.9.0.
	 *
	 * Performs a case-sensitive check indicating if needle is
	 * contained in haystack.
	 *
	 * @since 2.0.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return bool True if `$needle` is in `$haystack`, otherwise false.
	 */
	function str_contains( $haystack, $needle ) {
		return ( '' === $needle || false !== strpos( $haystack, $needle ) );
	}
}

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Polyfill for `str_starts_with()` function added in PHP 8.0/WP 5.9.0.
	 *
	 * Performs a case-sensitive check indicating if
	 * the haystack begins with needle.
	 *
	 * @since 2.0.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 * @return bool True if `$haystack` starts with `$needle`, otherwise false.
	 */
	function str_starts_with( $haystack, $needle ) {
		if ( '' === $needle ) {
			return true;
		}

		return 0 === strpos( $haystack, $needle );
	}
}

if ( ! function_exists( 'str_ends_with' ) ) {
	/**
	 * Polyfill for `str_ends_with()` function added in PHP 8.0/WP 5.9.0.
	 *
	 * Performs a case-sensitive check indicating if
	 * the haystack ends with needle.
	 *
	 * @since 2.0.0
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the `$haystack`.
	 * @return bool True if `$haystack` ends with `$needle`, otherwise false.
	 */
	function str_ends_with( $haystack, $needle ) {
		if ( '' === $haystack && '' !== $needle ) {
			return false;
		}

		$len = strlen( $needle );

		return 0 === substr_compare( $haystack, $needle, -$len, $len );
	}
}

/**
 * Register a data field for Stored ID handling.
 *
 * @since x.x.x
 *
 * @global array $distributor_registered_data Global registry for distributor data.
 *
 * @param string $data_name The unique identifier for the data.
 * @param array  $args {
 *     Array of settings to describe where the data is and how to process it.
 *
 *     @type string $location             Where is the data located? Either 'post_meta' or 'post_content'.
 *     @type array  $attributes {
 *         Additional details depending on location.
 *
 *         @type string|array $meta_key            Required if data is located in meta.
 *         @type string       $shortcode           Required if data is in a shortcode.
 *         @type string|array $shortcode_attribute Required if data is in a shortcode.
 *         @type string       $block_name          Required if data is in a block.
 *         @type string|array $block_attribute     Required if data is in a block.
 *     }
 *     @type string   $type               Type of data, e.g., 'image', 'post', or 'term'. If set, default callbacks can be used.
 *                                        Cannot be combined with custom callbacks. Adding custom callbacks will override the default behavior.
 *     @type callable $pre_distribute_cb  Function that returns extra data that needs to be added
 *                                        to the request (source processing).
 *     @type callable $post_distribute_cb Function that processes the extra data on the target
 *                                        and returns the resulting ID to replace the source ID.
 * }
 */
function distributor_register_data( $data_name, $args ) {
	global $distributor_registered_data;
	if ( ! isset( $distributor_registered_data ) ) {
		$distributor_registered_data = array();
	}

	if ( empty( $args ) ) {
		return;
	}

	// Default values.
	$defaults = array(
		'location'           => '',
		'attributes'         => array(),
		'type'               => '',
		'pre_distribute_cb'  => null,
		'post_distribute_cb' => null,
	);

	$args = wp_parse_args( $args, $defaults );

	// Validate the location.
	if ( ! in_array( $args['location'], array( 'post_meta', 'post_content' ), true ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Invalid data location specified. It must be either post_meta or post_content.', 'distributor' ), esc_attr( DT_VERSION ) );
	}

	// Validate if meta_key is set for post_meta location.
	if ( 'post_meta' === $args['location'] && empty( $args['attributes']['meta_key'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Invalid data attributes specified. meta_keys is required for post_meta location.', 'distributor' ), esc_attr( DT_VERSION ) );
	}

	// Validate if callback functions are callable.
	if ( ! empty( $args['pre_distribute_cb'] ) && ! is_callable( $args['pre_distribute_cb'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Invalid data callback specified. pre_distribute_cb must be callable.', 'distributor' ), esc_attr( DT_VERSION ) );
	}

	if ( ! empty( $args['post_distribute_cb'] ) && ! is_callable( $args['post_distribute_cb'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Invalid data callback specified. post_distribute_cb must be callable.', 'distributor' ), esc_attr( DT_VERSION ) );
	}

	// Validate if type is set and default callbacks are used.
	if ( ! empty( $args['type'] ) && ( ! empty( $args['pre_distribute_cb'] ) || ! empty( $args['post_distribute_cb'] ) ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Invalid data type specified. If type is set, custom callbacks cannot be used.', 'distributor' ), esc_attr( DT_VERSION ) );
	}

	// Set default callbacks based on type.
	if ( ! empty( $args['type'] ) ) {
		switch ( $args['type'] ) {
			case 'image':
				$args['pre_distribute_cb']  = 'distributor_image_pre_distribute_callback';
				$args['post_distribute_cb'] = 'distributor_image_post_distribute_callback';
				break;
			case 'post':
				$args['pre_distribute_cb']  = 'distributor_post_pre_distribute_callback';
				$args['post_distribute_cb'] = 'distributor_post_post_distribute_callback';
				break;
			case 'term':
				$args['pre_distribute_cb']  = 'distributor_term_pre_distribute_callback';
				$args['post_distribute_cb'] = 'distributor_term_post_distribute_callback';
				break;
		}
	}

	$distributor_registered_data[ $data_name ] = $args;
}

/**
 * Get the registered distributor data.
 *
 * @since x.x.x
 *
 * @global array $distributor_registered_data Global registry for distributor data.
 *
 * @see distributor_register_data()
 * @return array
 */
function distributor_get_registered_data() {
	global $distributor_registered_data;
	if ( ! isset( $distributor_registered_data ) ) {
		$distributor_registered_data = array();
	}

	return $distributor_registered_data;
}

/**
 * Pre-distribute callback for image data.
 * This is the default pre-distribute callback for image data, used when the type is set to 'image' in distributor_register_data().
 *
 * @param mixed $image The image ID to be processed before distribution.
 * @return array The extra data of the image to be distributed to the target site.
 */
function distributor_image_pre_distribute_callback( $image_id ) {
	// TODO: Implement pre processing.
	return array();
}

/**
 * Post-distribute callback for image data.
 * This is the default post-distribute callback for image data, used when the type is set to 'image' in distributor_register_data().
 *
 * @param mixed $image_extra_data The extra data to be processed after distribution.
 * @param mixed $source_image_id  The source image ID.
 * @param mixed $post_data        The post data.
 * @return int The ID of the distributed image.
 */
function distributor_image_post_distribute_callback( $image_extra_data, $source_image_id, $post_data ) {
	// Do something with the data.
	return $source_image_id;
}

/**
 * Pre-distribute callback for post data.
 * This is the default pre-distribute callback for post data, used when the type is set to 'post' in distributor_register_data().
 *
 * @param mixed $post_id The post ID to be processed before distribution.
 * @return array The data of the post to be distributed to the target site.
 */
function distributor_post_pre_distribute_callback( $post_id ) {
	// TODO: Implement pre processing.
	return array();
}

/**
 * Post-distribute callback for post data.
 * This is the default post-distribute callback for post data, used when the type is set to 'post' in distributor_register_data().
 *
 * @param mixed $post_extra_data The extra data to be processed after distribution.
 * @param mixed $source_post_id  The source post ID.
 * @param mixed $post_data       The post data.
 * @return int The ID of the distributed post.
 */
function distributor_post_post_distribute_callback( $post_extra_data, $source_post_id, $post_data ) {
	// TODO: Implement post processing.
	return $source_post_id;
}

/**
 * Pre-distribute callback for term data.
 * This is the default pre-distribute callback for term data, used when the type is set to 'term' in distributor_register_data().
 *
 * @param mixed $term_id The Term ID to be processed before distribution.
 * @return array|WP_Term The data of the term to be distributed to the target site.
 */
function distributor_term_pre_distribute_callback( $term_id ) {
	if ( ! $term_id ) {
		return array();
	}

	/**
	 * Filter whether to distribute term with parents.
	 * If set to true, the term will be distributed with its parents.
	 *
	 * @since x.x.x
	 * @hook dt_register_data_distribute_term_with_parents
	 *
	 * @param bool $with_parents Whether to distribute term with parents. Default false.
	 *
	 * @return bool Whether to distribute term with parents.
	 */
	$with_parents = apply_filters( 'dt_registered_data_distribute_term_with_parents', false );
	$term         = prepare_registered_data_term( $term_id, $with_parents );

	if ( ! $term ) {
		return array();
	}

	return $term;
}

/**
 * Post-distribute callback for term data.
 * This is the default post-distribute callback for term data, used when the type is set to 'term' in distributor_register_data().
 *
 * @param mixed $term_extra_data The extra data to be processed after distribution.
 * @param mixed $source_term_id  The source term ID.
 * @param mixed $post_data       The post data.
 * @return int The ID of the distributed term.
 */
function distributor_term_post_distribute_callback( $term_extra_data, $source_term_id, $post_data ) {
	if ( ! $term_extra_data ) {
		return $source_term_id;
	}

	$term_data = (array) $term_extra_data;
	$taxonomy  = $term_data['taxonomy'];

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return $source_term_id;
	}

	// Filter documented in distributor_term_pre_distribute_callback().
	$process_parent = apply_filters( 'dt_registered_data_distribute_term_with_parents', false );
	$new_term_id    = process_registered_data_term( $term_data, $process_parent );

	if ( empty( $new_term_id ) ) {
		return $source_term_id;
	}

	return $new_term_id;
}
