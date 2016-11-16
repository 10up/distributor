<?php
namespace Distributor\ExternalConnections;
use \Distributor\Authentications\WordPressBasicAuth as WordPressBasicAuth;

class WordPressExternalConnectionTest extends \TestCase {

	public function setUp() {

		$this->auth       = new WordPressBasicAuth( array() );
		$this->connection = new WordPressExternalConnection( 'name', 'url', 1, $this->auth );

	}

	/**
	 * Test creating a WordPressExternalConnection object
	 *
	 * @since  0.8
	 * @group WordPressExternalConnection
	 */
	public function test_construct() {
		try {
			$connection = new WordPressExternalConnection();
		} catch ( \Exception $e ) {
			// Requires arguments
			$this->assertTrue( true );
		}

		// Now test a successful creation
		$auth = new WordPressBasicAuth( array() );

		$connection = new WordPressExternalConnection( 'name', 'url', 1, $auth );

		$this->assertTrue( is_a( $connection, '\Distributor\ExternalConnection' ) );

		// Check connection properties
		$this->assertTrue( ! empty( $connection->name ) );
		$this->assertTrue( ! empty( $connection->base_url ) );
		$this->assertTrue( ! empty( $connection->id ) );
		$this->assertTrue( ! empty( $connection->auth_handler ) );
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
	 */
	public function test_push() {

		\WP_Mock::userFunction( 'untrailingslashit' );
		\WP_Mock::userFunction( 'get_the_title' );
		\WP_Mock::userFunction( 'wp_remote_post' );
		\WP_Mock::userFunction( 'esc_html__' );

		$post_type = 'foo';

		$body = json_encode( [
			'id' => 123,
			$post_type => [
				'_links' => [
					'wp:items' => [
						0 => [
							'href' => 'http://url.com',
						],
					],
				],
			],
		] );

		\WP_Mock::userFunction( 'get_post', [
			'args'   => 1,
			'return' => ( object ) [
                'post_content' => 'my post content',
                'post_type'    => $post_type,
                'post_excerpt' => 'post excerpt',
            	],
		] );

		\WP_Mock::userFunction( 'get_post_type', [
			'return' => $post_type
		] );

		\WP_Mock::userFunction( 'wp_remote_get', [
			'return' => $body
		] );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body', [
			'return' => $body
		] );

		$this->assertInstanceOf( \WP_Error::class, $this->connection->push( 0 ) );
		$this->assertTrue( is_int( $this->connection->push( 1 ) ) );

	}

	/**
	 * Test if the pull method returns an array.
	 *
	 * @since  0.8
	 * @return void
	 */
	public function test_pull() {

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' );

		remote_get_setup();

		\WP_Mock::userFunction( 'get_current_user_id' );

		\Mockery::mock( 'WP_Post' );

		\WP_Mock::userFunction( 'wp_insert_post', [
			'return' => []
		] );

		$this->assertTrue( is_array( $this->connection->pull( [
			123
		] ) ) );

	}

	/**
	 * Handles mocking the correct remote request to receive a WP_Post instance.
	 *
	 * @since  0.8
	 * @group ExternalConnection
	 */
	public function test_remote_get(){

		remote_get_setup();

        $this->assertInstanceOf( \WP_Post::class, $this->connection->remote_get( [
			'id'        => 111,
			'post_type' => 'post',
		] ) );

	}

	/**
	 * Check that the connection does not return an error
	 *
	 * @since 0.8
	 * @return void
	 */
	public function test_check_connections(){

		\WP_Mock::userFunction( 'wp_remote_retrieve_body', [
			'return' => json_encode( [
				'routes' => 'my routes'
			] )
		] );

		\WP_Mock::userFunction( 'wp_remote_retrieve_headers', [
			'return' => [
				'Link' => null
			]
		] );

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code', [
			'return' => 200
		] );

		$this->assertTrue( empty( $this->connection->check_connections()['errors'] ) );

	}
}
