<?php
/**
 * Subscription REST API endpoint
 *
 * @package  distributor
 */

namespace Distributor\API;

use Distributor\RegisteredDataHandler;

/**
 * Subscription controller REST API class
 */
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
			$this->namespace,
			'/' . $this->rest_base,
			array(
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
			$this->namespace,
			'/' . $this->rest_base . '/receive',
			array(
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
			$this->namespace,
			'/' . $this->rest_base . '/delete',
			array(
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
	 * Authenticate the request via the signature if available.
	 *
	 * @deprecated 2.3.1 Subscription signatures are verified in the endpoint
	 *                   permission callbacks instead.
	 *
	 * @param  WP_Error|null|bool $status The authentication status.
	 *
	 * @return WP_Error|null|bool The unmodified authentication status.
	 */
	public function dt_verify_signature_authentication( $status ) {
		_deprecated_function( __METHOD__, '2.3.1', 'Distributor\\API\\SubscriptionsController::verify_subscription_signature()' );

		return $status;
	}

	/**
	 * Verify the subscription signature sent with a request against the
	 * signature stored on the post being updated.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  2.3.1
	 * @return true|\WP_Error True if the signature is valid, \WP_Error object otherwise.
	 */
	protected function verify_subscription_signature( $request ) {
		$post_id = (int) $request['post_id'];

		if ( empty( $post_id ) ) {
			return new \WP_Error( 'rest_post_invalid_post_id', esc_html__( 'Invalid post id.', 'distributor' ), array( 'status' => 400 ) );
		}

		$signature = $request['signature'];

		// Should already be verified as a string but checking again just in case.
		if ( ! is_string( $signature ) || '' === trim( $signature ) ) {
			return new \WP_Error( 'rest_post_invalid_signature', esc_html__( 'Signature invalid or missing.', 'distributor' ), array( 'status' => 403 ) );
		}

		$stored_signature = get_post_meta( $post_id, 'dt_subscription_signature', true );

		// An empty stored signature cannot match, as the value checked above is never empty.
		if ( ! is_string( $stored_signature ) || ! hash_equals( $stored_signature, $signature ) ) {
			return new \WP_Error( 'rest_post_invalid_signature', esc_html__( 'Signature invalid or missing.', 'distributor' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Determine if receive endpoint permissions are correct.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @since  1.0
	 * @return true|\WP_Error True if the request has receive access, \WP_Error object otherwise.
	 */
	public function receive_item_permissions_check( $request ) {
		return $this->verify_subscription_signature( $request );
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
			if ( empty( $request['post_data'] ) || ! is_array( $request['post_data'] ) ) {
				return new \WP_Error( 'rest_post_no_data', esc_html__( 'No post data for update.', 'distributor' ), array( 'status' => 400 ) );
			}

			// Process registered custom data.
			$registered_data = distributor_get_registered_data();
			if ( ! empty( $registered_data ) ) {
				$connection_data = array(
					'connection_type'           => 'external',
					'connection_direction'      => 'pull',
					'connection_id'             => get_post_meta( (int) $request['post_id'], 'dt_original_source_id', true ),
					'subscription_notification' => true,
				);

				$registered_data_handler = new RegisteredDataHandler( $connection_data );
				$request['post_data']    = $registered_data_handler->process_registered_data( $request['post_data'], true );
			}

			$post_data = $request['post_data'];

			// Normalise the incoming fields up front.
			$title   = isset( $post_data['title'] ) && is_string( $post_data['title'] ) ? $post_data['title'] : '';
			$slug    = isset( $post_data['slug'] ) && is_string( $post_data['slug'] ) ? $post_data['slug'] : '';
			$excerpt = isset( $post_data['excerpt'] ) && is_string( $post_data['excerpt'] ) ? $post_data['excerpt'] : '';
			$meta    = isset( $post_data['distributor_meta'] ) && is_array( $post_data['distributor_meta'] ) ? $post_data['distributor_meta'] : [];
			$terms   = isset( $post_data['distributor_terms'] ) && is_array( $post_data['distributor_terms'] ) ? $post_data['distributor_terms'] : [];
			$media   = isset( $post_data['distributor_media'] ) && is_array( $post_data['distributor_media'] ) ? $post_data['distributor_media'] : [];

			// Limit taxonomy updates to those shown in the REST API.
			$rest_taxonomies = array_fill_keys( get_taxonomies( [ 'show_in_rest' => true ] ), true );
			$terms           = array_intersect_key( $terms, $rest_taxonomies );

			// When both sides of a subscription connection support Gutenberg, update with the raw content.
			$content                 = isset( $post_data['content'] ) && is_string( $post_data['content'] ) ? $post_data['content'] : '';
			$suspend_content_filters = false;

			if ( \Distributor\Utils\is_using_gutenberg( $post ) && isset( $post_data['distributor_raw_content'] ) && is_string( $post_data['distributor_raw_content'] ) ) {
				if ( \Distributor\Utils\dt_use_block_editor_for_post_type( $post->post_type ) ) {
					$content = $post_data['distributor_raw_content'];

					// Raw block content is stored verbatim. The filters that would alter
					// it are suspended around the update itself, further down.
					$suspend_content_filters = true;
				}
			}

			/**
			 * We save the update in meta in case the post is unlinked. If the post is re-linked, we'll
			 * apply the update
			 */
			$update = [
				'post_title'   => sanitize_text_field( $title ),
				'post_name'    => sanitize_text_field( $slug ),
				'post_content' => wp_kses_post( $content ),
				'post_excerpt' => wp_kses_post( $excerpt ),
				// Todo: how do we properly sanitize this?
				'meta'         => $meta,
				'terms'        => $terms,
				'media'        => $media,
			];

			update_post_meta( (int) $request['post_id'], 'dt_subscription_update', $update );

			$unlinked = (bool) get_post_meta( $request['post_id'], 'dt_unlinked', true );

			if ( ! empty( $unlinked ) ) {
				$response = new \WP_REST_Response();
				$response->set_data( array( 'updated' => false ) );

				return $response;
			}

			/*
			 * Suspend the `content_save_pre` filters for the duration of the update so
			 * raw block content is stored verbatim.
			 */
			$suspended_content_filters = null;

			if ( $suspend_content_filters && isset( $GLOBALS['wp_filter']['content_save_pre'] ) ) {
				$suspended_content_filters = $GLOBALS['wp_filter']['content_save_pre'];

				unset( $GLOBALS['wp_filter']['content_save_pre'] );
			}

			try {
				wp_update_post(
					wp_slash(
						[
							'ID'           => $request['post_id'],
							'post_title'   => $title,
							'post_content' => $content,
							'post_excerpt' => $excerpt,
							'post_name'    => $slug,
						]
					)
				);
			} finally {
				// Restored even if a save hook throws, so kses cannot stay disabled.
				if ( null !== $suspended_content_filters ) {
					$GLOBALS['wp_filter']['content_save_pre'] = $suspended_content_filters;
				}
			}

			/**
			 * We check if each of these exist since the API removes empty arrays from requests
			 */
			if ( ! empty( $meta ) ) {
				\Distributor\Utils\set_meta( $request['post_id'], $meta );
			}

			if ( ! empty( $terms ) ) {
				\Distributor\Utils\set_taxonomy_terms( $request['post_id'], $terms );
			}

			if ( ! empty( $media ) ) {
				\Distributor\Utils\set_media( $request['post_id'], $media );
			} else {
				// Remove any previously set featured image.
				delete_post_meta( (int) $request['post_id'], '_thumbnail_id' );
			}

			/**
			 * Action fired after receiving a subscription update from Distributor
			 *
			 * @since 1.3.8
			 *
			 * @param WP_Post         $post    Updated post object.
			 * @param WP_REST_Request $request Request object.
			 */
			do_action( 'dt_process_subscription_attributes', $post, $request );

			$response = new \WP_REST_Response();
			$response->set_data( array( 'updated' => true ) );

			return $response;
		}
	}

	/**
	 * Helper function to build response array for a subscription
	 *
	 * @param  int $post_id Post ID.
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
	 * Ensure user has permissions to create a subscription.
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

		$post_id = (int) $request['post_id'];
		$post    = empty( $post_id ) ? null : get_post( $post_id );

		if ( empty( $post ) ) {
			return new \WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid post ID.', 'distributor' ), array( 'status' => 404 ) );
		}

		/*
		 * We already check `create_posts` above but a subscription sends the full content,
		 * meta, terms and media on every update, so we want the caller to be able to edit the
		 * specific post being subscribed to, not merely to hold a generic editing capability.
		 */
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return new \WP_Error( 'rest_cannot_create', esc_html__( 'Sorry, you are not allowed to create subscriptions for this post.', 'distributor' ), array( 'status' => rest_authorization_required_code() ) );
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

		$target_url  = is_string( $request['target_url'] ) ? $request['target_url'] : '';
		$target_host = wp_parse_url( $target_url, PHP_URL_HOST );

		if ( empty( $target_host ) || ! in_array( wp_parse_url( $target_url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) {
			return new \WP_Error( 'rest_subscription_invalid_target_url', esc_html__( 'Invalid target URL.', 'distributor' ), array( 'status' => 400 ) );
		}

		$post_id = \Distributor\Subscriptions\create_subscription( $request['post_id'], $request['remote_post_id'], $target_url, $request['signature'] );

		/**
		 * We need to make sure this post shows up as "distributed"
		 */
		$connection_map = get_post_meta( $request['post_id'], 'dt_connection_map', true );

		if ( empty( $connection_map ) ) {
			$connection_map = [
				'internal' => [],
				'external' => [],
			];
		}

		if ( empty( $connection_map['external'] ) ) {
			$connection_map['external'] = [];
		}

		/**
		 * We don't know the external connection ID
		 *
		 * @Todo: Find a way around this
		 */
		$connection_map['external'][-1] = [
			'post_id' => (int) $request['remote_post_id'],
			'time'    => time(),
		];

		update_post_meta( $request['post_id'], 'dt_connection_map', $connection_map );

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
		// Should already be verified as a string but checking again just in case.
		if ( ! is_string( $request['signature'] ) || '' === trim( $request['signature'] ) ) {
			return new \WP_Error( 'rest_post_invalid_signature', esc_html__( 'Signature invalid or missing.', 'distributor' ), array( 'status' => 403 ) );
		}

		$subscriptions = get_post_meta( $request['post_id'], 'dt_subscriptions', true );

		if ( ! is_array( $subscriptions ) || empty( $subscriptions[ md5( $request['signature'] ) ] ) ) {
			return new \WP_Error( 'rest_post_invalid_signature', esc_html__( 'Signature invalid or missing.', 'distributor' ), array( 'status' => 403 ) );
		}

		// Only reachable with a valid signature, so the post ID can safely be confirmed.
		$post = get_post( $request['post_id'] );

		if ( empty( $post ) ) {
			return new \WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid post ID.', 'distributor' ), array( 'status' => 404 ) );
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
