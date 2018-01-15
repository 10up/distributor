<?php

namespace Distributor\API;

class SubscriptionsController extends \WP_REST_Controller {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type;

	/**
	 * Register controller
	 *
	 * @since 1.0
	 * @param string $post_type Post type.
	 */
	public function __construct( $post_type ) {
		$this->post_type = $post_type;
		$this->namespace = 'wp/v2';
		$obj             = get_post_type_object( $post_type );
		$this->rest_base = ! empty( $obj->rest_base ) ? $obj->rest_base : $obj->name;

		$this->meta = new \WP_REST_Post_Meta_Fields( $this->post_type );
	}

	/**
	 * Register subscription routes
	 *
	 * @since 1.0
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace, '/' . $this->rest_base, array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => array(
						'post_id'        => array(
							'required'    => true,
							'description' => esc_html__( 'Post that is being subscribed to.', 'distributor' ),
							'type'        => 'integer',
						),
						'remote_post_id' => array(
							'required'    => true,
							'description' => esc_html__( 'Post on remote site that maps to subscription post.', 'distributor' ),
							'type'        => 'integer',
						),
						'target_url'     => array(
							'required'    => true,
							'description' => esc_html__( 'WordPress URL to notify.', 'distributor' ),
							'type'        => 'string',
						),
						'signature'      => array(
							'required'    => true,
							'description' => esc_html__( 'Subscription signature for post.', 'distributor' ),
							'type'        => 'string',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/receive', array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'receive_item' ),
					'permission_callback' => array( $this, 'receive_item_permissions_check' ),
					'args'                => [
						'post_id'   => array(
							'required'    => true,
							'description' => esc_html__( 'Post to be updated.', 'distributor' ),
							'type'        => 'integer',
						),
						'signature' => array(
							'required'    => true,
							'description' => esc_html__( 'Signature for given signature', 'distributor' ),
							'type'        => 'string',
						),
					],
				),
			)
		);

		register_rest_route(
			$this->namespace, '/' . $this->rest_base . '/delete', array(
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
					'args'                => [
						'post_id'   => array(
							'required'    => true,
							'description' => esc_html__( 'Post with subscription.', 'distributor' ),
							'type'        => 'integer',
						),
						'signature' => array(
							'required'    => true,
							'description' => esc_html__( 'Signature for given subscription', 'distributor' ),
							'type'        => 'string',
						),
					],
				),
			)
		);
	}

	/**
	 * Determine if receive endpoint permissions are correct by checking the signature
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return true|\WP_Error True if the request has receive access, \WP_Error object otherwise.
	 */
	public function receive_item_permissions_check( $request ) {

		if ( ! empty( $request['signature'] ) && ! empty( $request['post_id'] ) ) {
			$signature = get_post_meta( $request['post_id'], 'dt_subscription_signature', true );

			if ( $request['signature'] === $signature ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Receive a subscription update. We could just push using the existing REST API. However, in the scenario where
	 * we are receiving an update from a pulled post, we wouldn't have access to push since source connections are one-way
	 * intentionally.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return WP_REST_Response|\WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function receive_item( $request ) {
		if ( (int) $request['post_id'] <= 0 ) {
			return new \WP_Error( 'rest_post_no_post_id', esc_html__( 'Missing post id.', 'distributor' ), array( 'status' => 400 ) );
		}

		$post = get_post( (int) $request['post_id'] );
		if ( empty( $post ) ) {
			return new \WP_REST_Response( null, 404, [ 'X-Distributor-Post-Deleted' => 'yes' ] );
		}

		$original_post_id = get_post_meta( $request['post_id'], 'dt_original_post_id', true );

		if ( empty( $original_post_id ) ) {
			return new \WP_Error( 'rest_post_not_distributed', esc_html__( 'Post not distributed.', 'distributor' ), array( 'status' => 400 ) );
		}

		// This endpoint updates post data and unlinks posts
		if ( isset( $request['original_deleted'] ) ) {
			update_post_meta( $request['post_id'], 'dt_original_post_deleted', true );

			$response = new \WP_REST_Response();
			$response->set_data( array( 'updated' => true ) );

			return $response;
		} else {
			if ( empty( $request['post_data'] ) ) {
				return new \WP_Error( 'rest_post_no_data', esc_html__( 'No post data for update.', 'distributor' ), array( 'status' => 400 ) );
			}

			/**
			 * We save the update in meta in case the post is unlinked. If the post is re-linked, we'll
			 * apply the update
			 */
			$update = [
				'post_title'   => sanitize_text_field( $request['post_data']['title'] ),
				'post_name'    => sanitize_text_field( $request['post_data']['slug'] ),
				'post_content' => wp_kses_post( $request['post_data']['content'] ),
				'post_excerpt' => wp_kses_post( $request['post_data']['excerpt'] ),
				// Todo: how do we properly sanitize this?
				'meta'         => ( isset( $request['post_data']['distributor_meta'] ) ) ? $request['post_data']['distributor_meta'] : [],
				'terms'        => ( isset( $request['post_data']['distributor_terms'] ) ) ? $request['post_data']['distributor_terms'] : [],
				'media'        => ( isset( $request['post_data']['distributor_media'] ) ) ? $request['post_data']['distributor_media'] : [],
			];

			update_post_meta( (int) $request['post_id'], 'dt_subscription_update', $update );

			$unlinked = (bool) get_post_meta( $request['post_id'], 'dt_unlinked', true );

			if ( ! empty( $unlinked ) ) {
				$response = new \WP_REST_Response();
				$response->set_data( array( 'updated' => false ) );

				return $response;
			}

			wp_update_post(
				[
					'ID'           => $request['post_id'],
					'post_title'   => $request['post_data']['title'],
					'post_content' => $request['post_data']['content'],
					'post_excerpt' => $request['post_data']['excerpt'],
					'post_name'    => $request['post_data']['slug'],
				]
			);

			\Distributor\Utils\set_meta( $request['post_id'], $request['post_data']['distributor_meta'] );
			\Distributor\Utils\set_taxonomy_terms( $request['post_id'], $request['post_data']['distributor_terms'] );
			\Distributor\Utils\set_media( $request['post_id'], $request['post_data']['distributor_media'] );

			$response = new \WP_REST_Response();
			$response->set_data( array( 'updated' => true ) );

			return $response;
		}
	}

	/**
	 * Helper function to build response array for a subscription
	 *
	 * @param  int $post_id
	 * @since  1.0
	 */
	protected function get_response_array( $post_id ) {
		return array(
			'id'             => (int) $post_id,
			'post_id'        => (int) get_post_meta( $post_id, 'dt_subscription_post_id', true ),
			'remote_post_id' => (int) get_post_meta( $post_id, 'dt_subscription_remote_post_id', true ),
			'target_url'     => esc_url_raw( get_post_meta( $post_id, 'dt_subscription_target_url', true ) ),
		);
	}

	/**
	 * Ensure user has permissions to create a subscription
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return true|\WP_Error True if the request has access to create items, \WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		$post_type = get_post_type_object( $this->post_type );

		if ( ! current_user_can( $post_type->cap->create_posts ) ) {
			return new \WP_Error( 'rest_cannot_create', esc_html__( 'Sorry, you are not allowed to create subscriptions.', 'distributor' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Create a subscription
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return WP_REST_Response|\WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new \WP_Error( 'rest_subscription_exists', esc_html__( 'Cannot create existing subscription.', 'distributor' ), array( 'status' => 400 ) );
		}

		if ( empty( $request['post_id'] ) ) {
			return new \WP_Error( 'rest_subscription_post_missing', esc_html__( 'Subscription post does not exist.', 'distributor' ), array( 'status' => 400 ) );
		}

		$post_id = \Distributor\Subscriptions\create_subscription( $request['post_id'], $request['remote_post_id'], $request['target_url'], $request['signature'] );

		$response = rest_ensure_response( $this->get_response_array( $post_id ) );

		$response->set_status( 201 );

		return $response;
	}


	/**
	 * Ensure user has permissions to delete a subscription
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return true|\WP_Error True if the request has access to delete the item, \WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		$post = get_post( $request['post_id'] );

		if ( empty( $post ) ) {
			return new \WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid post ID.', 'distributor' ), array( 'status' => 404 ) );
		}

		$subscriptions = get_post_meta( $request['post_id'], 'dt_subscriptions', true );

		if ( empty( $subscriptions[ md5( $request['signature'] ) ] ) ) {
			return false;
		}

		if ( $request['signature'] !== get_post_meta( $subscriptions[ md5( $request['signature'] ) ], 'dt_subscription_signature', true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Delete a subscription
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return WP_REST_Response|\WP_Error Response object on success, or \WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$post = get_post( $request['post_id'] );

		if ( empty( $post ) ) {
			return new \WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid post ID.', 'distributor' ), array( 'status' => 404 ) );
		}

		\Distributor\Subscriptions\delete_subscription( $request['post_id'], $request['signature'] );

		$response = new \WP_REST_Response();
		$response->set_data( array( 'deleted' => true ) );

		return $response;
	}
}
