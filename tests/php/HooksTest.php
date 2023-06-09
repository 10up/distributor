<?php

namespace Distributor\Tests;

use Distributor\Hooks;

class HooksTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Set up with WP_Mock
	 *
	 * Set up common mocks required for multiple tests.
	 *
	 * @since x.x.x
	 */
	public function setUp(): void {
		parent::setUp();

		\WP_Mock::userFunction(
			'get_current_blog_id',
			[
				'return' => 1,
			]
		);

		// Return voids.
		\WP_Mock::userFunction( '_prime_post_caches' );
		\WP_Mock::userFunction( 'update_object_term_cache' );
		\WP_Mock::userFunction( 'update_postmeta_cache' );
	}

	/**
	 * Helper function to mock get_post.
	 */
	public function setup_post_mock( $post_overrides = array() ) {
		$defaults = array(
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
		);

		$post = array_merge( $defaults, $post_overrides );


		\WP_Mock::userFunction(
			'get_post',
			array(
				'return' => (object) $post,
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
	 * Test get_canonical_url
	 *
	 * @since x.x.x
	 */
	public function test_get_canonical_url_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(array());

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\get_canonical_url( 'https://example.com/?p=1', (object) array( 'ID' => 1 ) );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test get_canonical_url
	 *
	 * @since x.x.x
	 */
	public function test_get_canonical_url_external_pushed() {
		$this->setup_post_mock();
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

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\get_canonical_url( 'https://example.com/?p=1', (object) array( 'ID' => 1 ) );
		$this->assertSame( 'http://origin.example.org/?p=10', $actual );
	}

	/**
	 * Test get_canonical_url
	 *
	 * @since x.x.x
	 */
	public function test_get_canonical_url_external_pulled() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=11' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\get_canonical_url( 'https://example.com/?p=1', (object) array( 'ID' => 1 ) );
		$this->assertSame( 'http://origin.example.org/?p=11', $actual );
	}

	/**
	 * Test get_canonical_url
	 *
	 * @since x.x.x
	 */
	public function test_get_canonical_url_internal() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=12' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\get_canonical_url( 'https://example.com/?p=1', (object) array( 'ID' => 1 ) );
		$this->assertSame( 'http://origin.example.org/?p=12', $actual );
	}

	/**
	 * Test wpseo_canonical
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_canonical_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(array());

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_canonical( 'https://example.com/?p=1' );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test wpseo_canonical
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_canonical_external_pushed() {
		$this->setup_post_mock();
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

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_canonical( 'https://example.com/?p=1' );
		$this->assertSame( 'http://origin.example.org/?p=10', $actual );
	}

	/**
	 * Test wpseo_canonical
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_canonical_external_pulled() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=11' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_canonical( 'https://example.com/?p=1' );
		$this->assertSame( 'http://origin.example.org/?p=11', $actual );
	}

	/**
	 * Test wpseo_canonical
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_canonical_internal() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=12' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_canonical( 'https://example.com/?p=1' );
		$this->assertSame( 'http://origin.example.org/?p=12', $actual );
	}

	/**
	 * Test wpseo_opengraph_url
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_opengraph_url_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(array());

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_opengraph_url( 'https://example.com/?p=1' );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test wpseo_opengraph_url
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_opengraph_url_external_pushed() {
		$this->setup_post_mock();
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

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_opengraph_url( 'https://example.com/?p=1' );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test wpseo_opengraph_url
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_opengraph_url_external_pulled() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=11' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_opengraph_url( 'https://example.com/?p=1' );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test wpseo_opengraph_url
	 *
	 * @since x.x.x
	 */
	public function test_wpseo_opengraph_url_internal() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=12' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		$actual = Hooks\wpseo_opengraph_url( 'https://example.com/?p=1' );
		$this->assertSame( 'https://example.com/?p=1', $actual );
	}

	/**
	 * Test filter_the_author
	 *
	 * @since x.x.x
	 */
	public function test_filter_the_author_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(array());

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\filter_the_author( 'Alexander Hamilton' );
		$this->assertSame( 'Alexander Hamilton', $actual );
	}

	/**
	 * Test filter_the_author
	 *
	 * @since x.x.x
	 */
	public function test_filter_the_author_external_pushed() {
		$this->setup_post_mock();
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

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\filter_the_author( 'George Washington' );
		$this->assertSame( 'Test External, Pushed Origin', $actual );
	}

	/**
	 * Test filter_the_author
	 *
	 * @since x.x.x
	 */
	public function test_filter_the_author_external_pulled() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=11' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\filter_the_author( 'James Madison' );
		$this->assertSame( 'Test External, Pulled Origin', $actual );
	}

	/**
	 * Test filter_the_author
	 *
	 * @since x.x.x
	 */
	public function test_filter_the_author_internal() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=12' ),
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
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

		$actual = Hooks\filter_the_author( 'Aaron Burr' );
		$this->assertSame( 'Test Internal Origin', $actual );
	}

	/**
	 * Test get_the_author_display_name
	 *
	 * @since x.x.x
	 */
	public function test_get_the_author_display_name_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(array());

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\get_the_author_display_name( 'Alexander Hamilton', 1, false );
		$this->assertSame( 'Alexander Hamilton', $actual, 'Unexpected value when 1 current post author.' );

		$actual = Hooks\get_the_author_display_name( 'Alexander Hamilton', 1, 1 );
		$this->assertSame( 'Alexander Hamilton', $actual, 'Unexpected value when getting specific author.' );
	}

	/**
	 * Test get_the_author_display_name
	 *
	 * @since x.x.x
	 */
	public function test_get_the_author_display_name_no_post() {
		$actual = Hooks\get_the_author_display_name( 'George Washington', 1, false );
		$this->assertSame( 'George Washington', $actual, 'Unexpected value when 1 current post author.' );

		$actual = Hooks\get_the_author_display_name( 'George Washington', 1, 1 );
		$this->assertSame( 'George Washington', $actual, 'Unexpected value when getting specific author.' );
	}

	/**
	 * Test get_the_author_display_name
	 *
	 * @since x.x.x
	 */
	public function test_get_the_author_display_name_external_pushed() {
		$this->setup_post_mock();
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

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\get_the_author_display_name( 'George Washington', 1, false );
		$this->assertSame( 'Test External, Pushed Origin', $actual, 'Unexpected value when getting current post author.' );
	}

	/**
	 * Test get_the_author_display_name
	 *
	 * @since x.x.x
	 */
	public function test_get_the_author_display_name_external_pulled() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=11' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'https://example.com/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$actual = Hooks\get_the_author_display_name( 'James Madison', 1, false );
		$this->assertSame( 'Test External, Pulled Origin', $actual, 'Unexpected value when getting current post author.' );
	}

	/**
	 * Test get_the_author_display_name
	 *
	 * @since x.x.x
	 */
	public function test_get_the_author_display_name_internal() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=12' ),
			)
		);

		\WP_Mock::userFunction(
			'is_singular',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
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

		$actual = Hooks\get_the_author_display_name( 'Aaron Burr', 1, false );
		$this->assertSame( 'Test Internal Origin', $actual, 'Unexpected value when getting current post author.' );
	}
}
