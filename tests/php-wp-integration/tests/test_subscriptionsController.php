<?php
/**
 * Subscriptions REST controller tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_REST_Request;
use WP_REST_Response;
use WP_UnitTestCase;
use Distributor\API\SubscriptionsController;

/**
 * Integration tests for the subscription REST controller.
 *
 * These cover the endpoint authorisation, which is the only thing standing
 * between an anonymous request and an overwrite of a distributed post.
 *
 * Requests are dispatched through the REST server so route registration,
 * argument validation and permission callbacks are all exercised. A few tests
 * call the controller directly to pin guards that core's argument validation
 * prevents requests from reaching.
 *
 * @group Subscriptions
 */
class Test_SubscriptionsController extends WP_UnitTestCase {
	/**
	 * Signature stored on distributed posts.
	 *
	 * @var string
	 */
	const SIGNATURE = 'the-real-signature';

	/**
	 * Set up.
	 */
	public function set_up() {
		parent::set_up();

		// Initialize a fresh REST server so routes are registered for this test.
		$GLOBALS['wp_rest_server'] = null;
		rest_get_server();

		// Subscription notifications are sent by remote sites without authentication.
		wp_set_current_user( 0 );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		$GLOBALS['wp_rest_server'] = null;
		parent::tear_down();
	}

	/**
	 * Dispatch a request to a subscription endpoint.
	 *
	 * @param string $method   HTTP method.
	 * @param string $endpoint Endpoint below the subscription route, empty for the collection.
	 * @param array  $params   Request parameters.
	 * @return WP_REST_Response Response.
	 */
	protected function dispatch( $method, $endpoint, $params ) {
		$request = new WP_REST_Request( $method, rtrim( '/wp/v2/dt_subscription/' . $endpoint, '/' ) );

		if ( 'GET' === $method || 'DELETE' === $method ) {
			$request->set_query_params( $params );
		} else {
			// Subscription notifications are sent as JSON.
			$request->set_header( 'Content-Type', 'application/json' );
			$request->set_body( wp_json_encode( $params ) );
		}

		return rest_do_request( $request );
	}

	/**
	 * Get the controller registered for the subscription routes.
	 *
	 * @return SubscriptionsController Controller instance.
	 */
	protected function get_controller() {
		$routes = rest_get_server()->get_routes();
		return $routes['/wp/v2/dt_subscription/receive'][0]['callback'][0];
	}

	/**
	 * Build a request carrying the given parameters for calling the controller directly.
	 *
	 * @param array $params Request parameters.
	 * @return WP_REST_Request Request.
	 */
	protected function get_request( array $params ) {
		$request = new WP_REST_Request( 'POST', '/wp/v2/dt_subscription/receive' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Assert a REST response or direct result is an error with the given code and status.
	 *
	 * @param string                     $code   Expected error code.
	 * @param int                        $status Expected HTTP status.
	 * @param WP_REST_Response|\WP_Error $result Result under test.
	 */
	protected function assert_rest_error( $code, $status, $result ) {
		if ( $result instanceof WP_REST_Response ) {
			$this->assertTrue( $result->is_error(), 'Expected an error response.' );
			$this->assertSame( $status, $result->get_status(), 'Unexpected response status.' );
			$result = $result->as_error();
		}

		$this->assertWPError( $result, 'Expected a WP_Error.' );
		$this->assertSame( $code, $result->get_error_code(), 'Unexpected error code.' );

		$data = $result->get_error_data();
		$this->assertSame( $status, $data['status'], 'Unexpected error status.' );
	}

	/**
	 * Create a post distributed to this site, as a subscription notification target.
	 *
	 * @param array $meta Additional post meta.
	 * @return int Post ID.
	 */
	protected function create_distributed_post( $meta = array() ) {
		return self::factory()->post->create(
			array(
				'post_title'   => 'Original title',
				'post_content' => 'Original content',
				'post_excerpt' => 'Original excerpt',
				'post_name'    => 'original-slug',
				'meta_input'   => array_merge(
					array(
						'dt_original_post_id'       => 5,
						'dt_subscription_signature' => self::SIGNATURE,
					),
					$meta
				),
			)
		);
	}

	/**
	 * Get the ID of a post that does not exist.
	 *
	 * @return int Post ID.
	 */
	protected function get_deleted_post_id() {
		$post_id = self::factory()->post->create();
		wp_delete_post( $post_id, true );
		return $post_id;
	}

	/**
	 * Deny a capability to the current user.
	 *
	 * @param string   $denied_cap Capability to deny.
	 * @param int|null $object_id  Optional. Only deny the capability for this object ID.
	 */
	protected function deny_capability( $denied_cap, $object_id = null ) {
		add_filter(
			'map_meta_cap',
			static function ( $caps, $cap, $user_id, $args ) use ( $denied_cap, $object_id ) {
				if ( $denied_cap !== $cap ) {
					return $caps;
				}

				if ( null !== $object_id && ( ! isset( $args[0] ) || (int) $args[0] !== $object_id ) ) {
					return $caps;
				}

				$caps[] = 'do_not_allow';
				return $caps;
			},
			10,
			4
		);
	}

	/*
	 * ---------------------------------------------------------------------
	 * Receive endpoint permissions.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A request with no signature must be rejected.
	 */
	public function test_receive_rejects_missing_signature() {
		$post_id = $this->create_distributed_post();

		$response = $this->dispatch( 'POST', 'receive', array( 'post_id' => $post_id ) );
		$this->assert_rest_error( 'rest_missing_callback_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->receive_item_permissions_check( $this->get_request( array( 'post_id' => $post_id ) ) );
		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $result );
	}

	/**
	 * An empty signature must be rejected.
	 */
	public function test_receive_rejects_empty_signature() {
		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $this->create_distributed_post(),
				'signature' => '',
			)
		);

		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $response );
	}

	/**
	 * A non-string signature must not reach `hash_equals()`, which raises a
	 * TypeError.
	 */
	public function test_receive_rejects_array_signature() {
		$params = array(
			'post_id'   => $this->create_distributed_post(),
			'signature' => array( 'x' ),
		);

		$response = $this->dispatch( 'POST', 'receive', $params );
		$this->assert_rest_error( 'rest_invalid_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->receive_item_permissions_check( $this->get_request( $params ) );
		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $result );
	}

	/**
	 * A missing post ID must be rejected.
	 */
	public function test_receive_rejects_missing_post_id() {
		$response = $this->dispatch( 'POST', 'receive', array( 'signature' => self::SIGNATURE ) );
		$this->assert_rest_error( 'rest_missing_callback_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->receive_item_permissions_check( $this->get_request( array( 'signature' => self::SIGNATURE ) ) );
		$this->assert_rest_error( 'rest_post_invalid_post_id', 400, $result );
	}

	/**
	 * A post with no stored signature must be rejected, so posts distributed
	 * over connections that never stored one cannot be written to.
	 */
	public function test_receive_rejects_post_without_stored_signature() {
		$post_id = $this->create_distributed_post();
		delete_post_meta( $post_id, 'dt_subscription_signature' );

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $post_id,
				'signature' => self::SIGNATURE,
			)
		);

		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $response );
	}

	/**
	 * A signature that does not match the stored one must be rejected.
	 */
	public function test_receive_rejects_incorrect_signature() {
		$post_id = $this->create_distributed_post();

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $post_id,
				'signature' => 'not-the-real-signature',
				'post_data' => array( 'title' => 'Overwritten title' ),
			)
		);

		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $response );
		$this->assertSame( 'Original title', get_post( $post_id )->post_title, 'Post should not be updated.' );
	}

	/**
	 * The matching signature must be accepted, so real subscription updates
	 * still get through.
	 */
	public function test_receive_accepts_correct_signature() {
		$post_id = $this->create_distributed_post();
		$params  = array(
			'post_id'   => $post_id,
			'signature' => self::SIGNATURE,
		);

		$this->assertTrue( $this->get_controller()->receive_item_permissions_check( $this->get_request( $params ) ), 'Permission check should pass.' );

		$params['post_data'] = array( 'title' => 'Updated title' );
		$response            = $this->dispatch( 'POST', 'receive', $params );

		$this->assertSame( 200, $response->get_status(), 'Request should succeed.' );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Delete endpoint permissions.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A missing signature must be rejected.
	 */
	public function test_delete_rejects_missing_signature() {
		$post_id = self::factory()->post->create();

		$response = $this->dispatch( 'DELETE', 'delete', array( 'post_id' => $post_id ) );
		$this->assert_rest_error( 'rest_missing_callback_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->delete_item_permissions_check( $this->get_request( array( 'post_id' => $post_id ) ) );
		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $result );
	}

	/**
	 * A non-string signature must not reach md5(), which raises a TypeError
	 * when handed an array.
	 */
	public function test_delete_rejects_array_signature() {
		$params = array(
			'post_id'   => self::factory()->post->create(),
			'signature' => array( 'x' ),
		);

		$response = $this->dispatch( 'DELETE', 'delete', $params );
		$this->assert_rest_error( 'rest_invalid_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->delete_item_permissions_check( $this->get_request( $params ) );
		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $result );
	}

	/**
	 * An unknown post must answer exactly as an unknown signature does, so the
	 * endpoint does not report whether a given post ID exists.
	 */
	public function test_delete_does_not_disclose_post_existence() {
		$existing_post = self::factory()->post->create();

		$missing_response  = $this->dispatch(
			'DELETE',
			'delete',
			array(
				'post_id'   => $this->get_deleted_post_id(),
				'signature' => 'abc',
			)
		);
		$existing_response = $this->dispatch(
			'DELETE',
			'delete',
			array(
				'post_id'   => $existing_post,
				'signature' => 'abc',
			)
		);

		$this->assert_rest_error( 'rest_post_invalid_signature', 403, $missing_response );
		$this->assertSame( $existing_response->get_status(), $missing_response->get_status(), 'Status should not disclose whether the post exists.' );
		$this->assertSame( $existing_response->get_data(), $missing_response->get_data(), 'Response should not disclose whether the post exists.' );
	}

	/**
	 * A signature present in the subscription map must be accepted and the
	 * subscription deleted.
	 */
	public function test_delete_accepts_known_signature() {
		$post_id         = self::factory()->post->create();
		$subscription_id = \Distributor\Subscriptions\create_subscription( $post_id, 20, 'https://example.com/wp-json', 'good-signature' );

		$response = $this->dispatch(
			'DELETE',
			'delete',
			array(
				'post_id'   => $post_id,
				'signature' => 'good-signature',
			)
		);

		$this->assertSame( 200, $response->get_status(), 'Request should succeed.' );
		$this->assertSame( array( 'deleted' => true ), $response->get_data(), 'Response should report the deletion.' );
		$this->assertNull( get_post( $subscription_id ), 'Subscription post should be deleted.' );
		$this->assertSame( array(), get_post_meta( $post_id, 'dt_subscriptions', true ), 'Subscription should be removed from the post.' );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Create endpoint.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * Get valid parameters for creating a subscription.
	 *
	 * @param int $post_id Post being subscribed to.
	 * @return array Request parameters.
	 */
	protected function get_create_params( $post_id ) {
		return array(
			'post_id'        => $post_id,
			'remote_post_id' => 20,
			'signature'      => 'abc',
			'target_url'     => 'https://example.com/wp-json',
		);
	}

	/**
	 * An anonymous caller must be rejected.
	 */
	public function test_create_rejects_anonymous_user() {
		$response = $this->dispatch( 'POST', '', $this->get_create_params( self::factory()->post->create() ) );

		$this->assert_rest_error( 'rest_cannot_create', 401, $response );
	}

	/**
	 * A caller without the generic creation capability must be rejected.
	 */
	public function test_create_rejects_without_capability() {
		wp_set_current_user( 1 );
		$this->deny_capability( get_post_type_object( 'dt_subscription' )->cap->create_posts );

		$response = $this->dispatch( 'POST', '', $this->get_create_params( self::factory()->post->create() ) );

		$this->assert_rest_error( 'rest_cannot_create', 403, $response );
	}

	/**
	 * A caller holding only the generic capability must not be able to
	 * subscribe to a post they cannot edit. A subscription sends the post's
	 * full content to an arbitrary URL.
	 */
	public function test_create_rejects_post_the_user_cannot_edit() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();
		$this->deny_capability( 'edit_post', $post_id );

		$response = $this->dispatch( 'POST', '', $this->get_create_params( $post_id ) );

		$this->assert_rest_error( 'rest_cannot_create', 403, $response );
		$this->assertSame( '', get_post_meta( $post_id, 'dt_subscriptions', true ), 'No subscription should be created.' );
	}

	/**
	 * An unknown post must be rejected.
	 */
	public function test_create_rejects_unknown_post() {
		wp_set_current_user( 1 );

		$response = $this->dispatch( 'POST', '', $this->get_create_params( $this->get_deleted_post_id() ) );

		$this->assert_rest_error( 'rest_post_invalid_id', 404, $response );
	}

	/**
	 * A caller who can edit the post must be able to subscribe, so the pull
	 * flow that registers a remote subscription still works.
	 */
	public function test_create_accepts_editable_post() {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();

		$response = $this->dispatch( 'POST', '', $this->get_create_params( $post_id ) );

		$this->assertSame( 201, $response->get_status(), 'Subscription should be created.' );

		$data = $response->get_data();
		$this->assertSame( $post_id, $data['post_id'], 'Response should include the subscribed post ID.' );
		$this->assertSame( 20, $data['remote_post_id'], 'Response should include the remote post ID.' );
		$this->assertSame( 'https://example.com/wp-json', $data['target_url'], 'Response should include the target URL.' );

		$subscriptions = get_post_meta( $post_id, 'dt_subscriptions', true );
		$this->assertSame( array( md5( 'abc' ) => $data['id'] ), $subscriptions, 'Subscription should be stored on the post, keyed by the signature hash.' );

		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );
		$this->assertSame( 20, $connection_map['external'][-1]['post_id'], 'Post should be marked as distributed.' );
	}

	/**
	 * A target URL that is not an http(s) URL must be rejected before it is
	 * stored and later requested.
	 *
	 * @dataProvider data_create_rejects_invalid_target_url
	 *
	 * @param string $target_url Target URL under test.
	 */
	public function test_create_rejects_invalid_target_url( $target_url ) {
		wp_set_current_user( 1 );
		$post_id = self::factory()->post->create();
		$params  = array_merge( $this->get_create_params( $post_id ), array( 'target_url' => $target_url ) );

		$response = $this->dispatch( 'POST', '', $params );

		$this->assert_rest_error( 'rest_subscription_invalid_target_url', 400, $response );
		$this->assertSame( '', get_post_meta( $post_id, 'dt_subscriptions', true ), 'No subscription should be created.' );
	}

	/**
	 * Target URLs that must not be accepted.
	 *
	 * @return array[] Data provider.
	 */
	public function data_create_rejects_invalid_target_url() {
		return array(
			'javascript scheme' => array( 'javascript:alert(1)' ),
			'file scheme'       => array( 'file:///etc/passwd' ),
			'no scheme'         => array( 'example.com/wp-json' ),
			'not a url'         => array( 'nonsense' ),
			'empty'             => array( '' ),
		);
	}

	/**
	 * A non-string target URL must be rejected.
	 */
	public function test_create_rejects_array_target_url() {
		wp_set_current_user( 1 );
		$params = array_merge(
			$this->get_create_params( self::factory()->post->create() ),
			array( 'target_url' => array( 'https://example.com' ) )
		);

		$response = $this->dispatch( 'POST', '', $params );
		$this->assert_rest_error( 'rest_invalid_param', 400, $response );

		// Pin the controller's own guard, which core's validation prevents requests reaching.
		$result = $this->get_controller()->create_item( $this->get_request( $params ) );
		$this->assert_rest_error( 'rest_subscription_invalid_target_url', 400, $result );
	}

	/*
	 * ---------------------------------------------------------------------
	 * Receive endpoint.
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A legitimate update must be applied, and taxonomy terms must be limited to
	 * taxonomies shown in the REST API.
	 */
	public function test_receive_item_applies_update_and_limits_taxonomies() {
		register_taxonomy( 'dt_hidden_tax', 'post', array( 'show_in_rest' => false ) );
		$post_id = $this->create_distributed_post();

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $post_id,
				'signature' => self::SIGNATURE,
				'post_data' => array(
					'title'             => 'New title',
					'slug'              => 'new-slug',
					'excerpt'           => 'New excerpt',
					'content'           => 'New content',
					'distributor_terms' => array(
						'category'      => array(
							array(
								'term_id'     => 100,
								'name'        => 'News',
								'slug'        => 'news',
								'description' => '',
								'parent'      => 0,
							),
						),
						'dt_hidden_tax' => array(
							array(
								'term_id'     => 101,
								'name'        => 'Secret',
								'slug'        => 'secret',
								'description' => '',
								'parent'      => 0,
							),
						),
					),
				),
			)
		);

		$this->assertSame( 200, $response->get_status(), 'Request should succeed.' );
		$this->assertSame( array( 'updated' => true ), $response->get_data(), 'Response should report the update.' );

		$post = get_post( $post_id );
		$this->assertSame( 'New title', $post->post_title, 'Post title should be updated.' );
		$this->assertSame( 'new-slug', $post->post_name, 'Post slug should be updated.' );
		$this->assertSame( 'New excerpt', $post->post_excerpt, 'Post excerpt should be updated.' );
		$this->assertSame( 'New content', $post->post_content, 'Post content should be updated.' );

		$this->assertSame( array( 'news' ), wp_get_post_terms( $post_id, 'category', array( 'fields' => 'slugs' ) ), 'Category terms should be set.' );
		$this->assertSame( array(), wp_get_post_terms( $post_id, 'dt_hidden_tax', array( 'fields' => 'slugs' ) ), 'Hidden taxonomy terms should not be set.' );
		$this->assertFalse( get_term_by( 'slug', 'secret', 'dt_hidden_tax' ), 'Hidden taxonomy terms should not be created.' );

		$stored_update = get_post_meta( $post_id, 'dt_subscription_update', true );
		$this->assertSame( array( 'category' ), array_keys( $stored_update['terms'] ), 'Stored update should only include REST visible taxonomies.' );
	}

	/**
	 * An update to an unlinked post must be stored but not applied.
	 */
	public function test_receive_item_stores_update_for_unlinked_post() {
		$post_id = $this->create_distributed_post( array( 'dt_unlinked' => 1 ) );

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $post_id,
				'signature' => self::SIGNATURE,
				'post_data' => array( 'title' => 'New title' ),
			)
		);

		$this->assertSame( 200, $response->get_status(), 'Request should succeed.' );
		$this->assertSame( array( 'updated' => false ), $response->get_data(), 'Response should report the post was not updated.' );
		$this->assertSame( 'Original title', get_post( $post_id )->post_title, 'Unlinked post should not be updated.' );
		$this->assertSame( 'New title', get_post_meta( $post_id, 'dt_subscription_update', true )['post_title'], 'Update should be stored for relinking.' );
	}

	/**
	 * A notification that the original was deleted must be recorded.
	 */
	public function test_receive_item_records_original_deleted() {
		$post_id = $this->create_distributed_post();

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'          => $post_id,
				'signature'        => self::SIGNATURE,
				'original_deleted' => true,
			)
		);

		$this->assertSame( 200, $response->get_status(), 'Request should succeed.' );
		$this->assertSame( array( 'updated' => true ), $response->get_data(), 'Response should report the update.' );
		$this->assertSame( '1', get_post_meta( $post_id, 'dt_original_post_deleted', true ), 'Original deletion should be recorded.' );
		$this->assertSame( 'Original title', get_post( $post_id )->post_title, 'Post content should not change.' );
	}

	/**
	 * A post that was never distributed must not be updated.
	 */
	public function test_receive_item_rejects_undistributed_post() {
		$post_id = $this->create_distributed_post();
		delete_post_meta( $post_id, 'dt_original_post_id' );

		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $post_id,
				'signature' => self::SIGNATURE,
				'post_data' => array( 'title' => 'New title' ),
			)
		);

		$this->assert_rest_error( 'rest_post_not_distributed', 400, $response );
		$this->assertSame( 'Original title', get_post( $post_id )->post_title, 'Post should not be updated.' );
	}

	/**
	 * A scalar `post_data` must be rejected. Indexing into a string raises a
	 * TypeError on PHP 8, which turned a malformed payload into a fatal error.
	 */
	public function test_receive_item_rejects_non_array_post_data() {
		$response = $this->dispatch(
			'POST',
			'receive',
			array(
				'post_id'   => $this->create_distributed_post(),
				'signature' => self::SIGNATURE,
				'post_data' => 'not-an-array',
			)
		);

		$this->assert_rest_error( 'rest_post_no_data', 400, $response );
	}

	/**
	 * A deleted post must report as deleted rather than erroring.
	 *
	 * Called directly: a deleted post has no stored signature, so a request
	 * for one is rejected by the permission check before reaching here.
	 */
	public function test_receive_item_reports_deleted_post() {
		$result = $this->get_controller()->receive_item( $this->get_request( array( 'post_id' => $this->get_deleted_post_id() ) ) );

		$this->assertInstanceOf( WP_REST_Response::class, $result, 'A response should be returned.' );
		$this->assertSame( 404, $result->get_status(), 'Response should be not found.' );
		$this->assertSame( 'yes', $result->get_headers()['X-Distributor-Post-Deleted'] ?? null, 'Response should flag the post as deleted.' );
	}
}
