<?php

namespace Distributor\InternalConnections;

use WP_Mock\Tools\TestCase;

class NetworkSiteConnectionsTest extends TestCase {

	public function setUp(): void {
		$this->site_obj = \Mockery::mock(
			'\WP_Site', [
				'args'   => 1,
				'return' => '',
			]
		);

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

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'ID'           => 111,
					'post_content' => '',
					'post_excerpt' => '',
					'post_type'    => '',
					'post_name'    => '',
				],
			]
		);

		\WP_Mock::userFunction(
			'get_current_blog_id', [
				'return' => 925,
			]
		);

		\WP_Mock::userFunction( 'get_current_user_id' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'add_filter' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_the_title' );
		\WP_Mock::userFunction( 'remove_filter' );
		\WP_Mock::userFunction( 'get_option' );

		$this->connection_obj->site->blog_id = 2;

		$original_url = 'original url';
		$new_post_id  = 123;

		\WP_Mock::userFunction(
			'wp_insert_post', [
				'return' => $new_post_id,
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ $new_post_id, 'dt_original_post_id', true ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ $new_post_id, 'dt_original_blog_id', 925 ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ $new_post_id, 'dt_syndicate_time', \WP_Mock\Functions::type( 'int' ) ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ $new_post_id, 'dt_original_post_url', $original_url ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_permalink', [
				'return' => $original_url,
			]
		);

		/**
		 * We will test the util prepare/set functions later
		 */
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_meta' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_meta' );

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

		$original_url = 'original url';

		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_current_blog_id' );
		\WP_Mock::userFunction( 'remove_filter' );

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'ID'        => 111,
					'post_tite' => 'My post title',
					'meta'      => [],
				],
			]
		);

		\WP_Mock::userFunction(
			'get_permalink', [
				'return' => $original_url,
			]
		);

		\WP_Mock::userFunction(
			'wp_insert_post', [
				'return' => 123,
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_post_id', 2 ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_blog_id', 2 ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_original_post_url', $original_url ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_syndicate_time', \WP_Mock\Functions::type( 'int' ) ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_connection_map', true ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ \WP_Mock\Functions::type( 'int' ), 'dt_connection_map', \WP_Mock\Functions::type( 'array' ) ],
			]
		);

		/**
		 * We will test the util prepare/set functions later
		 */
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_meta' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\set_meta' );

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
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_meta' );

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
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_media' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_taxonomy_terms' );
		\WP_Mock::userFunction( '\Distributor\Utils\prepare_meta' );

		$this->connection_obj->site->blog_id = 321;

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'ID'         => 111,
					'post_title' => 'my title',
				],
			]
		);

		$this->assertArrayHasKey(
			'post_title', (array) $this->connection_obj->remote_get(
				[
					'id' => 123,
				]
			)
		);

	}

}
