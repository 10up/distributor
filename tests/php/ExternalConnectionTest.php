<?php

namespace Distributor;

class ExternalConnectionTest extends \TestCase {

	/**
	 * Text External Connection instantiation failure on no type
	 *
	 * @since  0.8
	 * @group ExternalConnection
	 */
	public function test_instantiate_fail_type() {
		Connections::factory()->register( '\TestExternalConnection' );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_type', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_url', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_auth', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnection::instantiate( 1 );

		$this->assertEquals( 'external_connection_not_found', $external_connection->code );
	}

	/**
	 * Text External Connection instantiation failure on not registered
	 *
	 * @since  0.8
	 * @group ExternalConnection
	 */
	public function test_instantiate_fail_not_registered() {
		Connections::factory()->register( '\TestExternalConnection' );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_type', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_url', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_auth', true ),
		    'return' => false,
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnection::instantiate( 1 );

		$this->assertEquals( 'no_external_connection_type', $external_connection->code );
	}

	/**
	 * Text External Connection instantiation success
	 *
	 * @since  0.8
	 * @group ExternalConnection
	 */
	public function test_instantiate_success() {
		Connections::factory()->register( '\TestExternalConnection' );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_type', true ),
		    'return' => 'test-external-connection',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_url', true ),
		    'return' => 'fake',
		) );

		\WP_Mock::userFunction( 'get_post_meta', array(
		    'times' => 1,
		    'args' => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_auth', true ),
		    'return' => array(),
		) );

		\WP_Mock::userFunction( 'get_the_title', array(
		    'times' => 1,
		    'args' => 1,
		    'return' => '',
		) );

		// Test non-existent connection ID
		$external_connection = ExternalConnection::instantiate( 1 );

		$this->assertTrue( is_a( $external_connection, '\Distributor\ExternalConnection' ) );
	}
}
