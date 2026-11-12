<?php
/**
 * Subscriptions tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_REST_Response;
use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\Subscriptions;
use Distributor\ExternalConnections\WordPressExternalConnection;

/**
 * Integration tests for subscriptions.
 *
 * A network site plays the part of the remote site, with HTTP requests
 * bridged to its REST API.
 *
 * @group Subscriptions
 */
class Test_Subscriptions extends WP_UnitTestCase {
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
	 * Connection to the remote site.
	 *
	 * @var WordPressExternalConnection
	 */
	protected $connection;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$remote_blog_id = $factory->blog->create( array( 'title' => 'Remote site' ) );

		self::$remote_user = $factory->user->create_and_get( array( 'role' => 'administrator' ) );
		add_user_to_blog( self::$remote_blog_id, self::$remote_user->ID, 'administrator' );
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

		$password         = $this->create_application_password( self::$remote_user->ID );
		$this->connection = $this->create_bridged_connection( self::$remote_blog_id, self::$remote_user->user_login, $password );
	}

	/**
	 * Create a post on the main site.
	 *
	 * @return int Post ID.
	 */
	protected function create_local_post() {
		return self::factory()->post->create(
			array(
				'post_title'   => 'Source post title',
				'post_content' => 'Source post content',
			)
		);
	}

	/**
	 * Create a post on the remote site.
	 *
	 * @return int Remote post ID.
	 */
	protected function create_remote_post() {
		switch_to_blog( self::$remote_blog_id );
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'Remote post title',
				'post_content' => 'Remote post content',
				'post_author'  => self::$remote_user->ID,
			)
		);
		restore_current_blog();

		return $post_id;
	}

	/**
	 * Get a post from the remote site.
	 *
	 * @param int $post_id Remote post ID.
	 * @return \WP_Post|null Remote post.
	 */
	protected function get_remote_post( $post_id ) {
		switch_to_blog( self::$remote_blog_id );
		$post = get_post( $post_id );
		restore_current_blog();

		return $post;
	}

	/**
	 * Get post meta from the remote site.
	 *
	 * @param int    $post_id Remote post ID.
	 * @param string $key     Meta key.
	 * @return mixed Meta value.
	 */
	protected function get_remote_post_meta( $post_id, $key ) {
		switch_to_blog( self::$remote_blog_id );
		$value = get_post_meta( $post_id, $key, true );
		restore_current_blog();

		return $value;
	}

	/**
	 * Push a local post to the remote site, subscribing the remote copy to it.
	 *
	 * @param int $post_id Local post ID.
	 * @return int Remote post ID.
	 */
	protected function push_to_remote( $post_id ) {
		$result = $this->connection->push( $post_id );
		$this->assertIsArray( $result, 'Push should succeed.' );
		$this->assertCount( 1, get_post_meta( $post_id, 'dt_subscriptions', true ), 'Local post should have a subscription.' );

		return $result['id'];
	}

	/**
	 * Pull a remote post and subscribe the local copy to it, as the pull screen does.
	 *
	 * @param int $remote_post_id Remote post ID.
	 * @return int Local post ID.
	 */
	protected function pull_from_remote( $remote_post_id ) {
		$result = $this->connection->pull(
			array(
				array(
					'remote_post_id' => $remote_post_id,
					'post_type'      => 'post',
				),
			)
		);
		$this->assertIsInt( $result[0] ?? null, 'Pull should succeed.' );

		Subscriptions\create_remote_subscription( $this->connection, $remote_post_id, $result[0] );

		return $result[0];
	}

	/**
	 * Test creating a local subscription for a post a remote post subscribes to.
	 */
	public function test_create_subscription() {
		$post_id = $this->create_local_post();
		update_post_meta( $post_id, 'dt_subscriptions', array( 'sig' => 5 ) );

		$subscription_id = Subscriptions\create_subscription( $post_id, 2, 'http://test.com', '12345' );

		$subscription = get_post( $subscription_id );
		$this->assertSame( 'dt_subscription', $subscription->post_type, 'Subscription post should be created.' );
		$this->assertSame( $post_id, $subscription->post_parent, 'Subscription should be a child of the post.' );
		$this->assertSame( (string) $post_id, get_post_meta( $subscription_id, 'dt_subscription_post_id', true ), 'Subscription should record the post ID.' );
		$this->assertSame( '12345', get_post_meta( $subscription_id, 'dt_subscription_signature', true ), 'Subscription should record the signature.' );
		$this->assertSame( '2', get_post_meta( $subscription_id, 'dt_subscription_remote_post_id', true ), 'Subscription should record the remote post ID.' );
		$this->assertSame( 'http://test.com', get_post_meta( $subscription_id, 'dt_subscription_target_url', true ), 'Subscription should record the target URL.' );

		$this->assertSame(
			array(
				'sig'          => 5,
				md5( '12345' ) => $subscription_id,
			),
			get_post_meta( $post_id, 'dt_subscriptions', true ),
			'Subscription should be added to the post, keyed by the signature hash.'
		);
	}

	/**
	 * Test deleting a local subscription given a signature.
	 */
	public function test_delete_subscription_local() {
		$post_id         = $this->create_local_post();
		$kept_id         = Subscriptions\create_subscription( $post_id, 3, 'http://test.com', 'kept' );
		$subscription_id = Subscriptions\create_subscription( $post_id, 2, 'http://test.com', 'signature' );

		Subscriptions\delete_subscription( $post_id, 'signature' );

		$this->assertNull( get_post( $subscription_id ), 'Subscription post should be deleted.' );
		$this->assertSame( array( md5( 'kept' ) => $kept_id ), get_post_meta( $post_id, 'dt_subscriptions', true ), 'Only the matching subscription should be removed.' );
	}

	/**
	 * Test creating a subscription on the remote site for a pulled post.
	 */
	public function test_create_remote_subscription() {
		$remote_post_id = $this->create_remote_post();
		$local_post_id  = $this->pull_from_remote( $remote_post_id );

		$signature = get_post_meta( $local_post_id, 'dt_subscription_signature', true );
		$this->assertNotEmpty( $signature, 'Pulled post should store the subscription signature.' );

		$remote_subscriptions = $this->get_remote_post_meta( $remote_post_id, 'dt_subscriptions' );
		$this->assertIsArray( $remote_subscriptions, 'Remote post should have subscriptions.' );
		$this->assertArrayHasKey( md5( $signature ), $remote_subscriptions, 'Remote subscription should be keyed by the signature hash.' );

		$subscription_id = $remote_subscriptions[ md5( $signature ) ];
		$this->assertSame( (string) $local_post_id, $this->get_remote_post_meta( $subscription_id, 'dt_subscription_remote_post_id' ), 'Remote subscription should map to the pulled post.' );
		$this->assertSame( untrailingslashit( home_url() ) . '/wp-json', $this->get_remote_post_meta( $subscription_id, 'dt_subscription_target_url' ), 'Remote subscription should target this site.' );
	}

	/**
	 * Test deleting a post subscribed to by a remote post notifies the remote post.
	 */
	public function test_delete_subscribed_post() {
		$post_id         = $this->create_local_post();
		$remote_post_id  = $this->push_to_remote( $post_id );
		$subscription_id = current( get_post_meta( $post_id, 'dt_subscriptions', true ) );

		wp_delete_post( $post_id, true );

		$this->assertNull( get_post( $subscription_id ), 'Local subscription should be deleted.' );
		$this->assertSame( '1', $this->get_remote_post_meta( $remote_post_id, 'dt_original_post_deleted' ), 'Remote post should be told the original was deleted.' );
	}

	/**
	 * Test deleting a post subscribing to a remote post deletes the remote subscription.
	 */
	public function test_delete_subscribing_post() {
		$remote_post_id = $this->create_remote_post();
		$local_post_id  = $this->pull_from_remote( $remote_post_id );

		$remote_subscriptions = $this->get_remote_post_meta( $remote_post_id, 'dt_subscriptions' );
		$subscription_id      = current( $remote_subscriptions );
		$this->assertNotNull( $this->get_remote_post( $subscription_id ), 'Remote subscription should exist.' );

		wp_delete_post( $local_post_id, true );

		$this->assertNull( $this->get_remote_post( $subscription_id ), 'Remote subscription should be deleted.' );
		$this->assertSame( array(), $this->get_remote_post_meta( $remote_post_id, 'dt_subscriptions' ), 'Subscription should be removed from the remote post.' );
	}

	/**
	 * Test no notifications are sent for a post without subscriptions.
	 */
	public function test_send_notifications_none() {
		$post_id = $this->create_local_post();

		Subscriptions\send_notifications( $post_id );
		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => 'Updated title',
			)
		);

		$this->assertSame( array(), $this->bridged_requests, 'No requests should be made.' );
	}

	/**
	 * Test updating a post sends the update to subscribed remote posts.
	 */
	public function test_send_notifications_remote_post_exists() {
		$post_id         = $this->create_local_post();
		$remote_post_id  = $this->push_to_remote( $post_id );
		$subscriptions   = get_post_meta( $post_id, 'dt_subscriptions', true );
		$subscription_id = current( $subscriptions );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_title'   => 'Updated title',
				'post_content' => 'Updated content',
			)
		);

		$remote_post = $this->get_remote_post( $remote_post_id );
		$this->assertSame( 'Updated title', $remote_post->post_title, 'Remote post title should be updated.' );
		$this->assertSame( 'Updated content', $remote_post->post_content, 'Remote post content should be updated.' );

		$this->assertSame( $subscriptions, get_post_meta( $post_id, 'dt_subscriptions', true ), 'Subscription should be kept.' );
		$this->assertNotNull( get_post( $subscription_id ), 'Subscription post should be kept.' );
	}

	/**
	 * Test a subscription is deleted when the remote site reports the remote post was deleted.
	 *
	 * The remote response is faked: a request for a deleted post fails the
	 * receive endpoint's signature check, as the signature is deleted with the
	 * post, so the endpoint does not currently report the deletion.
	 */
	public function test_send_notifications_remote_post_deleted() {
		$post_id = $this->create_local_post();
		$this->push_to_remote( $post_id );
		$subscription_id = current( get_post_meta( $post_id, 'dt_subscriptions', true ) );

		add_filter(
			'rest_pre_dispatch',
			static function ( $result, $server, $request ) {
				if ( '/wp/v2/dt_subscription/receive' !== $request->get_route() ) {
					return $result;
				}

				return new WP_REST_Response( null, 404, array( 'X-Distributor-Post-Deleted' => 'yes' ) );
			},
			10,
			3
		);

		wp_update_post(
			array(
				'ID'         => $post_id,
				'post_title' => 'Updated title',
			)
		);

		$this->assertNull( get_post( $subscription_id ), 'Subscription post should be deleted.' );
		$this->assertSame( array(), get_post_meta( $post_id, 'dt_subscriptions', true ), 'Subscription should be removed from the post.' );
	}
}
