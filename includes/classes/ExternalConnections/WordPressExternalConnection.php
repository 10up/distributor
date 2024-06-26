<?php
/**
 * WP external connection functionality
 *
 * @package  distributor
 */

namespace Distributor\ExternalConnections;

use Distributor\DistributorPost;
use \Distributor\ExternalConnection as ExternalConnection;
use \Distributor\Utils;

/**
 * Class handling WP external connections
 */
class WordPressExternalConnection extends ExternalConnection {

	/**
	 * Connection slug
	 *
	 * @var string
	 */
	public static $slug = 'wp';

	/**
	 * Connection pretty label
	 *
	 * This is to represent the authentication method,
	 * not the connection type. This value was previously
	 * "WordPress REST API".
	 *
	 * @since 1.4.0 Label as authentication method, not connection type
	 *
	 * @var string
	 */
	public static $label = 'Username / Password';

	/**
	 * Auth handler to use
	 *
	 * @var string
	 */
	public static $auth_handler_class = '\Distributor\Authentications\WordPressBasicAuth';

	/**
	 * REST API namespace
	 *
	 * @var string
	 */
	public static $namespace = 'wp/v2';

	/**
	 * Remote request timeout
	 *
	 * @var integer
	 */
	public static $timeout = 5;

	/**
	 * Default post type to pull.
	 *
	 * @var string
	 */
	public $pull_post_type;

	/**
	 * Default post types supported.
	 *
	 * @var string
	 */
	public $pull_post_types;

	/**
	 * This is a utility function for parsing annoying API link headers returned by the types endpoint
	 *
	 * @param  array $type Types array.
	 * @since  0.8
	 * @return string|bool
	 */
	private function parse_type_items_link( $type ) {
		try {
			if ( isset( $type['_links']['wp:items'][0]['href'] ) ) {
				$link = $type['_links']['wp:items'][0]['href'];
				return $link;
			}
		} catch ( \Exception $e ) {
			// Bummer
		}

		try {
			if ( isset( $type['_links']['https://api.w.org/items'][0]['href'] ) ) {
				$link = $type['_links']['https://api.w.org/items'][0]['href'];
				return $link;
			}
		} catch ( \Exception $e ) {
			// Even bigger bummer
		}

		return false;
	}

	/**
	 * Remotely get posts
	 *
	 * @param  array $args Remote get args.
	 * @since  0.8
	 * @return array|\WP_Post|\WP_Error
	 */
	public function remote_get( $args = array() ) {
		$id = ( empty( $args['id'] ) ) ? false : $args['id'];

		$query_args = array();

		$post_type = ( empty( $args['post_type'] ) ) ? 'post' : $args['post_type'];

		if ( empty( $id ) ) {
			$query_args['post_status'] = ( empty( $args['post_status'] ) ) ? 'any' : $args['post_status'];
			$posts_per_page            = ( empty( $args['posts_per_page'] ) ) ? get_option( 'posts_per_page' ) : $args['posts_per_page'];
			$query_args['page']        = ( empty( $args['paged'] ) ) ? 1 : $args['paged'];

			if ( isset( $args['post__in'] ) ) {
				// If post__in is empty, we can just stop right here
				if ( empty( $args['post__in'] ) ) {
					/**
					 * Filter the remote_get request.
					 *
					 * @since 1.0
					 * @hook dt_remote_get
					 *
					 * @param {array} $args  The arguments originally passed to `remote_get`.
					 * @param {object} $this The authentication class.
					 *
					 * @return {array} The arguments originally passed to `remote_get`.
					 */
					return apply_filters(
						'dt_remote_get',
						[
							'items'       => array(),
							'total_items' => 0,
						],
						$args,
						$this
					);
				}

				$query_args['include'] = $args['post__in'];
			} elseif ( isset( $args['post__not_in'] ) ) {
				// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				$query_args['exclude'] = $args['post__not_in'];
			}

			if ( ! empty( $args['s'] ) ) {
				$query_args['search'] = $args['s'];
			}

			if ( ! empty( $args['orderby'] ) ) {
				if ( 'post__in' === $args['orderby'] ) {
					$query_args['orderby'] = 'include';
				} else {
					$query_args['orderby'] = strtolower( $args['orderby'] );
				}
			}

			if ( ! empty( $args['order'] ) ) {
				$query_args['order'] = strtolower( $args['order'] );
			}
		}

		// When running a query for the Pull screen, make a POST request instead
		if ( empty( $id ) ) {
			$query_args['post_type']      = isset( $post_type ) ? $post_type : 'post';
			$query_args['posts_per_page'] = isset( $posts_per_page ) ? $posts_per_page : 20;

			$posts_response = $this->remote_post(
				untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/distributor/list-pull-content',
				$query_args
			);

			return $posts_response;
		}

		$query_args     = array(
			'include'   => absint( $id ),
			'post_type' => isset( $args['post_type'] ) ? $args['post_type'] : 'any',
		);
		$posts_response = $this->remote_post(
			untrailingslashit( $this->base_url ) . '/wp/v2/distributor/list-pull-content',
			$query_args
		);

		if ( is_wp_error( $posts_response ) ) {
			return $posts_response;
		}

		return $posts_response['items'][0];
	}

	/**
	 * Make a remote_post request.
	 *
	 * @param string $url Endpoint URL.
	 * @param array  $args Query arguments
	 * @return array|\WP_Error
	 */
	public function remote_post( $url = '', $args = array() ) {
		if ( ! $url ) {
			return new \WP_Error( 'endpoint-error', esc_html__( 'Endpoint URL must be set.', 'distributor' ) );
		}

		/**
		* Filter the remote_post query arguments
		*
		* @since 1.6.7
		* @hook dt_remote_post_query_args
		*
		* @param {array}  $args The request arguments.
		* @param {WordPressExternalConnection} $this The current connection object.
		*
		* @return {array} The query arguments.
		*/
		$body = apply_filters( 'dt_remote_post_query_args', $args, $this );

		// Add request parameter to specify Distributor request
		$body['distributor_request'] = '1';

		$request = wp_remote_post(
			$url,
			$this->auth_handler->format_post_args(
				array(
					/**
					 * Filter the timeout used when calling `remote_post`
					 *
					 * @since 1.6.7
					 * @hook dt_remote_post_timeout
					 *
					 * @param {int}   $timeout The timeout to use for the remote post. Default `45`.
					 * @param {array} $args    The request arguments.
					 *
					 * @return {int} The timeout to use for the remote_post call.
					 */
					'timeout' => apply_filters( 'dt_remote_post_timeout', 45, $args ),
					'body'    => $body,
				)
			)
		);

		if ( is_wp_error( $request ) ) {
			return $request;
		}

		$response_code = wp_remote_retrieve_response_code( $request );

		if ( 200 !== $response_code ) {
			if ( 404 === $response_code ) {
				return new \WP_Error( 'bad-endpoint', esc_html__( 'Could not connect to API endpoint.', 'distributor' ) );
			}

			$posts_body = json_decode( wp_remote_retrieve_body( $request ), true );

			$code    = empty( $posts_body['code'] ) ? 'endpoint-error' : esc_html( $posts_body['code'] );
			$message = empty( $posts_body['message'] ) ? esc_html__( 'API endpoint error.', 'distributor' ) : esc_html( $posts_body['message'] );

			return new \WP_Error( $code, $message );
		}

		$posts_body       = wp_remote_retrieve_body( $request );
		$response_headers = wp_remote_retrieve_headers( $request );

		if ( empty( $posts_body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty.', 'distributor' ) );
		}

		if (
			false === Utils\is_development_version()
			&& isset( $response_headers['x-distributor'] )
			&& (
				! isset( $response_headers['x-distributor-version'] )
				|| version_compare( $response_headers['x-distributor-version'], '2.0.0', '<' )
			)
		) {
			$version_error = new \WP_Error();
			$version_error->add(
				'old-distributor-version',
				esc_html__( 'Pulling content from external connections requires Distributor version 2.0.0 or later.', 'distributor' )
			);
			$version_error->add(
				'old-distributor-version',
				esc_html__( 'Please update Distributor on the site you are pulling content from.', 'distributor' )
			);
			return $version_error;
		}

		$posts           = json_decode( $posts_body, true );
		$formatted_posts = array();

		foreach ( $posts as $post ) {
			$post['full_connection'] = ! empty( $response_headers['X-Distributor'] );

			$formatted_posts[] = $this->to_wp_post( $post );
		}

		$total_posts = ! empty( $response_headers['X-WP-Total'] ) ? $response_headers['X-WP-Total'] : count( $formatted_posts );

		/**
		 * Filter the items returned when using `WordPressExternalConnection::remote_post`
		 *
		 * @since 1.6.7
		 * @hook dt_remote_post
		 *
		 * @param {array}                       $items The items returned from the POST request.
		 * @param {array}                       $args  The arguments used in the POST request.
		 * @param {WordPressExternalConnection} $this  The current connection object.
		 *
		 * @return {array} The items returned from a remote POST request.
		 */
		return apply_filters(
			'dt_remote_post',
			[
				'items'       => $formatted_posts,
				'total_items' => $total_posts,
			],
			$args,
			$this
		);
	}

	/**
	 * Pull items. Pass array of posts, each post should look like:
	 * [ 'remote_post_id' => POST ID TO GET, 'post_id' (optional) => POST ID TO MAP TO ]
	 *
	 * @param  array $items Posts to pull.
	 * @since  0.8
	 * @return array
	 */
	public function pull( $items ) {
		$created_posts = array();

		$remote_post_args = array(
			'include'   => array(),
			'post_type' => array(),
		);
		foreach ( $items as $item_array ) {
			$remote_post_args['include'][]   = $item_array['remote_post_id'];
			$remote_post_args['post_type'][] = $item_array['post_type'];
		}
		$remote_post_args['include']        = array_unique( $remote_post_args['include'] );
		$remote_post_args['post_type']      = array_unique( $remote_post_args['post_type'] );
		$remote_post_args['posts_per_page'] = count( $remote_post_args['include'] );

		// Get all remote posts in a single request.
		$remote_posts = $this->remote_post(
			untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/distributor/list-pull-content',
			$remote_post_args
		);

		if ( is_wp_error( $remote_posts ) ) {
			return $remote_posts;
		}

		foreach ( $items as $item_array ) {
			$post = wp_list_filter( $remote_posts['items'], array( 'ID' => $item_array['remote_post_id'] ) );
			if ( empty( $post ) ) {
				$created_posts[] = new \WP_Error( 'no-post', esc_html__( 'No post found.', 'distributor' ) );
				continue;
			}
			$post = reset( $post );

			$post_props = get_object_vars( $post );
			$post_array = array();

			foreach ( $post_props as $key => $value ) {
				$post_array[ $key ] = $value;
			}

			$update = false;
			// Unset data from remote site.
			unset( $post_array['ID'] );
			unset( $post_array['post_parent'] );
			unset( $post_array['post_date'] );
			unset( $post_array['post_date_gmt'] );
			unset( $post_array['post_modified'] );
			unset( $post_array['post_modified_gmt'] );
			unset( $post_array['post_author'] );

			if ( ! empty( $item_array['post_id'] ) ) {
				$update           = true;
				$post_array['ID'] = $item_array['post_id'];
			}

			if ( ! empty( $item_array['post_status'] ) ) {
				$post_array['post_status'] = $item_array['post_status'];
			}

			/**
			 * Filter the arguments passed into wp_insert_post during a pull.
			 *
			 * @since 1.0
			 * @hook dt_pull_post_args
			 *
			 * @param  {array}              $post_array      The post data to be inserted.
			 * @param  {array}              $remote_post_id  The remote post ID.
			 * @param  {object}             $post            The request that got the post.
			 * @param  {ExternalConnection} $this            The Distributor connection pulling the post.
			 *
			 * @return {array} The post data to be inserted.
			 */
			$new_post_args = Utils\post_args_allow_list( apply_filters( 'dt_pull_post_args', $post_array, $item_array['remote_post_id'], $post, $this ) );
			if ( $update ) {
				$new_post = wp_update_post( wp_slash( $new_post_args ) );
			} else {
				$new_post = wp_insert_post( wp_slash( $new_post_args ) );
			}

			update_post_meta( $new_post, 'dt_original_post_id', (int) $item_array['remote_post_id'] );
			update_post_meta( $new_post, 'dt_original_source_id', (int) $this->id );
			update_post_meta( $new_post, 'dt_syndicate_time', time() );
			update_post_meta( $new_post, 'dt_original_post_url', esc_url_raw( $post_array['link'] ) );
			update_post_meta( $new_post, 'dt_original_site_name', sanitize_text_field( $post_array['original_site_name'] ) );
			update_post_meta( $new_post, 'dt_original_site_url', sanitize_text_field( $post_array['original_site_url'] ) );

			if ( ! empty( $post->post_parent ) ) {
				update_post_meta( $new_post, 'dt_original_post_parent', (int) $post->post_parent );
			}

			if ( empty( $post_array['full_connection'] ) ) {
				update_post_meta( $new_post, 'dt_full_connection', false );
			} else {
				update_post_meta( $new_post, 'dt_full_connection', true );
			}

			if ( isset( $post_array['meta'] ) && is_array( $post_array['meta'] ) ) {
				// Filter documented in includes/classes/InternalConnections/NetworkSiteConnection.php.
				if ( apply_filters( 'dt_pull_post_meta', true, $new_post, $post_array['meta'], $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_meta( $new_post, $post_array['meta'] );
				}
			}

			if ( ! empty( $post_array['terms'] ) ) {
				// Filter documented in includes/classes/InternalConnections/NetworkSiteConnection.php.
				if ( apply_filters( 'dt_pull_post_terms', true, $new_post, $post_array['terms'], $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_taxonomy_terms( $new_post, $post_array['terms'] );
				}
			}

			if ( ! empty( $post_array['media'] ) ) {

				// Filter documented in includes/classes/InternalConnections/NetworkSiteConnection.php.
				if ( apply_filters( 'dt_pull_post_media', true, $new_post, $post_array['media'], $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_media( $new_post, $post_array['media'] );
				}
			}

			// Action documented in includes/classes/InternalConnections/NetworkSiteConnection.php.
			do_action( 'dt_pull_post', $new_post, $this, $post_array );

			$created_posts[] = $new_post;
		}

		return $created_posts;
	}

	/**
	 * Push a post to an external connection
	 *
	 * @param  int|WP_Post $post Post or Post ID to push. Required.
	 * @param  array       $args Post args to push. Optional.
	 * @since  0.8
	 * @return array|\WP_Error
	 */
	public function push( $post, $args = array() ) {
		if ( empty( $post ) ) {
			return new \WP_Error( 'no-push-post-id', esc_html__( 'Post ID required to push.', 'distributor' ) );
		}
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return new \WP_Error( 'invalid-push-post-id', esc_html__( 'Post does not exist.', 'distributor' ) );
		}


		$dt_post   = new DistributorPost( $post );
		$post_id   = $dt_post->post->ID;
		$post_type = $dt_post->get_post_type();
		$path      = self::$namespace;

		/*
		 * First let's get the actual route. We don't know the "plural" of our post type
		 */
		$types_path = untrailingslashit( $this->base_url ) . '/' . $path . '/types';

		$response = Utils\remote_http_request(
			$types_path,
			$this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty.', 'distributor' ) );
		}

		$body_array = json_decode( $body, true );

		$type_url = $this->parse_type_items_link( $body_array[ $post_type ] );

		if ( empty( $type_url ) ) {
			return new \WP_Error( 'no-push-post-type', esc_html__( 'Could not determine remote post type endpoint.', 'distributor' ) );
		}

		$signature = \Distributor\Subscriptions\generate_signature();

		/*
		 * Now let's push
		 */
		$rest_args = array(
			'status'                         => ( ! empty( $args['post_status'] ) ) ? $args['post_status'] : 'publish',
			'distributor_signature'          => $signature,
			'distributor_original_source_id' => $this->id,
		);
		$post_body = $dt_post->to_rest( $rest_args );

		// Map to remote ID if a push has already happened
		if ( ! empty( $args['remote_post_id'] ) ) {
			$existing_post_url = untrailingslashit( $type_url ) . '/' . $args['remote_post_id'];

			$post_exists_response = Utils\remote_http_request( $existing_post_url, $this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) ) );

			if ( ! is_wp_error( $post_exists_response ) ) {
				$post_exists_response_code = wp_remote_retrieve_response_code( $post_exists_response );

				if ( 200 === (int) $post_exists_response_code ) {
					$type_url = $existing_post_url;
				}
			}
		}

		$response = wp_remote_post(
			$type_url,
			$this->auth_handler->format_post_args(
				array(
					/**
					 * Filter the timeout used when calling `WordPressExternalConnection::push`.
					 *
					 * @since 1.0
					 * @hook dt_push_post_timeout
					 *
					 * @param {int} $timeout The timeout to use for the remote post. Default `5`.
					 * @param {object} $post The post object
					 *
					 * @return {int} The timeout to use for the remote post.
					 */
					'timeout' => apply_filters( 'dt_push_post_timeout', 45, $post ),
					/**
					 * Filter the arguments sent to the remote server during a push.
					 *
					 * @since 1.0
					 * @hook dt_push_post_args
					 * @tutorial snippets
					 *
					 * @param  {array}              $post_body  The request body to send.
					 * @param  {object}             $post       The WP_Post that is being pushed.
					 * @param  {array}              $args       Post args to push.
					 * @param  {ExternalConnection} $this       The distributor connection being pushed to.
					 *
					 * @return {array} The request body to send.
					 */
					'body'    => apply_filters( 'dt_push_post_args', $post_body, $post, $args, $this ),
				)
			)
		);

		// Action documented in includes/classes/InternalConnections/NetworkSiteConnection.php.
		do_action_deprecated(
			'dt_push_post',
			array( $response, $post_body, $type_url, $post_id, $args, $this ),
			'2.0.0',
			'dt_push_network_post|dt_push_external_post'
		);

		/**
		 * Fires the action after a post is pushed via Distributor before remote request validation.
		 *
		 * @since 2.0.0
		 * @hook  dt_push_external_post
		 *
		 * @param {array|WP_Error}              $response    The response from the remote request.
		 * @param {array}                       $post_body   The Post data formatted for the REST API endpoint.
		 * @param {string}                      $type_url    The Post type api endpoint.
		 * @param {int}                         $post_id     The Post id.
		 * @param {array}                       $args        The arguments passed into wp_insert_post.
		 * @param {WordPressExternalConnection} $this        The Distributor connection being pushed to.
		 */
		do_action( 'dt_push_external_post', $response, $post_body, $type_url, $post_id, $args, $this );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty.', 'distributor' ) );
		}

		$body_array = json_decode( $body, true );

		if ( empty( $body_array['id'] ) ) {
			return new \WP_Error( 'no-push-post-remote-id', esc_html__( 'Could not determine remote post ID.', 'distributor' ) );
		}

		$response_headers = wp_remote_retrieve_headers( $response );

		if ( ! empty( $response_headers['X-Distributor'] ) ) {
			// We have Distributor on the other side
			\Distributor\Subscriptions\create_subscription( $post_id, $body_array['id'], untrailingslashit( $this->base_url ), $signature );
		}

		$remote_post = array(
			'id' => $body_array['id'],
		);

		if ( ! empty( $body_array['push-errors'] ) ) {
			$remote_post['push-errors'] = $body_array['push-errors'];
		}

		return $remote_post;
	}

	/**
	 * Get the available post types.
	 *
	 * @since 1.3
	 * @return array|\WP_Error
	 */
	public function get_post_types() {
		$path = self::$namespace;

		$types_path = untrailingslashit( $this->base_url ) . '/' . $path . '/types';

		$types_response = Utils\remote_http_request(
			$types_path,
			$this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) )
		);

		if ( is_wp_error( $types_response ) ) {
			return $types_response;
		}

		if ( 404 === wp_remote_retrieve_response_code( $types_response ) ) {
			return new \WP_Error( 'bad-endpoint', esc_html__( 'Could not connect to API endpoint.', 'distributor' ) );
		}

		$types_body = wp_remote_retrieve_body( $types_response );

		if ( empty( $types_body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty.', 'distributor' ) );
		}

		$types_body_array = json_decode( $types_body, true );

		return $types_body_array;
	}

	/**
	 * Check what we can do with a given external connection (push or pull)
	 *
	 * @since  0.8
	 * @return array
	 */
	public function check_connections() {
		$output = array(
			'errors'              => array(),
			'can_post'            => array(),
			'can_get'             => array(),
			'endpoint_suggestion' => false,
		);

		$remote_request_url = untrailingslashit( $this->base_url );
		if ( str_ends_with( $remote_request_url, '?rest_route=' ) ) {
			// It's a request to get the REST API index using plain permalinks and needs a trailing slash.
			$remote_request_url = trailingslashit( $remote_request_url );
		}

		$response = Utils\remote_http_request( $remote_request_url, $this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) ) );

		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) || empty( $body ) ) {
			$output['errors']['no_external_connection'] = 'no_external_connection';
			return $output;
		}

		$data = json_decode( $body, true );

		if ( empty( $data ) ) {
			$output['errors']['no_external_connection'] = 'no_external_connection';
		}

		$response_headers = wp_remote_retrieve_headers( $response );
		$link_headers     = (array) $response_headers['Link'];
		$correct_endpoint = false;

		foreach ( $link_headers as $link_header ) {
			if ( strpos( $link_header, 'rel="https://api.w.org/"' ) !== false ) {
				$correct_endpoint = preg_replace( '#.*<([^>]+)>.*#', '$1', $link_header );
			}
		}

		if ( ! empty( $correct_endpoint ) && untrailingslashit( $this->base_url ) !== untrailingslashit( $correct_endpoint ) ) {
			$output['errors']['no_external_connection'] = 'no_external_connection';
			$output['endpoint_suggestion']              = untrailingslashit( $correct_endpoint );
		}

		if ( empty( $data['routes'] ) && empty( $output['errors']['no_external_connection'] ) ) {
			$output['errors']['no_types'] = 'no_types';
		}

		if ( ! empty( $output['errors'] ) ) {
			return $output;
		}

		if ( empty( $response_headers['X-Distributor'] ) ) {
			$output['errors']['no_distributor'] = 'no_distributor';
		}

		$types_path = untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/types';

		$types_response = Utils\remote_http_request( $types_path, $this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) ) );

		$types_body = wp_remote_retrieve_body( $types_response );
		$types      = json_decode( $types_body, true );

		if ( is_wp_error( $types_response ) || 200 !== wp_remote_retrieve_response_code( $types_response ) || empty( $types_body ) || empty( $types ) ) {
			$output['errors']['no_types'] = 'no_types';
		} else {
			$can_get  = array();
			$can_post = array();

			$permission_url      = untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/distributor/post-types-permissions';
			$permission_response = Utils\remote_http_request(
				$permission_url,
				$this->auth_handler->format_get_args(
					array(
						'timeout' => self::$timeout,
					)
				)
			);

			$permissions = json_decode( wp_remote_retrieve_body( $permission_response ) );

			$output['is_authenticated'] = isset( $permissions->is_authenticated ) ? $permissions->is_authenticated : 'na';

			if (
				is_wp_error( $permission_response )
				|| empty( $permissions )
				|| ! isset( $permissions->can_get )
				|| ! isset( $permissions->can_post )
			) {
				$output['errors']['no_permissions'] = 'no_permissions';
			} else {
				$can_get = array_values(
					array_filter(
						$permissions->can_get,
						[ $this, 'not_distributor_internal_post_type' ]
					)
				);

				$can_post = array_values(
					array_filter(
						$permissions->can_post,
						[ $this, 'not_distributor_internal_post_type' ]
					)
				);
			}

			$output['can_get']  = $can_get;
			$output['can_post'] = $can_post;
		}

		return $output;
	}


	/**
	 * Whether if the post type is not distributor internal post type.
	 *
	 * @param string $post_type Post type
	 *
	 * @return bool
	 */
	private function not_distributor_internal_post_type( $post_type ) {
		return 'dt_subscription' !== $post_type;
	}


	/**
	 * Convert array to WP_Post object suitable for insert/update.
	 *
	 * Some field names in the REST API response object do not match DB field names.
	 *
	 * @see    \WP_REST_Posts_Controller::prepare_item_for_database()
	 * @param  array $post Post as array.
	 * @since  0.8
	 * @return \WP_Post
	 */
	private function to_wp_post( $post ) {
		$obj = (object) $post;

		/*
		 * These will only be set if Distributor is active on the other side
		 */
		$obj->original_site_name = ( ! empty( $post['distributor_original_site_name'] ) ) ? $post['distributor_original_site_name'] : null;
		$obj->original_site_url  = ( ! empty( $post['distributor_original_site_url'] ) ) ? $post['distributor_original_site_url'] : null;

		// Unset these as they are renamed above.
		unset( $obj->distributor_original_site_name );
		unset( $obj->distributor_original_site_url );


		/**
		 * Filter the post item.
		 *
		 * @since 1.0
		 * @hook dt_item_mapping
		 *
		 * @param  {WP_Post}            $obj  The WP_Post that is being pushed.
		 * @param  {ExternalConnection} $this The external connection the post concerns.
		 *
		 * @return {WP_Post} The WP_Post that is being pushed.
		 */
		$post_object = apply_filters( 'dt_item_mapping', new \WP_Post( $obj ), $post, $this );

		return $post_object;
	}

	/**
	 * Setup actions and filters that are need on every page load
	 *
	 * @since 1.0
	 */
	public static function bootstrap() {
	}

	/**
	 * Setup canonicalization on front end
	 *
	 * @since  1.0
	 * @deprecated 2.0.0
	 */
	public static function canonicalize_front_end() {
		_deprecated_function( __METHOD__, '2.0.0' );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\filter_author_link instead.
	 *
	 * @param  string $link Author link.
	 * @param  int    $author_id Author ID.
	 * @param  string $author_nicename Author name.
	 * @return string
	 */
	public static function author_posts_url_distributed( $link, $author_id, $author_nicename ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\filter_author_link' );
		return \Distributor\Hooks\filter_author_link( $link );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\filter_the_author instead.
	 *
	 * @param  string $author Author name.
	 * @return string
	 */
	public static function the_author_distributed( $author ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\filter_the_author' );
		return \Distributor\Hooks\filter_the_author( $author );
	}

	/**
	 * Make sure canonical url header is outputted
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\get_canonical_url instead.
	 *
	 * @param  string $canonical_url Canonical URL.
	 * @param  object $post Post object.
	 * @return string
	 */
	public static function canonical_url( $canonical_url, $post ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\get_canonical_url' );
		return \Distributor\Hooks\get_canonical_url( $canonical_url, $post );
	}

	/**
	 * Handles the canonical URL change for distributed content when Yoast SEO is in use
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\wpseo_canonical instead.
	 *
	 * @param string $canonical_url The Yoast WPSEO deduced canonical URL
	 * @return string $canonical_url The updated distributor friendly URL
	 */
	public static function wpseo_canonical_url( $canonical_url ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\wpseo_canonical' );
		$presentation = false;
		if ( is_singular() ) {
			$source       = get_post();
			$presentation = (object) array( 'source' => $source );
		}
		return \Distributor\Hooks\wpseo_canonical( $canonical_url, $presentation );
	}

	/**
	 * Handles the og:url change for distributed content when Yoast SEO is in use
	 *
	 * @deprecated 2.0.0 Use Distributor\Hooks\wpseo_opengraph_url instead.
	 *
	 * @param string $og_url The Yoast WPSEO deduced OG URL which is a result of wpseo_canonical_url
	 * @return string $og_url The updated distributor friendly URL
	 */
	public static function wpseo_opengraph_url( $og_url ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\wpseo_opengraph_url' );
		$presentation = false;
		if ( is_singular() ) {
			$source       = get_post();
			$presentation = (object) array( 'source' => $source );
		}
		return \Distributor\Hooks\wpseo_opengraph_url( $og_url, $presentation );
	}
}
