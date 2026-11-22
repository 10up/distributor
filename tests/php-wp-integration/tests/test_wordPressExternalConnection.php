<?php
/**
 * WordPress external connection tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_Post;
use WP_REST_Response;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\ExternalConnection;
use Distributor\Authentications\WordPressBasicAuth;
use Distributor\ExternalConnections\WordPressExternalConnection;

/**
 * Integration tests for WordPressExternalConnection.
 *
 * A network site plays the part of the remote site, with HTTP requests
 * bridged to its REST API.
 *
 * @group WordPressExternalConnection
 */
class Test_WordPressExternalConnection extends WP_UnitTestCase {
	use REST_Bridge;

	/**
	 * Remote site ID.
	 *
	 * @var int
	 */
	public static $remote_blog_id = 0;

	/**
	 * User the connection authenticates as on the remote site.
	 *
	 * @var \WP_User
	 */
	public static $remote_user;

	/**
	 * Application password for the remote user.
	 *
	 * @var string
	 */
	public static $remote_password = '';

	/**
	 * Source post ID on the main site.
	 *
	 * @var int
	 */
	public static $post_id = 0;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$remote_blog_id = $factory->blog->create( array( 'title' => 'Remote site' ) );

		self::$remote_user = $factory->user->create_and_get( array( 'role' => 'administrator' ) );
		add_user_to_blog( self::$remote_blog_id, self::$remote_user->ID, 'administrator' );

		self::$post_id = $factory->post->create(
			array(
				'post_title'   => 'Source post title',
				'post_content' => 'Source post content',
				'post_excerpt' => 'Source post excerpt',
			)
		);
	}

	/**
	 * Tear down shared fixtures.
	 *
	 * Sites are not removed by the test suite's data cleanup, so delete the
	 * sub-site to avoid its tables leaking into later test runs.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_site( self::$remote_blog_id );
	}

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( 1 );
		$this->start_rest_bridge();

		// User meta is rolled back after each test, so create the password per test.
		self::$remote_password = $this->create_application_password( self::$remote_user->ID );
	}

	/**
	 * Create a connection to the remote site.
	 *
	 * @param string $permalink_structure Permalink structure of the remote site.
	 * @return WordPressExternalConnection Connection.
	 */
	protected function get_connection( $permalink_structure = '' ) {
		update_blog_option( self::$remote_blog_id, 'permalink_structure', $permalink_structure );

		$connection = $this->create_bridged_connection( self::$remote_blog_id, self::$remote_user->user_login, self::$remote_password );
		$this->assertInstanceOf( WordPressExternalConnection::class, $connection, 'Connection should be instantiated.' );

		return $connection;
	}

	/**
	 * Create a post on the remote site.
	 *
	 * @param array $args Post arguments.
	 * @return int Remote post ID.
	 */
	protected function create_remote_post( $args = array() ) {
		switch_to_blog( self::$remote_blog_id );
		$post_id = self::factory()->post->create(
			array_merge(
				array(
					'post_title'   => 'Remote post title',
					'post_content' => 'Remote post content',
					'post_excerpt' => 'Remote post excerpt',
					'post_author'  => self::$remote_user->ID,
				),
				$args
			)
		);
		restore_current_blog();

		return $post_id;
	}

	/**
	 * Data provider for the remote site's permalink structure.
	 *
	 * @return array[] Data provider.
	 */
	public function data_permalink_structures() {
		return array(
			'plain permalinks'  => array( '' ),
			'pretty permalinks' => array( '/%postname%/' ),
		);
	}

	/**
	 * Test creating a WordPressExternalConnection object.
	 */
	public function test_construct() {
		$auth       = new WordPressBasicAuth( array() );
		$connection = new WordPressExternalConnection( 'name', 'url', 1, $auth );

		$this->assertInstanceOf( ExternalConnection::class, $connection );
		$this->assertSame( 'name', $connection->name );
		$this->assertSame( 'url', $connection->base_url );
		$this->assertSame( 1, $connection->id );
		$this->assertSame( $auth, $connection->auth_handler );
	}

	/**
	 * Test pushing a post to the remote site.
	 *
	 * @dataProvider data_permalink_structures
	 *
	 * @param string $permalink_structure Permalink structure of the remote site.
	 */
	public function test_push( $permalink_structure ) {
		$connection = $this->get_connection( $permalink_structure );

		$this->assertWPError( $connection->push( 0 ), 'Pushing without a post should fail.' );

		$result = $connection->push( self::$post_id );

		$this->assertIsArray( $result, 'Push should succeed.' );
		$this->assertArrayNotHasKey( 'push-errors', $result, 'Push should not report errors.' );
		$remote_post_id = $result['id'];

		$source_permalink = get_permalink( self::$post_id );

		// The source post subscribes to the remote post as Distributor is installed there.
		$subscriptions = get_post_meta( self::$post_id, 'dt_subscriptions', true );
		$this->assertCount( 1, $subscriptions, 'Source post should have one subscription.' );
		$subscription_id = reset( $subscriptions );
		$this->assertSame( $remote_post_id, (int) get_post_meta( $subscription_id, 'dt_subscription_remote_post_id', true ), 'Subscription should map to the remote post.' );
		$this->assertSame( untrailingslashit( $connection->base_url ), get_post_meta( $subscription_id, 'dt_subscription_target_url', true ), 'Subscription should target the remote site.' );
		$signature = get_post_meta( $subscription_id, 'dt_subscription_signature', true );

		switch_to_blog( self::$remote_blog_id );
		$remote_post = get_post( $remote_post_id );
		$remote_meta = array(
			'dt_original_post_id'       => (int) get_post_meta( $remote_post_id, 'dt_original_post_id', true ),
			'dt_original_source_id'     => (int) get_post_meta( $remote_post_id, 'dt_original_source_id', true ),
			'dt_original_post_url'      => get_post_meta( $remote_post_id, 'dt_original_post_url', true ),
			'dt_full_connection'        => get_post_meta( $remote_post_id, 'dt_full_connection', true ),
			'dt_subscription_signature' => get_post_meta( $remote_post_id, 'dt_subscription_signature', true ),
		);
		restore_current_blog();

		$this->assertInstanceOf( WP_Post::class, $remote_post, 'Post should exist on the remote site.' );
		$this->assertSame( 'Source post title', $remote_post->post_title, 'Remote post should have the source title.' );
		$this->assertSame( 'publish', $remote_post->post_status, 'Remote post should be published.' );
		$this->assertSame( self::$post_id, $remote_meta['dt_original_post_id'], 'Remote post should record the original post ID.' );
		$this->assertSame( $connection->id, $remote_meta['dt_original_source_id'], 'Remote post should record the connection ID.' );
		$this->assertSame( $source_permalink, $remote_meta['dt_original_post_url'], 'Remote post should record the original URL.' );
		$this->assertSame( '1', $remote_meta['dt_full_connection'], 'Remote post should be marked as a full connection.' );
		$this->assertSame( $signature, $remote_meta['dt_subscription_signature'], 'Remote post should store the subscription signature.' );
	}

	/**
	 * Test pushing a post that has already been pushed updates the remote post.
	 */
	public function test_push_updates_existing_remote_post() {
		$connection     = $this->get_connection();
		$remote_post_id = $connection->push( self::$post_id )['id'];

		wp_update_post(
			array(
				'ID'         => self::$post_id,
				'post_title' => 'Updated source title',
			)
		);

		$result = $connection->push( self::$post_id, array( 'remote_post_id' => $remote_post_id ) );

		$this->assertIsArray( $result, 'Push should succeed.' );
		$this->assertSame( $remote_post_id, $result['id'], 'Existing remote post should be updated.' );

		switch_to_blog( self::$remote_blog_id );
		$remote_title = get_post( $remote_post_id )->post_title;
		restore_current_blog();

		$this->assertSame( 'Updated source title', $remote_title, 'Remote post should have the updated title.' );
	}

	/**
	 * Test pulling a post from the remote site.
	 *
	 * @dataProvider data_permalink_structures
	 *
	 * @param string $permalink_structure Permalink structure of the remote site.
	 */
	public function test_pull( $permalink_structure ) {
		$connection     = $this->get_connection( $permalink_structure );
		$remote_post_id = $this->create_remote_post();

		switch_to_blog( self::$remote_blog_id );
		$remote_permalink = get_permalink( $remote_post_id );
		restore_current_blog();

		$result = $connection->pull(
			array(
				array(
					'remote_post_id' => $remote_post_id,
					'post_type'      => 'post',
				),
			)
		);

		$this->assertIsArray( $result, 'Pull should succeed.' );
		$this->assertCount( 1, $result, 'One post should be pulled.' );

		$pulled_post_id = $result[0];
		$this->assertIsInt( $pulled_post_id, 'Pulled post should be created.' );

		$pulled_post = get_post( $pulled_post_id );
		$this->assertSame( 'Remote post title', $pulled_post->post_title, 'Pulled post should have the remote title.' );
		$this->assertSame( $remote_post_id, (int) get_post_meta( $pulled_post_id, 'dt_original_post_id', true ), 'Pulled post should record the original post ID.' );
		$this->assertSame( $connection->id, (int) get_post_meta( $pulled_post_id, 'dt_original_source_id', true ), 'Pulled post should record the connection ID.' );
		$this->assertSame( $remote_permalink, get_post_meta( $pulled_post_id, 'dt_original_post_url', true ), 'Pulled post should record the original URL.' );
		$this->assertSame( 'Remote site', get_post_meta( $pulled_post_id, 'dt_original_site_name', true ), 'Pulled post should record the original site name.' );
		$this->assertSame( '1', get_post_meta( $pulled_post_id, 'dt_full_connection', true ), 'Pulled post should be marked as a full connection.' );
	}

	/**
	 * Test getting a single post from the remote site.
	 *
	 * @dataProvider data_permalink_structures
	 *
	 * @param string $permalink_structure Permalink structure of the remote site.
	 */
	public function test_remote_get( $permalink_structure ) {
		$connection     = $this->get_connection( $permalink_structure );
		$remote_post_id = $this->create_remote_post();

		$actual = $connection->remote_get(
			array(
				'id'        => $remote_post_id,
				'post_type' => 'post',
			)
		);

		$this->assertInstanceOf( WP_Post::class, $actual, 'Remote post should be returned.' );
		$this->assertSame( $remote_post_id, $actual->ID, 'Remote post ID should match.' );
		$this->assertSame( 'Remote post title', $actual->post_title, 'Remote post title should match.' );
	}

	/**
	 * Test listing posts from the remote site.
	 */
	public function test_remote_get_list() {
		$connection     = $this->get_connection();
		$remote_post_id = $this->create_remote_post();

		$actual = $connection->remote_get( array( 'post_type' => 'post' ) );

		$this->assertIsArray( $actual, 'Remote posts should be returned.' );
		$this->assertContains( $remote_post_id, wp_list_pluck( $actual['items'], 'ID' ), 'Remote post should be listed.' );
		$this->assertEquals( count( $actual['items'] ), $actual['total_items'], 'Total items should be reported.' );
	}

	/**
	 * Test checking a working connection reports no errors.
	 *
	 * @dataProvider data_permalink_structures
	 *
	 * @param string $permalink_structure Permalink structure of the remote site.
	 */
	public function test_check_connections_no_errors( $permalink_structure ) {
		$connection = $this->get_connection( $permalink_structure );

		$check = $connection->check_connections();

		$this->assertSame( array(), $check['errors'], 'Connection should not report errors.' );
		$this->assertFalse( $check['endpoint_suggestion'], 'No alternative endpoint should be suggested.' );
		$this->assertSame( 'yes', $check['is_authenticated'], 'Connection should be authenticated.' );
		$this->assertContains( 'post', $check['can_get'], 'Connection should be able to get posts.' );
		$this->assertContains( 'post', $check['can_post'], 'Connection should be able to post posts.' );
		$this->assertNotContains( 'dt_subscription', $check['can_get'], 'Internal post types should not be listed.' );
	}

	/**
	 * Test checking a connection to a site without Distributor reports so.
	 */
	public function test_check_connections_no_distributor() {
		$connection = $this->get_connection();

		// Remove the header Distributor adds to REST API responses.
		add_filter(
			'rest_post_dispatch',
			static function ( WP_REST_Response $response ) {
				$headers = $response->get_headers();
				unset( $headers['X-Distributor'], $headers['X-Distributor-Version'] );
				$response->set_headers( $headers );
				return $response;
			},
			20
		);

		$check = $connection->check_connections();

		$this->assertArrayHasKey( 'no_distributor', $check['errors'], 'Connection should report Distributor is missing.' );
	}
}
