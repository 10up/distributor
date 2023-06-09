<?php

namespace Distributor\Tests;

use Distributor\Authentications\WordPressBasicAuth as WordPressBasicAuth;
use Distributor\ExternalConnection;
use Distributor\ExternalConnections\WordPressExternalConnection;

class WordPressExternalConnectionTest extends Utils\TestCase {
	private WordPressExternalConnection $connection;

	private WordPressBasicAuth $auth;

	public function setUp(): void {
		parent::setUp();

		$this->auth       = new WordPressBasicAuth( array() );
		$this->connection = new WordPressExternalConnection( 'name', 'url', 1, $this->auth );
	}

	/**
	 * Test creating a WordPressExternalConnection object
	 *
	 * @since  0.8
	 * @group  WordPressExternalConnection
	 */
	public function test_construct(): void {
		// Now test a successful creation
		$auth = new WordPressBasicAuth( array() );

		$connection = new WordPressExternalConnection( 'name', 'url', 1, $auth );

		$this->assertTrue( is_a( $connection, ExternalConnection::class ) );

		// Check connection properties
		$this->assertNotEmpty( $connection->name );
		$this->assertNotEmpty( $connection->base_url );
		$this->assertNotEmpty( $connection->id );
		$this->assertNotEmpty( $connection->auth_handler );
	}

	/**
	 * This test has been greatly simplified to handle testing that the push
	 * method returns true, or an instance of WP_Error.
	 *
	 * An elaborated test case would verify that each WP_Error returns the
	 * error id, and error message it specifies.
	 *
	 * This is needed so the method parse_type_items_link() can return a valid URL
	 * otherwise that method will return false, rending our test false as well.
	 * Valid response body, with JSON encoded body
	 *
	 * @group  WordPressExternalConnection
	 * @since  0.8
	 */
	public function test_push(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test if the pull method returns an array.
	 *
	 * @since  0.8
	 * @group  WordPressExternalConnection
	 */
	public function test_pull() {
		$this->markTestIncomplete();
	}

	/**
	 * Handles mocking the correct remote request to receive a WP_Post instance.
	 *
	 * @since  0.8
	 * @group  WordPressExternalConnection
	 */
	public function test_remote_get() {
		$this->markTestIncomplete();
	}

	/**
	 * Check that the connection does not return an error
	 *
	 * @since 0.8
	 * @group WordPressExternalConnection
	 */
	public function test_check_connections_no_errors() {
		$this->markTestIncomplete();
	}

	/**
	 * Check that the connection properly returns a no distributor warning
	 *
	 * @since 1.0
	 * @group WordPressExternalConnection
	 */
	public function test_check_connections_no_distributor() {
		$this->markTestIncomplete();
	}
}
