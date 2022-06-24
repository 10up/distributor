<?php
/**
 * Functions in the global namespace.
 *
 * These functions are required in the global namespace for the coding standards
 * sniffs to recognize them as custom escaping or sanitization functions.
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
