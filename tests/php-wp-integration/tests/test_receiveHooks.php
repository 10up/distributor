<?php
/**
 * Receive hook tests for network site connections and external relinks.
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\InternalConnections\NetworkSiteConnection;

/**
 * Integration tests for the dt_receive_post_* hooks.
 *
 * For network connections, the main site is the origin and a sub site receives
 * the post. The external relink tests run on the main site only.
 */
class Test_ReceiveHooks extends WP_UnitTestCase {
	/**
	 * Receiving sub site ID.
	 *
	 * @var int
	 */
	public static $blog_id = 0;

	/**
	 * Calls made to each receive hook during a test.
	 *
	 * Each call records the hook arguments and the blog the hook ran on.
	 *
	 * @var array
	 */
	protected $calls = array();

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$blog_id = $factory->blog->create();
	}

	/**
	 * Test set up.
	 */
	public function set_up() {
		parent::set_up();
		wp_set_current_user( 1 ); // Set user to super admin for all tests.

		$this->calls = array();

		foreach ( array( 'dt_receive_post_args', 'dt_receive_post_meta', 'dt_receive_post_terms', 'dt_receive_post_media' ) as $hook ) {
			add_filter(
				$hook,
				function ( $value, $context ) use ( $hook ) {
					$this->calls[ $hook ][] = array(
						'value'   => $value,
						'context' => $context,
						'blog_id' => get_current_blog_id(),
					);
					return $value;
				},
				5,
				2
			);
		}

		add_action(
			'dt_receive_post',
			function ( $post_id, $context ) {
				$this->calls['dt_receive_post'][] = array(
					'post_id' => $post_id,
					'context' => $context,
					'blog_id' => get_current_blog_id(),
				);
			},
			5,
			2
		);
	}

	/**
	 * Create a post on the origin site.
	 *
	 * @param array $args Post arguments.
	 * @return int Post ID.
	 */
	protected function create_origin_post( $args = array() ) {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_title'   => 'Origin title',
					'post_content' => 'Origin content',
					'post_excerpt' => 'Origin excerpt',
				),
				$args
			)
		);
	}

	/**
	 * Assert the context passed to a receive hook.
	 *
	 * @param array  $expected Expected context values, except the connection.
	 * @param array  $context  Actual context.
	 * @param string $message  Message prefix.
	 */
	protected function assertReceiveContext( $expected, $context, $message ) {
		$this->assertInstanceOf( NetworkSiteConnection::class, $context['connection'], "$message: connection should be the network connection." );
		unset( $context['connection'] );
		$this->assertSame( $expected, $context, "$message: unexpected context." );
	}

	/**
	 * A push to a new post runs every receive hook on the receiving site.
	 */
	public function test_push_new_post_runs_receive_hooks() {
		$origin_post_id = $this->create_origin_post();
		$connection     = new NetworkSiteConnection( get_site( self::$blog_id ) );

		$push_result = $connection->push( $origin_post_id );
		$this->assertIsArray( $push_result, 'NetworkSiteConnection::push() should return an array.' );
		$remote_post_id = $push_result['id'];

		$this->assertCount( 1, $this->calls['dt_receive_post_args'], 'dt_receive_post_args should run once.' );
		$args_call = $this->calls['dt_receive_post_args'][0];
		$this->assertSame( self::$blog_id, $args_call['blog_id'], 'dt_receive_post_args should run on the receiving site.' );
		$this->assertSame( 'Origin title', $args_call['value']['post_title'], 'dt_receive_post_args should get the origin title.' );
		$this->assertReceiveContext(
			array(
				'source'           => 'push',
				'connection_type'  => 'internal',
				'is_update'        => false,
				'post_id'          => 0,
				'original_post_id' => $origin_post_id,
			),
			$args_call['context'],
			'dt_receive_post_args'
		);

		$after_insert_context = array(
			'source'           => 'push',
			'connection_type'  => 'internal',
			'is_update'        => false,
			'post_id'          => $remote_post_id,
			'original_post_id' => $origin_post_id,
		);

		foreach ( array( 'dt_receive_post_meta', 'dt_receive_post_terms', 'dt_receive_post_media' ) as $hook ) {
			$this->assertCount( 1, $this->calls[ $hook ], "$hook should run once." );
			$this->assertSame( self::$blog_id, $this->calls[ $hook ][0]['blog_id'], "$hook should run on the receiving site." );
			$this->assertReceiveContext( $after_insert_context, $this->calls[ $hook ][0]['context'], $hook );
		}

		$this->assertCount( 1, $this->calls['dt_receive_post'], 'dt_receive_post should fire once.' );
		$action_call = $this->calls['dt_receive_post'][0];
		$this->assertSame( $remote_post_id, $action_call['post_id'], 'dt_receive_post should get the receiving post ID.' );
		$this->assertSame( self::$blog_id, $action_call['blog_id'], 'dt_receive_post should fire on the receiving site.' );
		$this->assertReceiveContext( $after_insert_context, $action_call['context'], 'dt_receive_post' );
	}

	/**
	 * Filter return values change the post, meta and terms a push saves.
	 */
	public function test_push_filters_change_saved_data() {
		$category_id    = self::factory()->category->create( array( 'slug' => 'receive-news' ) );
		$origin_post_id = $this->create_origin_post( array( 'post_category' => array( $category_id ) ) );
		add_post_meta( $origin_post_id, 'origin_meta', 'origin value' );

		add_filter(
			'dt_receive_post_args',
			function ( $post_args ) {
				$post_args['post_title'] = 'Received title';
				return $post_args;
			}
		);
		add_filter(
			'dt_receive_post_meta',
			function ( $meta ) {
				$meta['received_meta'] = array( 'received value' );
				return $meta;
			}
		);
		add_filter( 'dt_receive_post_terms', '__return_false' );

		$connection  = new NetworkSiteConnection( get_site( self::$blog_id ) );
		$push_result = $connection->push( $origin_post_id );

		switch_to_blog( self::$blog_id );
		$remote_post_id = $push_result['id'];

		$this->assertSame( 'Received title', get_post( $remote_post_id )->post_title, 'dt_receive_post_args should change the saved title.' );
		$this->assertSame( 'origin value', get_post_meta( $remote_post_id, 'origin_meta', true ), 'Origin meta should still be saved.' );
		$this->assertSame( 'received value', get_post_meta( $remote_post_id, 'received_meta', true ), 'dt_receive_post_meta should add meta.' );
		$this->assertNull( term_exists( 'receive-news', 'category' ), 'Returning false from dt_receive_post_terms should skip terms.' );
	}

	/**
	 * Removing media in dt_receive_post_media skips its download.
	 */
	public function test_push_media_filter_skips_download() {
		$origin_post_id = $this->create_origin_post();
		$attachment_id  = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg', $origin_post_id );
		set_post_thumbnail( $origin_post_id, $attachment_id );

		add_filter( 'dt_receive_post_media', '__return_empty_array' );

		$connection  = new NetworkSiteConnection( get_site( self::$blog_id ) );
		$push_result = $connection->push( $origin_post_id );

		$received_media = $this->calls['dt_receive_post_media'][0]['value'];
		$this->assertCount( 1, $received_media, 'dt_receive_post_media should get the featured image.' );
		$this->assertTrue( (bool) $received_media[0]['featured'], 'The media item should be the featured image.' );

		switch_to_blog( self::$blog_id );
		$this->assertFalse( has_post_thumbnail( $push_result['id'] ), 'The receiving post should have no featured image.' );
		$attachments = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
			)
		);
		$this->assertCount( 0, $attachments, 'No media should be downloaded.' );
	}

	/**
	 * Saving the origin post sends the update to the receiving site through
	 * update_syndicated(), which runs the receive hooks as an update.
	 */
	public function test_origin_update_runs_receive_hooks() {
		$origin_post_id = $this->create_origin_post();
		$connection     = new NetworkSiteConnection( get_site( self::$blog_id ) );
		$push_result    = $connection->push( $origin_post_id );
		$remote_post_id = $push_result['id'];

		// The push UI records the connection map. Record it here so updates are sent.
		update_post_meta(
			$origin_post_id,
			'dt_connection_map',
			array(
				'internal' => array(
					self::$blog_id => array(
						'post_id' => $remote_post_id,
						'time'    => time(),
					),
				),
				'external' => array(),
			)
		);

		$this->calls = array();

		wp_update_post(
			array(
				'ID'         => $origin_post_id,
				'post_title' => 'Updated origin title',
			)
		);

		$this->assertCount( 1, $this->calls['dt_receive_post_args'], 'dt_receive_post_args should run once for the update.' );
		$args_call = $this->calls['dt_receive_post_args'][0];
		$this->assertSame( 'Updated origin title', $args_call['value']['post_title'], 'dt_receive_post_args should get the updated title.' );
		$this->assertSame( $remote_post_id, $args_call['value']['ID'], 'dt_receive_post_args should target the receiving post.' );
		$this->assertReceiveContext(
			array(
				'source'           => 'push',
				'connection_type'  => 'internal',
				'is_update'        => true,
				'post_id'          => $remote_post_id,
				'original_post_id' => $origin_post_id,
			),
			$args_call['context'],
			'dt_receive_post_args'
		);

		$this->assertCount( 1, $this->calls['dt_receive_post'], 'dt_receive_post should fire once for the update.' );
		$this->assertSame( $remote_post_id, $this->calls['dt_receive_post'][0]['post_id'], 'dt_receive_post should get the receiving post ID.' );
		$this->assertTrue( $this->calls['dt_receive_post'][0]['context']['is_update'], 'dt_receive_post should mark the update.' );

		switch_to_blog( self::$blog_id );
		$this->assertSame( 'Updated origin title', get_post( $remote_post_id )->post_title, 'The receiving post should get the update.' );
	}

	/**
	 * A pull runs every receive hook on the pulling site.
	 */
	public function test_pull_runs_receive_hooks() {
		$origin_post_id = $this->create_origin_post();

		add_filter(
			'dt_receive_post_args',
			function ( $post_args ) {
				$post_args['post_title'] = 'Pulled title';
				return $post_args;
			}
		);

		switch_to_blog( self::$blog_id );
		$connection  = new NetworkSiteConnection( get_site( 1 ) );
		$pull_result = $connection->pull( array( array( 'remote_post_id' => $origin_post_id ) ) );
		$this->assertIsArray( $pull_result, 'NetworkSiteConnection::pull() should return an array.' );
		$pulled_post_id = $pull_result[0];

		$this->assertSame( 'Pulled title', get_post( $pulled_post_id )->post_title, 'dt_receive_post_args should change the saved title.' );

		$this->assertCount( 1, $this->calls['dt_receive_post_args'], 'dt_receive_post_args should run once.' );
		$this->assertSame( self::$blog_id, $this->calls['dt_receive_post_args'][0]['blog_id'], 'dt_receive_post_args should run on the pulling site.' );
		$this->assertReceiveContext(
			array(
				'source'           => 'pull',
				'connection_type'  => 'internal',
				'is_update'        => false,
				'post_id'          => 0,
				'original_post_id' => $origin_post_id,
			),
			$this->calls['dt_receive_post_args'][0]['context'],
			'dt_receive_post_args'
		);

		$after_insert_context = array(
			'source'           => 'pull',
			'connection_type'  => 'internal',
			'is_update'        => false,
			'post_id'          => $pulled_post_id,
			'original_post_id' => $origin_post_id,
		);

		foreach ( array( 'dt_receive_post_meta', 'dt_receive_post_terms', 'dt_receive_post_media' ) as $hook ) {
			$this->assertCount( 1, $this->calls[ $hook ], "$hook should run once." );
			$this->assertReceiveContext( $after_insert_context, $this->calls[ $hook ][0]['context'], $hook );
		}

		$this->assertCount( 1, $this->calls['dt_receive_post'], 'dt_receive_post should fire once.' );
		$this->assertSame( $pulled_post_id, $this->calls['dt_receive_post'][0]['post_id'], 'dt_receive_post should get the pulled post ID.' );
		$this->assertReceiveContext( $after_insert_context, $this->calls['dt_receive_post'][0]['context'], 'dt_receive_post' );
	}

	/**
	 * dt_receive_post does not fire when the receiving site fails to insert the post.
	 */
	public function test_push_failed_insert_skips_receive_post_action() {
		$origin_post_id = $this->create_origin_post();

		// Make wp_insert_post() fail on the receiving site.
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		$connection = new NetworkSiteConnection( get_site( self::$blog_id ) );
		$connection->push( $origin_post_id );

		$this->assertArrayNotHasKey( 'dt_receive_post', $this->calls, 'dt_receive_post should not fire after a failed insert.' );
	}

	/**
	 * dt_receive_post does not fire when the pulling site fails to insert the post.
	 */
	public function test_pull_failed_insert_skips_receive_post_action() {
		$origin_post_id = $this->create_origin_post();

		switch_to_blog( self::$blog_id );

		// Make wp_insert_post() fail on the pulling site.
		add_filter( 'wp_insert_post_empty_content', '__return_true' );

		$connection = new NetworkSiteConnection( get_site( 1 ) );
		$connection->pull( array( array( 'remote_post_id' => $origin_post_id ) ) );

		$this->assertArrayNotHasKey( 'dt_receive_post', $this->calls, 'dt_receive_post should not fire after a failed insert.' );
	}

	/**
	 * Create an unlinked post received from an external connection, with a
	 * stored subscription update waiting to be applied on relink.
	 *
	 * @return int Post ID.
	 */
	protected function create_unlinked_external_post() {
		$post_id = $this->create_origin_post(
			array(
				'post_title'   => 'Local title',
				'post_content' => 'Local content',
				'post_excerpt' => 'Local excerpt',
			)
		);

		update_post_meta( $post_id, 'dt_original_source_id', 5 );
		update_post_meta( $post_id, 'dt_original_post_id', 77 );
		update_post_meta( $post_id, 'dt_unlinked', true );
		update_post_meta(
			$post_id,
			'dt_subscription_update',
			array(
				'post_title'   => 'Stored title',
				'post_name'    => 'stored-title',
				'post_content' => 'Stored content',
				'post_excerpt' => 'Stored excerpt',
				'meta'         => array( 'stored_meta' => array( 'stored value' ) ),
				'terms'        => null,
				'media'        => array(
					array(
						'id'         => 9,
						'source_url' => 'https://example.com/stored.jpg',
						'featured'   => true,
					),
				),
			)
		);

		// Skip downloads, as the stored media item does not exist.
		add_filter( 'dt_receive_post_media', '__return_false' );

		return $post_id;
	}

	/**
	 * Relink a post through link(), as the "Restore link" button does.
	 *
	 * link() ends with a redirect and `exit`, so a wp_redirect filter throws
	 * to stop it first.
	 *
	 * @param int $post_id Post ID.
	 * @return string The redirect location.
	 */
	protected function relink( $post_id ) {
		$_GET['action']   = 'link';
		$_GET['post']     = (string) $post_id;
		$_GET['_wpnonce'] = wp_create_nonce( 'link-post_' . $post_id );

		// phpcs:disable WordPressVIPMinimum.Hooks.AlwaysReturnInFilter.TerminatingInsteadOfReturn -- Stops link() before it exits.
		add_filter(
			'wp_redirect',
			function ( $location ) {
				throw new \Exception( $location );
			}
		);
		// phpcs:enable

		try {
			\Distributor\SyndicatedPostUI\link();
		} catch ( \Exception $redirect ) {
			return $redirect->getMessage();
		} finally {
			unset( $_GET['action'], $_GET['post'], $_GET['_wpnonce'] );
		}

		$this->fail( 'link() should redirect.' );
	}

	/**
	 * Relinking an external post applies the stored update and runs every
	 * receive hook with the relink context.
	 */
	public function test_external_relink_runs_receive_hooks() {
		$post_id  = $this->create_unlinked_external_post();
		$location = $this->relink( $post_id );

		$this->assertStringContainsString( 'post=' . $post_id, $location, 'link() should redirect to the post edit screen.' );
		$this->assertFalse( (bool) get_post_meta( $post_id, 'dt_unlinked', true ), 'The post should be linked again.' );

		$expected_context = array(
			'source'           => 'relink',
			'connection_type'  => 'external',
			'is_update'        => true,
			'post_id'          => $post_id,
			'original_post_id' => 77,
			'connection'       => null,
		);

		$this->assertCount( 1, $this->calls['dt_receive_post_args'], 'dt_receive_post_args should run once.' );
		$this->assertSame(
			array(
				'ID'           => $post_id,
				'post_title'   => 'Stored title',
				'post_content' => 'Stored content',
				'post_excerpt' => 'Stored excerpt',
			),
			$this->calls['dt_receive_post_args'][0]['value'],
			'dt_receive_post_args should get the stored update.'
		);
		$this->assertSame( $expected_context, $this->calls['dt_receive_post_args'][0]['context'], 'dt_receive_post_args: unexpected context.' );

		$this->assertSame( array( 'stored_meta' => array( 'stored value' ) ), $this->calls['dt_receive_post_meta'][0]['value'], 'dt_receive_post_meta should get the stored meta.' );
		$this->assertSame( $expected_context, $this->calls['dt_receive_post_meta'][0]['context'], 'dt_receive_post_meta: unexpected context.' );
		$this->assertSame( $expected_context, $this->calls['dt_receive_post_media'][0]['context'], 'dt_receive_post_media: unexpected context.' );
		$this->assertArrayNotHasKey( 'dt_receive_post_terms', $this->calls, 'dt_receive_post_terms should not run when no terms are stored.' );

		$this->assertCount( 1, $this->calls['dt_receive_post'], 'dt_receive_post should fire once.' );
		$this->assertSame( $post_id, $this->calls['dt_receive_post'][0]['post_id'], 'dt_receive_post should get the relinked post ID.' );
		$this->assertSame( $expected_context, $this->calls['dt_receive_post'][0]['context'], 'dt_receive_post: unexpected context.' );

		$post = get_post( $post_id );
		$this->assertSame( 'Stored title', $post->post_title, 'The stored title should be applied.' );
		$this->assertSame( 'Stored content', $post->post_content, 'The stored content should be applied.' );
		$this->assertSame( 'stored value', get_post_meta( $post_id, 'stored_meta', true ), 'The stored meta should be applied.' );
	}

	/**
	 * Filter return values change what a relink saves, but cannot move the
	 * update to another post.
	 */
	public function test_external_relink_filters_change_saved_data() {
		$post_id       = $this->create_unlinked_external_post();
		$other_post_id = $this->create_origin_post( array( 'post_title' => 'Other title' ) );

		add_filter(
			'dt_receive_post_args',
			function ( $post_args ) use ( $other_post_id ) {
				$post_args['ID']         = $other_post_id;
				$post_args['post_title'] = 'Relinked title';
				return $post_args;
			}
		);
		add_filter( 'dt_receive_post_meta', '__return_false' );

		$this->relink( $post_id );

		$this->assertSame( 'Relinked title', get_post( $post_id )->post_title, 'dt_receive_post_args should change the saved title.' );
		$this->assertSame( 'Other title', get_post( $other_post_id )->post_title, 'dt_receive_post_args should not move the update to another post.' );
		$this->assertSame( '', get_post_meta( $post_id, 'stored_meta', true ), 'Returning false from dt_receive_post_meta should skip meta.' );
	}

	/**
	 * Relinking without a stored update runs no receive hooks.
	 */
	public function test_external_relink_without_stored_update_skips_receive_hooks() {
		$post_id = $this->create_origin_post();
		update_post_meta( $post_id, 'dt_original_source_id', 5 );
		update_post_meta( $post_id, 'dt_unlinked', true );

		$this->relink( $post_id );

		$this->assertSame( array(), $this->calls, 'No receive hook should run without a stored update.' );
	}
}
