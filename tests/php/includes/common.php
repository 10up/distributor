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
 * @param  Object $thing
 * @since  0.8
 * @return boolean
 */
function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

/**
 * Classes for testing connections
 */
class TestExternalConnection extends \Syndicate\ExternalConnection {
	static $slug = 'test-external-connection';
	static $auth_handler_class = '\Syndicate\Authentications\WordPressBasicAuth';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function check_connections() { }

	public function remote_get( $args ) { }
}

class TestInternalConnection extends \Syndicate\Connection {
	static $slug = 'test-internal-connection';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function remote_get( $args ) { }

	public function log_sync( array $item_id_mappings ) { }
}
