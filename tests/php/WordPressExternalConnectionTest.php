<?php
namespace Distributor\ExternalConnections;
use \Distributor\Authentications\WordPressBasicAuth as WordPressBasicAuth;

class WordPressExternalConnectionTest extends \TestCase {

	public function setUp() {

		$this->auth       = new WordPressBasicAuth( array() );
		$this->connection = new WordPressExternalConnection( 'name', 'url', 1, $this->auth );

		\WP_Mock::userFunction( 'untrailingslashit' );

		\WP_Mock::userFunction( 'get_post_type', [
			'return' => 'foo'
		] );

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
	 * Push method testing when there is no ID present to push.
	 * The push method will return a WP Error object with
	 * the id of "no-push-post-id"
	 *
	 * @since  0.8
	 */
	public function test_push_no_id(){

		$this->assertEquals( $this->connection->push( false ), new \WP_Error( 'no-push-post-id' ) );

	}

	/**
	 * Push method with an ID, however this simulates a WP Error
	 * response from the wp_remote_get function. When the
	 * error is returned no message is checked.
	 *
	 * @since  0.8
	 */
	public function test_push_reponse_is_wp_error(){

		\WP_Mock::userFunction( 'wp_remote_get', [
			'return' => new \WP_Error('','')
		] );

		$this->assertEquals( $this->connection->push( 123 ), new \WP_Error('','') );

	}

	/**
	 * Push method with an ID, however on this test the
	 * wp_remote_retrieve_body will return a WP Error.
	 *
	 * @since 0.8
	 */
	public function test_push_body_is_wp_error(){

		\WP_Mock::userFunction( 'wp_remote_retrieve_body', [
			'return' => new \WP_Error('','')
		] );

		$this->assertEquals( $this->connection->push( 123 ), new \WP_Error('','') );

	}

	/**
	 * Push method with an ID, wp_remote_get, and wp_remote_retrieve_body
	 * do not return an error. However there is no "post type" set
	 * therefore the class of WP Error is returned with an id
	 * of "no-push-post-type"
	 *
	 * @since 0.8
	 */
	public function test_push_no_post_type(){

		\WP_Mock::userFunction( 'get_post_type' );
		\WP_Mock::userFunction( 'wp_remote_get' );
		\WP_Mock::userFunction( 'wp_remote_retrieve_body' );

		$this->assertEquals( $this->connection->push( 1 ), new \WP_Error( 'no-push-post-type', null ) );

	}

	// no check for is remote post is NOT wp error; https://github.com/10up/distributor/blob/aab6b906d3d8b3ce31c59949674dafbeeddce64e/includes/classes/ExternalConnections/WordPressExternalConnection.php#L303
	public function test_push_test_reponse_is_wp_error_two(){}

	// no check for is remote body NOT wp error; https://github.com/10up/distributor/blob/aab6b906d3d8b3ce31c59949674dafbeeddce64e/includes/classes/ExternalConnections/WordPressExternalConnection.php#L308
	public function test_push_body_is_wp_error_two(){}

	// no check for try statement; https://github.com/10up/distributor/blob/aab6b906d3d8b3ce31c59949674dafbeeddce64e/includes/classes/ExternalConnections/WordPressExternalConnection.php#L314
	public function test_push_no_post_remote_id(){}

	/**
	 * Push method with an ID, and a successful; wp_remote_get, wp_remote_retrieve_body,
	 * along with the post object, and item links set.
	 *
	 * @since 0.8
	 */
	public function test_push_post_remote_id(){

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
			'return' => ( object ) [
				'post_content' => 'my post content',
				'post_type' => $post_type,
				'post_excerpt' => 'my post excerpt',
				'status' => 'publish',
			]
		] );

		\WP_Mock::userFunction( 'wp_remote_post', [
			'return' => 'no'
		] );

		\WP_Mock::userFunction( 'wp_remote_retrieve_body', [
			'return' => $body
		] );

		$this->assertEquals( $this->connection->push( 123 ), 123 );

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
