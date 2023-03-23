<?php

namespace Distributor\InternalConnections;

use WP_Mock\Tools\TestCase;

class NetworkSiteConnectionsTest extends TestCase {
	/**
	 * Site object.
	 *
	 * @var \WP_Site
	 */
	public $site_obj;

	/**
	 * Connection object.
	 *
	 * @var NetworkSiteConnection
	 */
	public $connection_obj;

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
	 * Helper function to mock get_post_meta.
	 */
	public function setup_post_meta_mock( $post_meta ) {
		$get_post_meta = function( $post_id, $key = '', $single = false ) use ( $post_meta ) {
			if ( empty( $key ) ) {
				return $post_meta;
			}

			if ( isset( $post_meta[ $key ] ) ) {
				if ( $single ) {
					return $post_meta[ $key ][0];
				}
				return $post_meta[ $key ];
			}

			return '';
		};

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => $get_post_meta,
			)
		);
	}

	/**
	 * Push returns an post ID on success instance of WP Error on failure.
	 *
	 * @since  0.8
	 * @group NetworkSiteConnection
	 * @runInSeparateProcess
	 */
	public function test_push() {
		// There is no post meta to mock for a source post.
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'ID'           => 111,
					'post_content' => '',
					'post_excerpt' => '',
					'post_type'    => '',
					'post_name'    => '',
					'post_status'  => 'publish',
				],
			]
		);

		\WP_Mock::userFunction(
			'has_blocks', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'get_current_blog_id', [
				'return' => 925,
			]
		);

		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'charset':
							return 'UTF-8';
						case 'name':
							return 'Example Dot Org';
						default:
							return '';
					}
				},
			)
		);

		\WP_Mock::userFunction( 'do_action_deprecated' );
		\WP_Mock::userFunction( 'get_current_user_id' );
		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'add_filter' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_the_title' );
		\WP_Mock::userFunction( 'remove_filter' );
		\WP_Mock::userFunction( 'get_option' );
		\WP_Mock::passthruFunction( 'wp_slash' );
		\WP_Mock::passthruFunction( 'absint' );

		$this->connection_obj->site->blog_id = 2;

		$original_url = 'original url';
		$new_post_id  = 123;

		\WP_Mock::userFunction(
			'use_block_editor_for_post_type', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_insert_post', [
				'times'  => 1,
				'args'   => [
					[
						'post_title'     => '',
						'post_name'      => '',
						'post_type'      => '',
						'post_content'   => '',
						'post_excerpt'   => '',
						'post_status'    => 'publish',
						'post_author'    => null,
						'meta_input'     => [
							'dt_original_post_id'   => 111,
							'dt_original_post_url'  => $original_url,
						],
					],
				],
				'return' => $new_post_id,
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
			'get_permalink', [
				'return' => $original_url,
			]
		);

		\WP_Mock::userFunction(
			'get_transient', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'return' => false
			)
		);
		\WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'return' => false
			)
		);

		/**
		 * We will test the util prepare/set functions later
		 */
		\WP_Mock::userFunction(
			'get_attached_media',
			array(
				'return' => array(),
			)
		);
		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);

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

		$this->assertIsArray( $this->connection_obj->push( 1 ) );

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
		$this->setup_post_meta_mock( array(
			'dt_connection_map' => array( array() )
		) );
		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'charset':
							return 'UTF-8';
						case 'name':
							return 'Test Internal Origin';
						default:
							return '';
					}
				},
			)
		);

		$this->connection_obj->site->blog_id = 2;

		$original_url = 'original url';

		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );
		\WP_Mock::userFunction( 'get_current_blog_id' );
		\WP_Mock::userFunction( 'remove_filter' );
		\WP_Mock::passthruFunction( 'wp_slash' );

		\WP_Mock::userFunction(
			'get_post', [
				'return' => (object) [
					'ID'        => 111,
					'post_title' => 'My post title',
					'post_name' => 'my-post-title',
					'post_type' => 'post',
					'post_status' => 'publish',
					'post_content' => 'My post content',
					'post_excerpt' => 'My post excerpt',
					'meta'      => [],
				],
			]
		);
		\WP_Mock::userFunction(
			'get_the_title', [
				'return' => 'My post title',
			]
		);
		\WP_Mock::userFunction(
			'has_blocks',
			array(
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'get_attached_media',
			array(
				'return' => array(),
			)
		);
		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'get_current_user_id', [
				'return' => 1,
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
