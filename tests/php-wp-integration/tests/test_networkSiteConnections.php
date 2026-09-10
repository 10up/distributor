<?php
/**
 * Network Site Connections tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\InternalConnections\NetworkSiteConnection;

/**
 * Integration tests for Network Site Connections.
 */
class test_NetworkSiteConnections extends WP_UnitTestCase {
	/**
	 * Shared sub-site ID for the tests.
	 *
	 * @var int
	 */
	public static $blog_id = 0;

	/**
	 * Main site's post ID for the tests.
	 *
	 * @var int
	 */
	public static $post_id = 0;

	/**
	 * Connection object to sub site.
	 *
	 * @var NetworkSiteConnection
	 */
	public static $connection_obj;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$blog_id = $factory->blog->create();
		self::$post_id = $factory->post->create(
			array(
				'post_title'    => 'Test suite post title',
				'post_content'  => 'Test suite post content',
				'post_excerpt'  => 'Test suite post excerpt',
				'post_date'     => '2020-01-01 00:00:00',
				'post_date_gmt' => '2020-01-01 00:00:00',
			)
		);

		self::$connection_obj = new NetworkSiteConnection( get_site( self::$blog_id ) );
	}

	/**
	 * Test set up.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( 1 ); // Set user to super admin for all tests.
	}

	/**
	 * Test pushing to an internal site.
	 */
	public function test_push() {
		$now         = time();
		$push_result = self::$connection_obj->push( self::$post_id );
		$this->assertIsArray( $push_result, 'NetworkSiteConnection::push() should return an array' );

		$source_permalink = get_permalink( self::$post_id );

		switch_to_blog( self::$blog_id );
		$pushed_post_id = $push_result['id'];

		$this->assertSame( self::$post_id, (int) get_post_meta( $pushed_post_id, 'dt_original_post_id', true ), 'Original post ID should be stored in post meta.' );
		$this->assertSame( 1, (int) get_post_meta( $pushed_post_id, 'dt_original_blog_id', true ), 'Original blog ID should be stored in post meta.' );
		$this->assertSame( $source_permalink, get_post_meta( $pushed_post_id, 'dt_original_post_url', true ), 'Original post permalink should be stored in post meta.' );
		$this->assertGreaterThanOrEqual( $now, (int) get_post_meta( $pushed_post_id, 'dt_syndicate_time', true ), 'Syndication time should be stored in post meta.' );
	}

	/**
	 * Test pulling from an internal site.
	 */
	public function test_pull() {
		$now              = time();
		$source_permalink = get_permalink( self::$post_id );
		switch_to_blog( self::$blog_id );
		$connection_obj = new NetworkSiteConnection( get_site( 1 ) );

		$pull_result = $connection_obj->pull( array( array( 'remote_post_id' => self::$post_id ) ) );
		$this->assertIsArray( $pull_result, 'NetworkSiteConnection::pull() should return an array' );

		$pulled_post_id = $pull_result[0];

		$this->assertSame( self::$post_id, (int) get_post_meta( $pulled_post_id, 'dt_original_post_id', true ), 'Original post ID should be stored in post meta.' );
		$this->assertSame( 1, (int) get_post_meta( $pulled_post_id, 'dt_original_blog_id', true ), 'Original blog ID should be stored in post meta.' );
		$this->assertSame( $source_permalink, get_post_meta( $pulled_post_id, 'dt_original_post_url', true ), 'Original post permalink should be stored in post meta.' );
		$this->assertGreaterThanOrEqual( $now, (int) get_post_meta( $pulled_post_id, 'dt_syndicate_time', true ), 'Syndication time should be stored in post meta.' );
	}
}
