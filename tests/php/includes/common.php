<?php

/**
 * Simple common WP classes/functions
 */

/**
 * Hollowed out WP_Error class for mocking
 *
 * @since  0.8
 */
class WP_Error {
	public function __construct( $code = '', $message = '' ) {
		$this->code = $code;
		$this->message = $message;
	}
}

/**
 * Check if object is WP_Error
 * 
 * @param  Object  $thing
 * @since  0.8
 * @return boolean
 */
function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}
