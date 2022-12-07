<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;

class DistributorPostTest extends TestCase {

	/**
	 * Set up with WP_Mock
	 *
	 * Set up common mocks required for multiple tests.
	 *
	 * @since x.x.x
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_common();
	}

	public function setup_common() {
		\WP_Mock::userFunction(
			'get_post',
			array(
				'return' => (object) array(
					'ID' => 1,
					'post_title' => 'Test Post',
					'post_content' => 'Test Content',
					'post_excerpt' => 'Test Excerpt',
					'post_status' => 'publish',
					'post_type' => 'post',
					'post_author' => 1,
					'post_date' => '2020-01-01 00:00:00',
					'post_date_gmt' => '2020-01-01 00:00:00',
					'post_modified' => '2020-01-01 00:00:00',
					'post_modified_gmt' => '2020-01-01 00:00:00',
					'post_parent' => 0,
					'post_mime_type' => '',
					'comment_count' => 0,
					'comment_status' => 'open',
					'ping_status' => 'open',
					'guid' => 'http://example.org/?p=1',
					'menu_order' => 0,
					'pinged' => '',
					'to_ping' => '',
					'post_password' => '',
					'post_name' => 'test-post',
					'post_content_filtered' => '',
				),
			)
		);
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
	 * Test the DistributorPost object for internal connections.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_internal_connection() {
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
			  )
			);

		\WP_Mock::userFunction(
			'get_current_blog_id',
			array(
				'return' => 1,
			)
		);

		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );

		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'name':
							return 'Test Internal Origin';
						default:
							return '';
					}
				},
			)
		);

		// Generic values for the origin site.
		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://origin.example.org/?p=10',
			)
		);

		\WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'http://origin.example.org/',
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 2, $dt_post->connection_id, 'Origin site ID does not match expected value.' );
		$this->assertSame( 'bidirectional', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'internal', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test Internal Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pushed_post() {
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pushed Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '1' ),
				'dt_original_source_id'     => array( '2' ),
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->connection_id, 'Origin connection ID does not match source URL.' );
		$this->assertSame( 'pushed', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pushed Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pulled_post() {
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 3, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( 'pulled', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pulled Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object a source post.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_source_post() {
		// There is no post meta to mock for a source post.
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'http://example.org/',
			)
		);

		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'name':
							return 'Example Dot Org';
						default:
							return '';
					}
				},
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 1, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 0, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Example Dot Org', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( true, $dt_post->is_source, 'Post is incorrectly marked as distributed.' );
	}
}
