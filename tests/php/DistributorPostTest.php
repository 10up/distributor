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
	 * Test the DistributorPost object for internal connections.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_internal_connection() {
		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function( $post_id, $key, $single ) {
					switch ( $key ) {
						case 'dt_original_post_id':
							return '10';
						case 'dt_unlinked':
							return '0';
						case 'dt_original_post_url':
							return 'http://origin.example.org/?p=1';
						case 'dt_original_blog_id':
							return '2';
						default:
							return '';
					}
				},
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
				'return' => 'http://origin.example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'http://origin.example.org/',
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( '10', $dt_post->original_post_id );
		$this->assertSame( true, $dt_post->is_linked );
		$this->assertSame( 'http://origin.example.org/?p=1', $dt_post->original_post_url );
		$this->assertSame( '2', $dt_post->connection_id );
		$this->assertSame( 'bidirectional', $dt_post->connection_direction );
		$this->assertSame( 'internal', $dt_post->connection_type );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'] );
		$this->assertSame( 'Test Internal Origin', $dt_post->source_site['name'] );
		$this->assertSame( false, $dt_post->is_source );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pushed_post() {
		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function( $post_id, $key, $single ) {
					switch ( $key ) {
						case 'dt_original_post_id':
							return '10';
						case 'dt_unlinked':
							return '0';
						case 'dt_original_post_url':
							return 'http://origin.example.org/?p=1';
						case 'dt_original_site_url':
							return 'http://origin.example.org/';
						case 'dt_full_connection':
							return '1';
						case 'dt_original_site_name':
							return 'Test External, Pushed Origin';
						default:
							return '';
					}
				},
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( '10', $dt_post->original_post_id );
		$this->assertSame( true, $dt_post->is_linked );
		$this->assertSame( 'http://origin.example.org/?p=1', $dt_post->original_post_url );
		$this->assertSame( 'http://origin.example.org/', $dt_post->connection_id );
		$this->assertSame( 'pushed', $dt_post->connection_direction );
		$this->assertSame( 'external', $dt_post->connection_type );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'] );
		$this->assertSame( 'Test External, Pushed Origin', $dt_post->source_site['name'] );
		$this->assertSame( false, $dt_post->is_source );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @group Post
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pulled_post() {
		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => function( $post_id, $key, $single ) {
					switch ( $key ) {
						case 'dt_original_post_id':
							return '10';
						case 'dt_unlinked':
							return '0';
						case 'dt_original_post_url':
							return 'http://origin.example.org/?p=1';
						case 'dt_original_site_url':
							return 'http://origin.example.org/';
						case 'dt_full_connection':
							return '';
						case 'dt_original_site_name':
							return 'Test External, Pulled Origin';
						case 'dt_original_source_id':
							return 3;
						default:
							return '';
					}
				},
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( '10', $dt_post->original_post_id );
		$this->assertSame( true, $dt_post->is_linked );
		$this->assertSame( 'http://origin.example.org/?p=1', $dt_post->original_post_url );
		$this->assertSame( 3, $dt_post->connection_id );
		$this->assertSame( 'pulled', $dt_post->connection_direction );
		$this->assertSame( 'external', $dt_post->connection_type );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'] );
		$this->assertSame( 'Test External, Pulled Origin', $dt_post->source_site['name'] );
		$this->assertSame( false, $dt_post->is_source );
	}
}
