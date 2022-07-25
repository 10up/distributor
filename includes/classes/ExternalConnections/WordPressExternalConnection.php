<?php
/**
 * WP external connection functionality
 *
 * @package  distributor
 */

namespace Distributor\ExternalConnections;

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

		// When running a query for the Pull screen with excluded items, make a POST request instead
		if ( empty( $id ) && isset( $args['post__not_in'] ) && isset( $args['dt_pull_list'] ) ) {
			$query_args['post_type']      = isset( $post_type ) ? $post_type : 'post';
			$query_args['posts_per_page'] = isset( $posts_per_page ) ? $posts_per_page : 20;

			$posts_response = $this->remote_post(
				untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/distributor/list-pull-content',
				$query_args
			);

			return $posts_response;
		}

		static $types_urls;
		$types_urls = array();

		if ( empty( $types_urls[ $post_type ] ) ) {
			/**
			 * First let's get the actual route if not cached. We don't know the "plural" of our post type
			 */

			/**
			 * Todo: This should be cached in a transient
			 */

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
				return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
			}

			$types_body_array = json_decode( $types_body, true );

			if ( empty( $types_body_array ) || empty( $types_body_array[ $post_type ] ) ) {
				return new \WP_Error( 'no-pull-post-type', esc_html__( 'Could not determine remote post type endpoint', 'distributor' ) );
			}

			$types_urls[ $post_type ] = $this->parse_type_items_link( $types_body_array[ $post_type ] );

			if ( empty( $types_urls[ $post_type ] ) ) {
				return new \WP_Error( 'no-pull-post-type', esc_html__( 'Could not determine remote post type endpoint', 'distributor' ) );
			}
		}

		$args_str = '';

		if ( ! empty( $posts_per_page ) ) {
			$args_str .= 'per_page=' . (int) $posts_per_page;
		}

		/**
		 * Filter the remote_get query arguments
		 *
		 * @since 1.0
		 * @hook dt_remote_get_query_args
		 *
		 * @param  {array}  $query_args The existing query arguments.
		 * @param  {array}  $args       The arguments originally passed to `remote_get`.
		 * @param  {object} $this       The authentication class.
		 *
		 * @return {array} The existing query arguments.
		 */
		$query_args = apply_filters( 'dt_remote_get_query_args', $query_args, $args, $this );

		foreach ( $query_args as $arg_key => $arg_value ) {
			if ( is_array( $arg_value ) ) {
				foreach ( $arg_value as $arg_value_value ) {
					if ( ! empty( $args_str ) ) {
						$args_str .= '&';
					}

					$args_str .= $arg_key . '[]=' . $arg_value_value;
				}
			} else {
				if ( ! empty( $args_str ) ) {
					$args_str .= '&';
				}

				$args_str .= $arg_key . '=' . $arg_value;
			}
		}

		$context = 'view';

		$prelim_get_args = $this->auth_handler->format_get_args();

		/**
		 * See if we are trying to authenticate
		 */
		if ( ! empty( $prelim_get_args ) && ! empty( $prelim_get_args['headers'] ) && ! empty( $prelim_get_args['headers']['Authorization'] ) ) {
			$context = 'edit';

			if ( ! empty( $args_str ) ) {
				$args_str .= '&';
			}

			$args_str .= 'context=edit';
		}

		if ( ! empty( $id ) ) {
			$posts_url = untrailingslashit( $types_urls[ $post_type ] ) . '/' . $id . '/?context=' . $context;
		} else {
			$posts_url = untrailingslashit( $types_urls[ $post_type ] ) . '/?' . $args_str;
		}

		// Add request parameter to specify Distributor request
		$posts_url = add_query_arg( 'distributor_request', '1', $posts_url );

		$posts_response = Utils\remote_http_request(
			/**
			 * Filter the URL that remote_get will use
			 *
			 * @since 1.0
			 * @hook dt_remote_get_url
			 *
			 * @param  {string} $posts_url  The posts URL
			 * @param  {string} $args       The arguments originally passed to `remote_get`.
			 * @param  {object} $this       The authentication class.
			 *
			 * @return {string} The posts URL.
			 */
			apply_filters( 'dt_remote_get_url', $posts_url, $args, $this ),
			// phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout -- false positive, shorter on VIP.
			$this->auth_handler->format_get_args( array( 'timeout' => 45 ) )
		);

		if ( is_wp_error( $posts_response ) ) {
			return $posts_response;
		}

		$response_code = wp_remote_retrieve_response_code( $posts_response );

		if ( 200 !== $response_code ) {

			if ( 404 === $response_code ) {
				return new \WP_Error( 'bad-endpoint', esc_html__( 'Could not connect to API endpoint.', 'distributor' ) );
			}

			$posts_body = json_decode( wp_remote_retrieve_body( $posts_response ), true );

			$code    = empty( $posts_body['code'] ) ? 'endpoint-error' : esc_html( $posts_body['code'] );
			$message = empty( $posts_body['message'] ) ? esc_html__( 'API endpoint error.', 'distributor' ) : esc_html( $posts_body['message'] );

			return new \WP_Error( $code, $message );
		}

		$posts_body = wp_remote_retrieve_body( $posts_response );

		if ( empty( $posts_body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
		}

		$posts           = json_decode( $posts_body, true );
		$formatted_posts = array();

		$response_headers = wp_remote_retrieve_headers( $posts_response );

		if ( empty( $id ) ) {
			foreach ( $posts as $post ) {
				$post['full_connection'] = ( ! empty( $response_headers['X-Distributor'] ) );

				$formatted_posts[] = $this->to_wp_post( $post );
			}

			$total_posts = wp_remote_retrieve_header( $posts_response, 'X-WP-Total' );
			if ( empty( $total_posts ) ) {
				$total_posts = count( $formatted_posts );
			}

			// Filter documented above.
			return apply_filters(
				'dt_remote_get',
				[
					'items'       => $formatted_posts,
					'total_items' => $total_posts,
				],
				$args,
				$this
			);
		} else {
			// Filter documented above.
			return apply_filters( 'dt_remote_get', $this->to_wp_post( $posts ), $args, $this );
		}
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
			return new \WP_Error( 'endpoint-error', esc_html__( 'Endpoint URL must be set', 'distributor' ) );
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
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
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

		foreach ( $items as $item_array ) {
			$post = $this->remote_get(
				[
					'id'        => $item_array['remote_post_id'],
					'post_type' => $item_array['post_type'],
				]
			);

			if ( is_wp_error( $post ) ) {
				$created_posts[] = $post;
				continue;
			}

			$post_props = get_object_vars( $post );
			$post_array = array();

			foreach ( $post_props as $key => $value ) {
				$post_array[ $key ] = $value;
			}

			if ( ! empty( $item_array['post_id'] ) ) {
				$post_array['ID'] = $item_array['post_id'];
			} else {
				unset( $post_array['ID'] );
			}

			if ( isset( $post_array['post_parent'] ) ) {
				unset( $post_array['post_parent'] );
			}

			if ( ! empty( $item_array['post_status'] ) ) {
				$post_array['post_status'] = $item_array['post_status'];
			}

			// Remove date stuff
			unset( $post_array['post_date'] );
			unset( $post_array['post_date_gmt'] );
			unset( $post_array['post_modified'] );
			unset( $post_array['post_modified_gmt'] );

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
			$new_post      = wp_insert_post( wp_slash( $new_post_args ) );

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

			if ( ! empty( $post_array['meta'] ) ) {
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
	 * @param  int   $post_id Post id
	 * @param  array $args Post args to push.
	 * @since  0.8
	 * @return array|\WP_Error
	 */
	public function push( $post_id, $args = array() ) {
		if ( empty( $post_id ) ) {
			return new \WP_Error( 'no-push-post-id', esc_html__( 'Post id required to push', 'distributor' ) );
		}

		$post = get_post( $post_id );

		$post_type = get_post_type( $post_id );

		$path = self::$namespace;

		/**
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
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
		}

		$body_array = json_decode( $body, true );

		$type_url = $this->parse_type_items_link( $body_array[ $post_type ] );

		if ( empty( $type_url ) ) {
			return new \WP_Error( 'no-push-post-type', esc_html__( 'Could not determine remote post type endpoint', 'distributor' ) );
		}

		$signature = \Distributor\Subscriptions\generate_signature();

		/**
		 * Now let's push
		 */
		$post_body = [
			'title'                          => html_entity_decode( get_the_title( $post_id ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'slug'                           => $post->post_name,
			'content'                        => Utils\get_processed_content( $post->post_content ),
			'type'                           => $post->post_type,
			'status'                         => ( ! empty( $args['post_status'] ) ) ? $args['post_status'] : 'publish',
			'excerpt'                        => $post->post_excerpt,
			'distributor_original_source_id' => $this->id,
			'distributor_original_site_name' => get_bloginfo( 'name' ),
			'distributor_original_site_url'  => home_url(),
			'distributor_original_post_url'  => get_permalink( $post_id ),
			'distributor_remote_post_id'     => $post_id,
			'distributor_signature'          => $signature,
			'distributor_media'              => \Distributor\Utils\prepare_media( $post_id ),
			'distributor_terms'              => \Distributor\Utils\prepare_taxonomy_terms( $post_id ),
			'distributor_meta'               => \Distributor\Utils\prepare_meta( $post_id ),
		];

		// Gutenberg posts also distribute raw content.
		if ( \Distributor\Utils\is_using_gutenberg( $post ) ) {
			if ( \Distributor\Utils\dt_use_block_editor_for_post_type( $post->post_type ) ) {
				$post_body['distributor_raw_content'] = $post->post_content;
			}
		}

		if ( ! empty( $post->post_parent ) ) {
			$post_body['distributor_original_post_parent'] = (int) $post->post_parent;
		}

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
		do_action( 'dt_push_post', $response, $post_body, $type_url, $post_id, $args, $this );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
		}

		$body_array = json_decode( $body, true );

		if ( empty( $body_array['id'] ) ) {
			return new \WP_Error( 'no-push-post-remote-id', esc_html__( 'Could not determine remote post id.', 'distributor' ) );
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
			return new \WP_Error( 'no-response-body', esc_html__( 'Response body is empty', 'distributor' ) );
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

		$response = Utils\remote_http_request( untrailingslashit( $this->base_url ), $this->auth_handler->format_get_args( array( 'timeout' => self::$timeout ) ) );

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
	 * Whether if the post type is not distibutor internal post type.
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
		$obj = new \stdClass();

		$obj->ID         = $post['id'];
		$obj->post_title = $post['title']['rendered'];

		if ( isset( $post['excerpt']['raw'] ) ) {
			$obj->post_excerpt = $post['excerpt']['raw'];
		} elseif ( isset( $post['excerpt']['rendered'] ) ) {
			$obj->post_excerpt = $post['excerpt']['rendered'];
		} else {
			$obj->post_excerpt = '';
		}

		$obj->post_status = 'draft';
		$obj->post_author = get_current_user_id();

		$obj->post_password     = $post['password'];
		$obj->post_date         = $post['date'];
		$obj->post_date_gmt     = $post['date_gmt'];
		$obj->guid              = $post['guid']['rendered'];
		$obj->post_modified     = $post['modified'];
		$obj->post_modified_gmt = $post['modified_gmt'];
		$obj->post_type         = $post['type'];
		$obj->link              = $post['link'];
		$obj->comment_status    = $post['comment_status'];
		$obj->ping_status       = $post['ping_status'];

		// Use raw content if remote post uses Gutenberg and the local post type is compatible with it.
		$obj->post_content = Utils\dt_use_block_editor_for_post_type( $obj->post_type ) && isset( $post['is_using_gutenberg'] ) ?
			$post['content']['raw'] :
			Utils\get_processed_content( $post['content']['raw'] );

		/**
		 * These will only be set if Distributor is active on the other side
		 */
		$obj->meta               = ( ! empty( $post['distributor_meta'] ) ) ? $post['distributor_meta'] : [];
		$obj->terms              = ( ! empty( $post['distributor_terms'] ) ) ? $post['distributor_terms'] : [];
		$obj->media              = ( ! empty( $post['distributor_media'] ) ) ? $post['distributor_media'] : [];
		$obj->original_site_name = ( ! empty( $post['distributor_original_site_name'] ) ) ? $post['distributor_original_site_name'] : null;
		$obj->original_site_url  = ( ! empty( $post['distributor_original_site_url'] ) ) ? $post['distributor_original_site_url'] : null;

		$obj->full_connection = ( ! empty( $post['full_connection'] ) );

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
		return apply_filters( 'dt_item_mapping', new \WP_Post( $obj ), $post, $this );
	}

	/**
	 * Setup actions and filters that are need on every page load
	 *
	 * @since 1.0
	 */
	public static function bootstrap() {
		add_action( 'template_redirect', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'canonicalize_front_end' ) );
	}

	/**
	 * Setup canonicalization on front end
	 *
	 * @since  1.0
	 */
	public static function canonicalize_front_end() {
		add_filter( 'get_canonical_url', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'canonical_url' ), 10, 2 );
		add_filter( 'wpseo_canonical', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'wpseo_canonical_url' ) );
		add_filter( 'wpseo_opengraph_url', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'wpseo_og_url' ) );
		add_filter( 'the_author', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'the_author_distributed' ) );
		add_filter( 'author_link', array( '\Distributor\ExternalConnections\WordPressExternalConnection', 'author_posts_url_distributed' ), 10, 3 );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @param  string $link Author link.
	 * @param  int    $author_id Author ID.
	 * @param  string $author_nicename Author name.
	 * @since  1.0
	 * @return string
	 */
	public static function author_posts_url_distributed( $link, $author_id, $author_nicename ) {
		global $post;

		if ( empty( $post ) ) {
			return $link;
		}

		$settings = Utils\get_settings();

		if ( empty( $settings['override_author_byline'] ) ) {
			return $link;
		}

		$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );
		$original_site_url  = get_post_meta( $post->ID, 'dt_original_site_url', true );
		$unlinked           = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( empty( $original_source_id ) || empty( $original_site_url ) || $unlinked ) {
			return $link;
		}

		return $original_site_url;
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @param  string $author Author name.
	 * @since  1.0
	 * @return string
	 */
	public static function the_author_distributed( $author ) {
		global $post;

		if ( empty( $post ) ) {
			return $author;
		}

		$settings = Utils\get_settings();

		if ( empty( $settings['override_author_byline'] ) ) {
			return $author;
		}

		$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );
		$original_site_name = get_post_meta( $post->ID, 'dt_original_site_name', true );
		$original_site_url  = get_post_meta( $post->ID, 'dt_original_site_url', true );
		$unlinked           = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( empty( $original_source_id ) || empty( $original_site_url ) || empty( $original_site_name ) || $unlinked ) {
			return $author;
		}

		return $original_site_name;
	}

	/**
	 * Make sure canonical url header is outputted
	 *
	 * @param  string $canonical_url Canonical URL.
	 * @param  object $post Post object.
	 * @since  1.0
	 * @return string
	 */
	public static function canonical_url( $canonical_url, $post ) {
		$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );
		$original_post_url  = get_post_meta( $post->ID, 'dt_original_post_url', true );
		$unlinked           = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );
		$original_deleted   = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

		if ( empty( $original_source_id ) || empty( $original_post_url ) || $unlinked || $original_deleted ) {
			return $canonical_url;
		}

		return $original_post_url;
	}

	/**
	 * Handles the canonical URL change for distributed content when Yoast SEO is in use
	 *
	 * @param string $canonical_url The Yoast WPSEO deduced canonical URL
	 * @since  1.0
	 * @return string $canonical_url The updated distributor friendly URL
	 */
	public static function wpseo_canonical_url( $canonical_url ) {

		// Return as is if not on a singular page - taken from rel_canonical()
		if ( ! is_singular() ) {
			return $canonical_url;
		}

		$id = get_queried_object_id();

		// Return as is if we do not have a object id for context - taken from rel_canonical()
		if ( 0 === $id ) {
			return $canonical_url;
		}

		$post = get_post( $id );

		// Return as is if we don't have a valid post object - taken from wp_get_canonical_url()
		if ( ! $post ) {
			return $canonical_url;
		}

		// Return as is if current post is not published - taken from wp_get_canonical_url()
		if ( 'publish' !== $post->post_status ) {
			return $canonical_url;
		}

		return self::canonical_url( $canonical_url, $post );
	}

	/**
	 * Handles the og:url change for distributed content when Yoast SEO is in use
	 *
	 * @param string $og_url The Yoast WPSEO deduced OG URL which is a result of wpseo_canonical_url
	 *
	 * @return string $og_url The updated distributor friendly URL
	 */
	public static function wpseo_og_url( $og_url ) {
		if ( is_singular() ) {
			$og_url = get_permalink();
		}

		return $og_url;
	}
}
