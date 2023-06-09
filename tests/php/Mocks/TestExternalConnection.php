<?php

namespace Distributor\Tests\Mocks;

/**
 * Classes for testing connections
 */
class TestExternalConnection extends \Distributor\ExternalConnection {
	static $slug               = 'test-external-connection';
	static $auth_handler_class = '\Distributor\Authentications\WordPressBasicAuth';
	static $namespace          = 'wp/v2';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function check_connections() { }

	public function remote_get( $args ) { }

	public function get_post_types() { }
}
