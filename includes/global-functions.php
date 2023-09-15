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
