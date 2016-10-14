<?php
namespace Syndicate\ExternalConnections;
use \Syndicate\Authentications\WordPressBasicAuth as WordPressBasicAuth;

class WordPressExternalConnectionTest extends \TestCase {

	public function setUp(){

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

		$this->assertTrue( is_a( $connection, '\Syndicate\ExternalConnection' ) );

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

		$post_type = 'foo';

        $body = json_encode( [
    		'id' => 123,
            $post_type => [
				'_links' => [
					'wp:items' => [
						0 => [
							'href' => 'http://url.com'
						]
					]
				]
			]
        ] );

		$this->user_functions = [
	        [ 'function' => 'untrailingslashit' ],
			[ 'function' => 'get_the_title' ],
			[ 'function' => 'wp_remote_post' ],
			[ 'function' => 'esc_html__' ],
			[
				'function'   => 'get_post',
				'params' => [
					'args'   => 1,
					'return' => ( object ) [
		                'post_content' => 'my post content',
		                'post_type'    => $post_type,
		                'post_excerpt' => 'post excerpt',
		            ]
		        ]
			],
			[
				'function'   => 'get_post_type',
				'params' => [ 'return' => $post_type ]
			],
			[
				'function'   => 'wp_remote_get',
				'params' => [ 'return' => $body ]
			],
			[
				'function'   => 'wp_remote_retrieve_body',
				'params' => [ 'return' => $body ]
			]
		];

		foreach( $this->user_functions as $key => $value ){

			if ( isset( $value['params'] ) ){
				$params = $value['params'];
			} else {
				$params = [];
			}

        	\WP_Mock::userFunction( $value['function'], $params );
        }

    	$this->assertInstanceOf( \WP_Error::class, $this->connection->push(0));
        $this->assertTrue( is_int( $this->connection->push(1) ) );

    }

    /**
     * Test if the pull method returns an array.
     *
     * @return void
     */
    public function test_pull() {

    	\WP_Mock::userFunction('wp_remote_retrieve_response_code');

    	$this->assertTrue( is_array( $this->connection->pull( [] ) ) );

    }

}
