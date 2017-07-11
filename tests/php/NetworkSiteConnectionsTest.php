<?php

namespace Distributor\InternalConnections;

class NetworkSiteConnectionsTest extends \TestCase {

	public function setUp() {
		$this->site_obj = \Mockery::mock( '\WP_Site', [
			'args' => 1,
			'return' => '',
		] );

		$this->connection_obj = new NetworkSiteConnection( $this->site_obj );
	}

	/**
	 * Push returns an post ID on success instance of WP Error on failure.
	 *
	 * @since  0.8
	 * @group NetworkSiteConnection
	 * @runInSeparateProcess
	 */
	public function test_push() {

		\WP_Mock::userFunction( 'get_post', [
			'return' => (object) [
				'post_content' => '',
				'post_excerpt' => '',
				'post_type' => '',
			],
		] );

		\WP_Mock::userFunction( 'get_current_blog_id', [
			'return' => 925,
		] );

		\WP_Mock::userFunction( 'get_current_user_id' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'add_filter' );
		\WP_Mock::userFunction( 'remove_filter' );
		\WP_Mock::userFunction( 'get_option' );

		$this->connection_obj->site->blog_id = 2;

		\WP_Mock::userFunction( 'wp_insert_post', [
			'return' => 123,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_post_id', true ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_blog_id', 925 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_syndicate_time', time() ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'return' => [
				'no_dt_unlinked'         => [ 0 ],
				'no_dt_original_post_id' => [ 0 ],
				'no_dt_original_blog_id' => [ 0 ],
				'no_dt_syndicate_time'   => [ 0 ],
			],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_unlinked', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_original_post_id', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_original_blog_id', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_syndicate_time', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_the_title' );

		\WP_Mock::onFilter( 'the_content' )
			->with( '' )
			->reply( '' );

		\WP_Mock::expectFilterAdded( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

		$this->assertTrue( is_int( $this->connection_obj->push( 1 ) ) );

	}

	/**
	 * Pull returns an array of Post IDs on success. This test simulates sending an
	 * array containing three IDs (integers) will receive an array containing
	 * three integers.
	 *
	 * @since  0.8
	 * @group NetworkSiteConnection
	 * @runInSeparateProcess
	 */
	public function test_pull() {

		$this->connection_obj->site->blog_id = 2;

		\WP_Mock::userFunction( 'get_permalink' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_current_blog_id' );
		\WP_Mock::userFunction( 'remove_filter' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'return' => [
				'no_dt_unlinked'         => [ 0 ],
				'no_dt_original_post_id' => [ 0 ],
				'no_dt_original_blog_id' => [ 0 ],
				'no_dt_syndicate_time'   => [ 0 ],
			],
		] );

		\WP_Mock::userFunction( 'get_post', [
			'return' => (object) [
				'post_tite' => 'My post title',
				'meta'      => [],
			],
		] );

		\WP_Mock::userFunction( 'wp_insert_post', [
			'return' => 123,
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_post_id', 2 ],
			'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_blog_id', 2 ],
			'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_syndicate_time', time() ],
			'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_unlinked', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_original_post_id', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_original_blog_id', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'no_dt_syndicate_time', 0 ],
		    'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
		    'times'  => 1,
		    'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_connection_map', \WP_Mock\Functions::type( 'array' ) ],
		    'return' => [],
		] );

		$this->assertTrue( count( $this->connection_obj->pull( [ [ 'remote_post_id' => 2 ] ] ) ) === 1 );

	}

	/**
	 * Verifies that when passed no id the request can still return items
	 *
	 * @since 0.8
	 * @group NetworkSiteConnection
	 * @runInSeparateProcess
	 */
	public function test_remote_get_empty_id() {

		$this->connection_obj->site->blog_id = 321;

		\WP_Mock::userFunction( 'get_option' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_permalink' );
		\WP_Mock::userFunction( 'get_post_meta' );

		$this->assertArrayHasKey( 'total_items', $this->connection_obj->remote_get() );

	}

	/**
	 * Verifies that the remote_get method returns an array containing the post title.
	 *
	 * @since 0.8
	 * @group NetworkSiteConnection
	 * @runInSeparateProcess
	 */
	public function test_remote_get() {

		\WP_Mock::userFunction( 'get_option' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_permalink' );
		\WP_Mock::userFunction( 'get_post_meta' );

		$this->connection_obj->site->blog_id = 321;

		\WP_Mock::userFunction( 'get_post', [
			'return' => (object) [
				'post_title' => 'my title',
			],
		] );

		$this->assertArrayHasKey( 'post_title', (array) $this->connection_obj->remote_get( [
			'id' => 123,
		] ) );

	}

}
