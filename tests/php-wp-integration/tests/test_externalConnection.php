<?php
/**
 * External connection tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use Distributor\Connections;
use Distributor\ExternalConnection;
use Distributor\Authentications\WordPressBasicAuth;
use Distributor\ExternalConnections\WordPressExternalConnection;

/**
 * Integration tests for ExternalConnection.
 *
 * @group ExternalConnection
 */
class Test_ExternalConnection extends WP_UnitTestCase {
	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		Connections::factory()->register( '\\' . Test_External_Connection::class );
	}

	/**
	 * Tear down.
	 *
	 * The registry is a singleton, so remove the test connection to avoid
	 * leaking it into other tests.
	 */
	public function tear_down() {
		unset( Connections::factory()->connections[ Test_External_Connection::$slug ] );
		parent::tear_down();
	}

	/**
	 * Create an external connection post.
	 *
	 * @param array $meta External connection meta data.
	 * @return int External connection post ID.
	 */
	protected function create_external_connection( $meta = array() ) {
		return self::factory()->post->create(
			array(
				'post_type'   => 'dt_ext_connection',
				'post_title'  => 'Test external connection',
				'post_status' => 'publish',
				'meta_input'  => $meta,
			)
		);
	}

	/**
	 * Test instantiation fails when the connection has no type or URL.
	 */
	public function test_instantiate_fail_type() {
		$connection_id = $this->create_external_connection();

		$external_connection = ExternalConnection::instantiate( $connection_id );

		$this->assertWPError( $external_connection, 'Instantiation should fail.' );
		$this->assertSame( 'external_connection_not_found', $external_connection->get_error_code() );
	}

	/**
	 * Test instantiation fails for a connection ID that does not exist.
	 */
	public function test_instantiate_fail_missing_post() {
		$connection_id = $this->create_external_connection(
			array(
				'dt_external_connection_type' => Test_External_Connection::$slug,
				'dt_external_connection_url'  => 'http://example.com/wp-json',
			)
		);
		wp_delete_post( $connection_id, true );

		$external_connection = ExternalConnection::instantiate( $connection_id );

		$this->assertWPError( $external_connection, 'Instantiation should fail.' );
		$this->assertSame( 'external_connection_not_found', $external_connection->get_error_code() );
	}

	/**
	 * Test instantiation fails when the connection type is not registered.
	 */
	public function test_instantiate_fail_not_registered() {
		$connection_id = $this->create_external_connection(
			array(
				'dt_external_connection_type' => 'fake',
				'dt_external_connection_url'  => 'fake',
			)
		);

		$external_connection = ExternalConnection::instantiate( $connection_id );

		$this->assertWPError( $external_connection, 'Instantiation should fail.' );
		$this->assertSame( 'no_external_connection_type', $external_connection->get_error_code() );
	}

	/**
	 * Test instantiation succeeds for a registered connection type.
	 */
	public function test_instantiate_success() {
		$connection_id = $this->create_external_connection(
			array(
				'dt_external_connection_type' => Test_External_Connection::$slug,
				'dt_external_connection_url'  => 'http://example.com/wp-json',
				'dt_external_connection_auth' => array(),
			)
		);

		$external_connection = ExternalConnection::instantiate( $connection_id );

		$this->assertInstanceOf( Test_External_Connection::class, $external_connection, 'Connection should be an instance of the registered class.' );
		$this->assertSame( 'Test external connection', $external_connection->name, 'Connection name should be the post title.' );
		$this->assertSame( 'http://example.com/wp-json', $external_connection->base_url, 'Connection base URL should be the stored URL.' );
		$this->assertSame( $connection_id, $external_connection->id, 'Connection ID should be the post ID.' );
		$this->assertInstanceOf( WordPressBasicAuth::class, $external_connection->auth_handler, 'Connection should use the class auth handler.' );
	}

	/**
	 * Test instantiation accepts a post object.
	 */
	public function test_instantiate_success_with_post_object() {
		$connection_id = $this->create_external_connection(
			array(
				'dt_external_connection_type' => Test_External_Connection::$slug,
				'dt_external_connection_url'  => 'http://example.com/wp-json',
			)
		);

		$external_connection = ExternalConnection::instantiate( get_post( $connection_id ) );

		$this->assertInstanceOf( Test_External_Connection::class, $external_connection, 'Connection should be an instance of the registered class.' );
		$this->assertSame( $connection_id, $external_connection->id, 'Connection ID should be the post ID.' );
	}

	/**
	 * Test instantiation of the plugin's WordPress external connection type.
	 */
	public function test_instantiate_wordpress_connection() {
		$connection_id = $this->create_external_connection(
			array(
				'dt_external_connection_type' => WordPressExternalConnection::$slug,
				'dt_external_connection_url'  => 'http://example.com/wp-json',
				'dt_external_connection_auth' => array(
					'username' => 'admin',
					'password' => 'password',
				),
			)
		);

		$external_connection = ExternalConnection::instantiate( $connection_id );

		$this->assertInstanceOf( WordPressExternalConnection::class, $external_connection, 'Connection should be a WordPress external connection.' );
		$this->assertInstanceOf( WordPressBasicAuth::class, $external_connection->auth_handler, 'Connection should use basic auth.' );
		$this->assertSame( base64_encode( 'admin:password' ), $external_connection->auth_handler->base64_encoded, 'Auth handler should encode the stored credentials.' ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}
}
