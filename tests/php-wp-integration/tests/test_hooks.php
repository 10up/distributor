<?php
/**
 * Hooks tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\Hooks;

/**
 * Integration tests for the default Distributor hooks.
 *
 * @group Hooks
 */
class Test_Hooks extends WP_UnitTestCase {
	/**
	 * Origin sub-site ID for internal connections.
	 *
	 * @var int
	 */
	public static $origin_blog_id = 0;

	/**
	 * Post ID on the origin sub-site that was distributed internally.
	 *
	 * @var int
	 */
	public static $origin_post_id = 0;

	/**
	 * Main site post IDs, keyed by the type of post.
	 *
	 * @var int[]
	 */
	public static $post_ids = array();

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$origin_blog_id = $factory->blog->create( array( 'title' => 'Test Internal Origin' ) );

		switch_to_blog( self::$origin_blog_id );
		self::$origin_post_id = $factory->post->create( array( 'post_title' => 'Internal origin post' ) );
		$origin_post_url      = get_permalink( self::$origin_post_id );
		restore_current_blog();

		$external_connection_id = $factory->post->create(
			array(
				'post_type'  => 'dt_ext_connection',
				'post_title' => 'Test External, Pulled Origin',
			)
		);

		self::$post_ids['source'] = $factory->post->create( array( 'post_title' => 'Source post' ) );

		self::$post_ids['external_pushed'] = $factory->post->create(
			array(
				'post_title' => 'Externally pushed post',
				'meta_input' => array(
					'dt_original_post_id'       => 10,
					'dt_original_site_name'     => 'Test External, Pushed Origin',
					'dt_original_site_url'      => 'http://origin.example.org/',
					'dt_original_post_url'      => 'http://origin.example.org/?p=10',
					'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
					'dt_syndicate_time'         => 1670384223,
					'dt_full_connection'        => 1,
					'dt_original_source_id'     => 2,
				),
			)
		);

		self::$post_ids['external_pulled'] = $factory->post->create(
			array(
				'post_title' => 'Externally pulled post',
				'meta_input' => array(
					'dt_original_post_id'       => 11,
					'dt_original_site_name'     => 'Test External, Pulled Origin',
					'dt_original_site_url'      => 'http://origin.example.org/',
					'dt_original_post_url'      => 'http://origin.example.org/?p=11',
					'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
					'dt_syndicate_time'         => 1670384223,
					'dt_full_connection'        => '',
					'dt_original_source_id'     => $external_connection_id,
				),
			)
		);

		self::$post_ids['internal'] = $factory->post->create(
			array(
				'post_title' => 'Internally distributed post',
				'meta_input' => array(
					'dt_original_post_id'  => self::$origin_post_id,
					'dt_original_blog_id'  => self::$origin_blog_id,
					'dt_syndicate_time'    => 1670383190,
					'dt_original_post_url' => $origin_post_url,
				),
			)
		);
	}

	/**
	 * Tear down shared fixtures.
	 *
	 * Sites are not removed by the test suite's data cleanup, so delete the
	 * sub-site to avoid it leaking into later test classes.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_site( self::$origin_blog_id );
	}

	/**
	 * Data provider for the types of post.
	 *
	 * @return array[] Data provider.
	 */
	public function data_post_types() {
		return array(
			'source'          => array( 'source' ),
			'external pushed' => array( 'external_pushed' ),
			'external pulled' => array( 'external_pulled' ),
			'internal'        => array( 'internal' ),
		);
	}

	/**
	 * Get the expected original post URL for a type of post.
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 * @param string $source_value  Value expected for the source post.
	 * @return string Expected value.
	 */
	protected function expected_original_post_url( $post_type_key, $source_value ) {
		if ( 'source' === $post_type_key ) {
			return $source_value;
		}

		return get_post_meta( self::$post_ids[ $post_type_key ], 'dt_original_post_url', true );
	}

	/**
	 * Get the expected author name for a type of post.
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 * @param string $source_value  Value expected for the source post.
	 * @return string Expected value.
	 */
	protected function expected_author_name( $post_type_key, $source_value ) {
		switch ( $post_type_key ) {
			case 'source':
				return $source_value;
			case 'internal':
				return 'Test Internal Origin';
			default:
				return get_post_meta( self::$post_ids[ $post_type_key ], 'dt_original_site_name', true );
		}
	}

	/**
	 * Test get_canonical_url.
	 *
	 * @dataProvider data_post_types
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 */
	public function test_get_canonical_url( $post_type_key ) {
		$post_id   = self::$post_ids[ $post_type_key ];
		$permalink = get_permalink( $post_id );

		$actual = Hooks\get_canonical_url( $permalink, get_post( $post_id ) );
		$this->assertSame( $this->expected_original_post_url( $post_type_key, $permalink ), $actual );
	}

	/**
	 * Test wpseo_canonical in the legacy format.
	 *
	 * @dataProvider data_post_types
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 */
	public function test_wpseo_canonical( $post_type_key ) {
		$post_id   = self::$post_ids[ $post_type_key ];
		$permalink = get_permalink( $post_id );
		$this->go_to( $permalink );

		$actual = Hooks\wpseo_canonical( $permalink );
		$this->assertSame( $this->expected_original_post_url( $post_type_key, $permalink ), $actual );
	}

	/**
	 * Test wpseo_opengraph_url in the legacy format.
	 *
	 * The OpenGraph URL is always the local permalink.
	 *
	 * @dataProvider data_post_types
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 */
	public function test_wpseo_opengraph_url( $post_type_key ) {
		$post_id   = self::$post_ids[ $post_type_key ];
		$permalink = get_permalink( $post_id );
		$this->go_to( $permalink );

		$actual = Hooks\wpseo_opengraph_url( $permalink );
		$this->assertSame( $permalink, $actual );
	}

	/**
	 * Test filter_the_author.
	 *
	 * @dataProvider data_post_types
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 */
	public function test_filter_the_author( $post_type_key ) {
		$this->go_to( get_permalink( self::$post_ids[ $post_type_key ] ) );

		$actual = Hooks\filter_the_author( 'Alexander Hamilton' );
		$this->assertSame( $this->expected_author_name( $post_type_key, 'Alexander Hamilton' ), $actual );
	}

	/**
	 * Test get_the_author_display_name for the current post's author.
	 *
	 * @dataProvider data_post_types
	 *
	 * @param string $post_type_key Key of the post in self::$post_ids.
	 */
	public function test_get_the_author_display_name( $post_type_key ) {
		$this->go_to( get_permalink( self::$post_ids[ $post_type_key ] ) );

		$actual = Hooks\get_the_author_display_name( 'Alexander Hamilton', 1, false );
		$this->assertSame( $this->expected_author_name( $post_type_key, 'Alexander Hamilton' ), $actual );
	}

	/**
	 * Test get_the_author_display_name for a specific author of a source post.
	 */
	public function test_get_the_author_display_name_specific_author_source() {
		$this->go_to( get_permalink( self::$post_ids['source'] ) );

		$actual = Hooks\get_the_author_display_name( 'Alexander Hamilton', 1, 1 );
		$this->assertSame( 'Alexander Hamilton', $actual, 'Unexpected value when getting specific author.' );
	}

	/**
	 * Test get_the_author_display_name without a global post.
	 */
	public function test_get_the_author_display_name_no_post() {
		$this->assertNull( get_post(), 'There should be no global post.' );

		$actual = Hooks\get_the_author_display_name( 'George Washington', 1, false );
		$this->assertSame( 'George Washington', $actual, 'Unexpected value when getting current post author.' );

		$actual = Hooks\get_the_author_display_name( 'George Washington', 1, 1 );
		$this->assertSame( 'George Washington', $actual, 'Unexpected value when getting specific author.' );
	}
}
