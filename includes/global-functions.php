<?php
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
 * @param string $data_name The unique identifier for the data.
 * @param array  $args {
 *     Array of settings to describe where the data is and how to process it.
 *
 *     @type string $location             Where is the data located? Either 'post_meta' or 'post_content'.
 *     @type array  $attributes {
 *         Additional details depending on location.
 *
 *         @type string|array $meta_keys           Required if data is located in meta.
 *         @type string       $shortcode_name      Required if data is in a shortcode.
 *         @type string|array $shortcode_attribute Required if data is in a shortcode.
 *         @type string       $block_name          Required if data is in a block.
 *         @type string|array $block_attribute     Required if data is in a block.
 *     }
 *     @type string   $type               Type of data: e.g. 'image', 'post', 'term'. If set,
 *                                        default callbacks can be used.
 *     @type callable $pre_distribute_cb  Function that returns extra data that needs to be added
 *                                        to the request (source processing).
 *     @type callable $post_distribute_cb Function that processes the extra data on the target
 *                                        and returns the resulting ID to replace the source ID.
 * }
 */
function distributor_register_data( $data_name, $args ) {
	/**
	 * Global registry for distributor data.
	 *
	 * @var array
		*/
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
		_doing_it_wrong( __FUNCTION__, esc_html__( "Invalid data location specified. It must be either post_meta or post_content.", 'distributor' ), DT_VERSION );
	}

	// Validate if meta_key is set for post_meta location.
	if ( 'post_meta' === $args['location'] && empty( $args['attributes']['meta_key'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( "Invalid data attributes specified. meta_keys is required for post_meta location.", 'distributor' ), DT_VERSION );
	}

	// Validate if callback functions are callable.
	if ( ! empty( $args['pre_distribute_cb'] ) && ! is_callable( $args['pre_distribute_cb'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( "Invalid data callback specified. pre_distribute_cb must be callable.", 'distributor' ), DT_VERSION );
	}

	if ( ! empty( $args['post_distribute_cb'] ) && ! is_callable( $args['post_distribute_cb'] ) ) {
		_doing_it_wrong( __FUNCTION__, esc_html__( "Invalid data callback specified. post_distribute_cb must be callable.", 'distributor' ), DT_VERSION );
	}

	$distributor_registered_data[ $data_name ] = $args;
}

/**
 * Get the registered distributor data.
 *
 * @since x.x.x
 *
 * @see distributor_register_data()
 * @return array
 */
function distributor_get_registered_data() {
	/**
	 * Global registry for distributor data.
	 *
	 * @var array
	 */
	global $distributor_registered_data;
	if ( ! isset( $distributor_registered_data ) ) {
		$distributor_registered_data = array();
	}

	return $distributor_registered_data;
}
