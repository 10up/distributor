<?php
namespace Distributor\ExternalConnections;

use \Distributor\Authentications\WordPressBasicAuth as WordPressBasicAuth;
use WP_Mock\Tools\TestCase;

class WordPressExternalConnectionTest extends TestCase {

	public function setUp() {

		$this->auth       = new WordPressBasicAuth( array() );
		$this->connection = new WordPressExternalConnection( 'name', 'url', 1, $this->auth );

	}

	/**
	 * Test creating a WordPressExternalConnection object
	 *
	 * @since  0.8
	 * @group WordPressExternalConnection
	 * @runInSeparateProcess
	 */
	public function test_construct() {
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
	 *
	 * @group WordPressExternalConnection
	 * @since  0.8
	 * @runInSeparateProcess
	 */
	public function test_push() {

		\WP_Mock::userFunction( 'untrailingslashit' );
		\WP_Mock::userFunction( 'get_the_title' );
		\WP_Mock::userFunction( 'wp_remote_post' );
		\WP_Mock::userFunction( 'esc_html__' );
		\WP_Mock::userFunction( 'get_bloginfo' );

		$post_type = 'foo';

		$body = json_encode(
			[
				'id'       => 123,
				$post_type => [
					'_links' => [
						'wp:items' => [
							0 => [
								'href' => 'http://url.com',
							],
						],
					],
				],
			]
		);

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'post_content' => 'my post content',
					'post_type'    => $post_type,
					'post_excerpt' => 'post excerpt',
					'post_name'    => 'slug',
				],
			]
		);

		\WP_Mock::userFunction(
			'get_post_type', [
				'return' => $post_type,
			]
		);

		\WP_Mock::userFunction(
			'wp_generate_password', [
				'return' => '12345',
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_get', [
				'return' => $body,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_body', [
				'return' => $body,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [],
			]
		);

		/**
		 * We will test the util prepare functions later
		 */
		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_media', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_taxonomy_terms', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_meta', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction( 'get_permalink' );

		\WP_Mock::userFunction(
			'remove_filter', [
				'times' => 2,
			]
		);

		$this->assertInstanceOf( \WP_Error::class, $this->connection->push( 0 ) );
		$this->assertTrue( is_int( $this->connection->push( 1 ) ) );

		/**
		 * Let's ensure \Distributor\Subscriptions\create_subscription is called when the X-Distributor header is
		 * returned by the remote API
		 */

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [
					'X-Distributor' => true,
				],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Subscriptions\create_subscription', [
				'times'  => 0,
				'return' => [
					'X-Distributor' => true,
				],
			]
		);

		$this->assertTrue( is_int( $this->connection->push( 1 ) ) );
	}

	/**
	 * Test if the pull method returns an array.
	 *
	 * @since  0.8
	 * @group WordPressExternalConnection
	 * @runInSeparateProcess
	 */
	public function test_pull() {
		$post_id = 123;

		\WP_Mock::userFunction( 'wp_remote_retrieve_response_code' );
		\WP_Mock::userFunction( 'untrailingslashit' );
		\WP_Mock::userFunction( 'sanitize_text_field' );

		remote_get_setup();

		\WP_Mock::userFunction( 'get_current_user_id' );
		\WP_Mock::userFunction( 'delete_post_meta' );

		\WP_Mock::userFunction(
			'wp_insert_post', [
				'return' => 2,
			]
		);

		\WP_Mock::userFunction(
			'get_attached_media', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_allowed_mime_types', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'add_query_arg', [
				'times' => 1,
			]
		);

		$this->assertTrue(
			is_array(
				$this->connection->pull(
					[
						[
							'remote_post_id' => $post_id,
							'post_type'      => 'post',
						],
					]
				)
			)
		);
	}

	/**
	 * Handles mocking the correct remote request to receive a WP_Post instance.
	 *
	 * @since  0.8
	 * @group WordPressExternalConnection
	 * @runInSeparateProcess
	 */
	public function test_remote_get() {

		remote_get_setup();

		\WP_Mock::passThruFunction( 'untrailingslashit' );
		\WP_Mock::userFunction( 'get_current_user_id' );

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code', [
				'times'  => 2,
				'return' => 200,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'times'  => 1,
				'return' => [
					'X-Distributor' => true,
				],
			]
		);

		\WP_Mock::userFunction(
			'add_query_arg', [
				'times' => 1,
			]
		);
		$this->assertInstanceOf(
			\WP_Post::class, $this->connection->remote_get(
				[
					'id'        => 111,
					'post_type' => 'post',
				]
			)
		);

	}

	/**
	 * Check that the connection does not return an error
	 *
	 * @since 0.8
	 * @group WordPressExternalConnection
	 * @runInSeparateProcess
	 */
	public function test_check_connections_no_errors() {

		\WP_Mock::userFunction(
			'wp_remote_retrieve_body', [
				'return' => json_encode(
					[
						'routes' => 'my routes',
					]
				),
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [
					'Link' => null,
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code', [
				'return' => 200,
			]
		);

		\WP_Mock::userFunction( 'wp_remote_get' );
		\WP_Mock::userFunction( 'untrailingslashit' );

		$check = $this->connection->check_connections();

		$this->assertTrue( ! empty( $check['errors']['no_distributor'] ) );
	}

	/**
	 * Check that the connection properly returns a no distributor warning
	 *
	 * @since 1.0
	 * @group WordPressExternalConnection
	 * @runInSeparateProcess
	 */
	public function test_check_connections_no_distributor() {
		\WP_Mock::userFunction(
			'wp_remote_retrieve_body', [
				'return' => json_encode(
					[
						'routes' => 'my routes',
					]
				),
			]
		);

		\WP_Mock::userFunction( 'wp_remote_get' );
		\WP_Mock::userFunction( 'untrailingslashit' );

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code', [
				'return' => 200,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [
					'X-Distributor' => true,
					'Link'          => null,
				],
			]
		);

		$this->assertTrue( empty( $this->connection->check_connections()['errors']['no_distributor'] ) );
	}
}
