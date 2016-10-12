<?php

namespace Syndicate;

/**
 * Class for testing registration
 */
class TestConnection extends ExternalConnection {
	static $slug = 'test-connection';
	static $auth_handler_class = '\Syndicate\Authentications\WordPressBasicAuth';
	static $mapping_handler_class = '\Syndicate\Mappings\WordPressRestPost';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function check_connections() { }

	public function remote_get( $args ) { }
}

class ExternalConnectionsTest extends \TestCase {
	/**
	 * Test External Connection registration
	 * 
	 * @since 0.8
	 * @group ExternalConnections
	 */
	public function test_register() {
		ExternalConnections::factory()->register( '\Syndicate\TestConnection' );

		$this->assertEquals( '\Syndicate\TestConnection', ExternalConnections::factory()->get_registered()['test-connection'] );
	}

	/**
	 * Text External Connection instantiation failure on no type
	 *
	 * @since  0.8
	 * @group ExternalConnections
	 */
	public function test_instantiate_fail_type() {
		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_type', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_url', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_auth', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnections::factory()->instantiate( 1 );

		$this->assertEquals( 'external_connection_not_found', $external_connection->code );
	}

	/**
	 * Text External Connection instantiation failure on not registered
	 *
	 * @since  0.8
	 * @group ExternalConnections
	 */
	public function test_instantiate_fail_not_registered() {
		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_type', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_url', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_auth', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnections::factory()->instantiate( 1 );

		$this->assertEquals( 'no_external_connection_type', $external_connection->code );
	}

	/**
	 * Text External Connection instantiation success
	 *
	 * @since  0.8
	 * @group ExternalConnections
	 */
	public function test_instantiate_success() {
		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_type', true ),
		    'return' => 'test-connection',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_url', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'sy_external_connection_auth', true ),
		    'return' => array(),
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnections::factory()->instantiate( 1 );

		$this->assertTrue( is_a( $external_connection, '\Syndicate\ExternalConnection' ) );
	}
}