<?php
/**
 * Tests for the subscription REST controller.
 *
 * These cover the endpoint authorisation, which is the only thing standing
 * between an anonymous request and an overwrite of a distributed post.
 *
 * @package distributor
 */

namespace Distributor;

use Distributor\API\SubscriptionsController;
use WP_Mock\Tools\TestCase;

class SubscriptionsControllerTest extends TestCase {

	/**
	 * Set up with WP_Mock
	 */
	public function setUp(): void {
		parent::setUp();

		\WP_Mock::userFunction( 'esc_html__' );
		\WP_Mock::userFunction(
			'rest_authorization_required_code',
			[
				'return' => 401,
			]
		);
	}

	/**
	 * Build a controller with the post type lookup its constructor needs.
	 *
	 * @param string $create_posts_cap Capability mapped to `create_posts`.
	 * @return SubscriptionsController
	 */
	protected function get_controller( $create_posts_cap = 'edit_posts' ) {
		\WP_Mock::userFunction(
			'get_post_type_object',
			[
				'return' => (object) [
					'name'      => 'dt_subscription',
					'rest_base' => 'dt_subscription',
					'cap'       => (object) [
						'create_posts' => $create_posts_cap,
					],
				],
			]
		);

		return new SubscriptionsController( 'dt_subscription' );
	}

	/**
	 * Build a request carrying the given parameters.
	 *
	 * @param array $params Request parameters.
	 * @return \WP_REST_Request
	 */
	protected function get_request( array $params ) {
		$request = new \WP_REST_Request( 'POST', '/wp/v2/dt_subscription/receive' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * Assert a result is a WP_Error with the given code and status.
	 *
	 * @param mixed  $result Result under test.
	 * @param string $code   Expected error code.
	 * @param int    $status Expected HTTP status.
	 */
	protected function assertRestError( $result, $code, $status ) {
		$this->assertInstanceOf( \WP_Error::class, $result, 'Expected a WP_Error.' );
		$this->assertSame( $code, $result->get_error_code() );

		$data = $result->get_error_data();
		$this->assertSame( $status, $data['status'] );
	}

	/*
	 * ---------------------------------------------------------------------
	 * receive_item_permissions_check()
	 * ---------------------------------------------------------------------
	 */

	/**
	 * An anonymous request with no signature must be rejected.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_missing_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$result = $controller->receive_item_permissions_check( $this->get_request( [ 'post_id' => 10 ] ) );

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * An empty signature must be rejected.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_empty_signature() {
		$controller = $this->get_controller();

		$result = $controller->receive_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => '',
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * A non-string signature must not reach `hash_equals()`, which raises a
	 * TypeError. Core rejects the wrong type first, so this pins the guard
	 * rather than a reachable request.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_array_signature() {
		$controller = $this->get_controller();

		$result = $controller->receive_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => [ 'x' ],
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * A missing post ID must be rejected.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_missing_post_id() {
		$controller = $this->get_controller();

		$result = $controller->receive_item_permissions_check( $this->get_request( [ 'signature' => 'abc' ] ) );

		$this->assertRestError( $result, 'rest_post_invalid_post_id', 400 );
	}

	/**
	 * A post with no stored signature must be rejected, so posts distributed
	 * over connections that never stored one cannot be written to.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_post_without_stored_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'times'  => 1,
				'args'   => [ 10, 'dt_subscription_signature', true ],
				'return' => '',
			]
		);

		$result = $controller->receive_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => 'abc',
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * A signature that does not match the stored one must be rejected. This is
	 * the case that was previously allowed through.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_rejects_incorrect_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'times'  => 1,
				'args'   => [ 10, 'dt_subscription_signature', true ],
				'return' => 'the-real-signature',
			]
		);

		$result = $controller->receive_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => 'not-the-real-signature',
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * The matching signature must be accepted, so real subscription updates
	 * still get through.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_accepts_correct_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'times'  => 1,
				'args'   => [ 10, 'dt_subscription_signature', true ],
				'return' => 'the-real-signature',
			]
		);

		$result = $controller->receive_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => 'the-real-signature',
				]
			)
		);

		$this->assertTrue( $result );
	}

	/*
	 * ---------------------------------------------------------------------
	 * delete_item_permissions_check()
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A missing signature must be rejected without looking the post up.
	 *
	 * @group Subscriptions
	 */
	public function test_delete_rejects_missing_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction( 'get_post', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$result = $controller->delete_item_permissions_check( $this->get_request( [ 'post_id' => 10 ] ) );

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * A non-string signature must not reach md5(), which raises a TypeError
	 * when handed an array. Core rejects the wrong type first, so this pins
	 * the guard rather than a reachable request.
	 *
	 * @group Subscriptions
	 */
	public function test_delete_rejects_array_signature() {
		$controller = $this->get_controller();

		$result = $controller->delete_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => [ 'x' ],
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * An unknown post must answer exactly as an unknown signature does, so the
	 * endpoint does not report whether a given post ID exists.
	 *
	 * @group Subscriptions
	 */
	public function test_delete_does_not_disclose_post_existence() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 999999, 'dt_subscriptions', true ],
				'return' => '',
			]
		);

		// The post lookup must not be reached for an unverified caller.
		\WP_Mock::userFunction( 'get_post', [ 'times' => 0 ] );

		$result = $controller->delete_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 999999,
					'signature' => 'abc',
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_invalid_signature', 403 );
	}

	/**
	 * A signature present in the subscription map must be accepted.
	 *
	 * @group Subscriptions
	 */
	public function test_delete_accepts_known_signature() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 10, 'dt_subscriptions', true ],
				'return' => [ md5( 'good-signature' ) => 55 ],
			]
		);

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 10 ],
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);

		$result = $controller->delete_item_permissions_check(
			$this->get_request(
				[
					'post_id'   => 10,
					'signature' => 'good-signature',
				]
			)
		);

		$this->assertTrue( $result );
	}

	/*
	 * ---------------------------------------------------------------------
	 * create_item_permissions_check()
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A caller without the generic creation capability must be rejected.
	 *
	 * @group Subscriptions
	 */
	public function test_create_rejects_without_capability() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_posts' ],
				'return' => false,
			]
		);

		$result = $controller->create_item_permissions_check(
			$this->get_request(
				[
					'post_id'    => 10,
					'target_url' => 'https://example.com/wp-json',
				]
			)
		);

		$this->assertRestError( $result, 'rest_cannot_create', 401 );
	}

	/**
	 * A caller holding only the generic capability must not be able to
	 * subscribe to a post they cannot edit. `create_posts` maps to
	 * `edit_posts` for this post type, which every contributor holds, and a
	 * subscription sends the post's full content to an arbitrary URL.
	 *
	 * @group Subscriptions
	 */
	public function test_create_rejects_post_the_user_cannot_edit() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_posts' ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 10 ],
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_post', 10 ],
				'return' => false,
			]
		);

		$result = $controller->create_item_permissions_check( $this->get_request( [ 'post_id' => 10 ] ) );

		$this->assertRestError( $result, 'rest_cannot_create', 401 );
	}

	/**
	 * An unknown post must be rejected.
	 *
	 * @group Subscriptions
	 */
	public function test_create_rejects_unknown_post() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_posts' ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 999999 ],
				'return' => null,
			]
		);

		$result = $controller->create_item_permissions_check( $this->get_request( [ 'post_id' => 999999 ] ) );

		$this->assertRestError( $result, 'rest_post_invalid_id', 404 );
	}

	/**
	 * A caller who can edit the post must be accepted, so the pull flow that
	 * registers a remote subscription still works.
	 *
	 * @group Subscriptions
	 */
	public function test_create_accepts_editable_post() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_posts' ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 10 ],
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'edit_post', 10 ],
				'return' => true,
			]
		);

		$result = $controller->create_item_permissions_check( $this->get_request( [ 'post_id' => 10 ] ) );

		$this->assertTrue( $result );
	}

	/**
	 * A target URL that is not an http(s) URL must be rejected before it is
	 * stored and later requested.
	 *
	 * @group Subscriptions
	 * @dataProvider data_create_rejects_invalid_target_url
	 *
	 * @param mixed $target_url Target URL under test.
	 */
	public function test_create_rejects_invalid_target_url( $target_url ) {
		$controller = $this->get_controller();

		\WP_Mock::userFunction( 'current_user_can', [ 'return' => true ] );
		\WP_Mock::userFunction(
			'get_post',
			[
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);
		\WP_Mock::userFunction( 'get_post_meta', [ 'return' => '' ] );

		$result = $controller->create_item(
			$this->get_request(
				[
					'post_id'        => 10,
					'remote_post_id' => 20,
					'signature'      => 'abc',
					'target_url'     => $target_url,
				]
			)
		);

		$this->assertRestError( $result, 'rest_subscription_invalid_target_url', 400 );
	}

	/**
	 * Target URLs that must not be accepted.
	 *
	 * @return array
	 */
	public function data_create_rejects_invalid_target_url() {
		return [
			'javascript scheme' => [ 'javascript:alert(1)' ],
			'file scheme'       => [ 'file:///etc/passwd' ],
			'no scheme'         => [ 'example.com/wp-json' ],
			'not a url'         => [ 'nonsense' ],
			'empty'             => [ '' ],
			'array'             => [ [ 'https://example.com' ] ],
		];
	}

	/*
	 * ---------------------------------------------------------------------
	 * receive_item()
	 * ---------------------------------------------------------------------
	 */

	/**
	 * A legitimate update must be applied, and taxonomy terms must be limited to
	 * taxonomies shown in the REST API.
	 *
	 * The restriction was previously applied only to the copy stored in
	 * `dt_subscription_update`, leaving `set_taxonomy_terms()` free to create
	 * terms in any taxonomy the caller named.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_item_applies_update_and_limits_taxonomies() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post',
			[
				'return' => new \WP_Post(
					(object) [
						'ID'        => 10,
						'post_type' => 'post',
					]
				),
			]
		);
		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 10, 'dt_original_post_id', true ],
				'return' => 5,
			]
		);
		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 10, 'dt_unlinked', true ],
				'return' => false,
			]
		);
		\WP_Mock::userFunction( 'distributor_get_registered_data', [ 'return' => [] ] );
		\WP_Mock::userFunction( 'Distributor\Utils\is_using_gutenberg', [ 'return' => false ] );
		\WP_Mock::userFunction( 'sanitize_text_field', [ 'return_arg' => 0 ] );
		\WP_Mock::userFunction( 'wp_kses_post', [ 'return_arg' => 0 ] );
		\WP_Mock::userFunction( 'wp_slash', [ 'return_arg' => 0 ] );
		\WP_Mock::userFunction( 'update_post_meta' );
		\WP_Mock::userFunction( 'delete_post_meta' );
		\WP_Mock::userFunction( 'Distributor\Utils\set_meta' );
		\WP_Mock::userFunction( 'Distributor\Utils\set_media' );

		// Only `category` is registered as visible in the REST API.
		\WP_Mock::userFunction(
			'get_taxonomies',
			[
				'return' => [ 'category' => 'category' ],
			]
		);

		// The hidden taxonomy must not reach the term writer.
		\WP_Mock::userFunction(
			'Distributor\Utils\set_taxonomy_terms',
			[
				'times' => 1,
				'args'  => [ 10, [ 'category' => [ 'news' ] ] ],
			]
		);

		\WP_Mock::userFunction(
			'wp_update_post',
			[
				'times' => 1,
				'args'  => [
					[
						'ID'           => 10,
						'post_title'   => 'New title',
						'post_content' => 'New content',
						'post_excerpt' => 'New excerpt',
						'post_name'    => 'new-slug',
					],
				],
			]
		);

		$result = $controller->receive_item(
			$this->get_request(
				[
					'post_id'   => 10,
					'post_data' => [
						'title'             => 'New title',
						'slug'              => 'new-slug',
						'excerpt'           => 'New excerpt',
						'content'           => 'New content',
						'distributor_terms' => [
							'category'   => [ 'news' ],
							'hidden_tax' => [ 'secret' ],
						],
					],
				]
			)
		);

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( [ 'updated' => true ], $result->get_data() );
	}

	/**
	 * A post that was never distributed must not be updated.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_item_rejects_undistributed_post() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 10 ],
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 10, 'dt_original_post_id', true ],
				'return' => '',
			]
		);

		$result = $controller->receive_item( $this->get_request( [ 'post_id' => 10 ] ) );

		$this->assertRestError( $result, 'rest_post_not_distributed', 400 );
	}

	/**
	 * A scalar `post_data` must be rejected. Indexing into a string raises a
	 * TypeError on PHP 8, which turned a malformed payload into a fatal error.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_item_rejects_non_array_post_data() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 10 ],
				'return' => new \WP_Post( (object) [ 'ID' => 10 ] ),
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args'   => [ 10, 'dt_original_post_id', true ],
				'return' => 5,
			]
		);

		$result = $controller->receive_item(
			$this->get_request(
				[
					'post_id'   => 10,
					'post_data' => 'not-an-array',
				]
			)
		);

		$this->assertRestError( $result, 'rest_post_no_data', 400 );
	}

	/**
	 * A deleted post must report as deleted rather than erroring.
	 *
	 * @group Subscriptions
	 */
	public function test_receive_item_reports_deleted_post() {
		$controller = $this->get_controller();

		\WP_Mock::userFunction(
			'get_post',
			[
				'args'   => [ 999999 ],
				'return' => null,
			]
		);

		$result = $controller->receive_item( $this->get_request( [ 'post_id' => 999999 ] ) );

		$this->assertInstanceOf( \WP_REST_Response::class, $result );
		$this->assertSame( 404, $result->get_status() );
	}
}
