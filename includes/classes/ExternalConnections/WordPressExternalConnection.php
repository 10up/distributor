<?php

namespace Syndicate\ExternalConnections;
use \Syndicate\ExternalConnection as ExternalConnection;

class WordPressExternalConnection extends ExternalConnection {

	static $slug = 'wp';
	static $label = 'WordPress REST API';
	static $auth_handler_class = '\Syndicate\Authentications\WordPressBasicAuth';
	static $mapping_handler_class = '\Syndicate\Mappings\WordPressRestPost';
	static $namespace = 'wp/v2';

	/**
	 * Remotely get posts
	 * 
	 * @param  array  $args
	 * @since  1.0
	 * @return array|WP_Post
	 */
	public function remote_get( $args = array() ) {
		$id = ( empty( $args['id'] ) ) ? false : $args['id'];

		$query_args = array();

		$query_args['post_type'] = ( empty( $args['post_type'] ) ) ? 'post' : $args['post_type'];

		if ( empty( $id ) ) {
			$query_args['post_status'] = ( empty( $args['post_status'] ) ) ? 'any' : $args['post_status'];
			$query_args['posts_per_page'] = ( empty( $args['posts_per_page'] ) ) ? get_option( 'posts_per_page' ) : $args['posts_per_page'];
			$query_args['paged'] = ( empty( $args['paged'] ) ) ? 1 : $args['paged'];
			
			if ( isset( $args['post__in'] ) ) {
				if ( empty( $args['post__in'] ) ) {
					// If post__in is empty, we can just stop right here
					return apply_filters( 'sy_remote_get', [
						'items'       => array(),
						'total_items' => 0,
					], $args, $this );
				}

				$query_args['post__in'] = $args['post__in'];
			} elseif ( isset( $args['post__not_in'] ) ) {
				$query_args['post__not_in'] = $args['post__not_in'];
			}

			if ( ! empty( $args['s'] ) ) {
				$query_args['s'] = $args['s'];
			}
		}

		static $types_urls;
		$types_urls = array();

		if ( empty( $types_urls[ $query_args['post_type'] ] ) ) {
			/**
			 * First let's get the actual route if not cached. We don't know the "plural" of our post type
			 */
			
			$path = self::$namespace;

			$types_response = wp_remote_get( untrailingslashit( $this->base_url ) . '/' . $path . '/types' );

			if ( is_wp_error( $types_response ) ) {
				return $response;
			}

			$types_body = wp_remote_retrieve_body( $types_response );

			if ( is_wp_error( $types_body ) ) {
				return $types_body;
			}

			try {
				$types_body_array = json_decode( $types_body, true );
				$types_urls[ $query_args['post_type'] ] = $types_body_array[ $query_args['post_type'] ]['_links']['wp:items'][0]['href'];
			} catch ( \Exception $e ) {
				return new WP_Error( 'no-pull-post-type', esc_html__( 'Could not determine remote post type endpoint', 'syndicate' ) );
			}
		}

		$args_str = '';

		foreach ( $query_args as $arg_key => $arg_value) {
			if ( is_array( $arg_value ) ) {
				foreach ( $arg_value as $arg_value_value ) {
					if ( ! empty( $args_str ) ) {
						$args_str .= '&';
					}

					$args_str .= 'filter[' . $arg_key . '][]=' . $arg_value_value;
				}
			} else {
				if ( ! empty( $args_str ) ) {
					$args_str .= '&';
				}

				$args_str .= 'filter[' . $arg_key . ']=' . $arg_value;
			}
		}

		if ( ! empty( $id ) ) {
			$posts_url = untrailingslashit( $types_urls[ $query_args['post_type'] ] ) . '/' . $id;
		} else {
			$posts_url = untrailingslashit( $types_urls[ $query_args['post_type'] ] ) . '/?' . $args_str;
		}

		$posts_response = wp_remote_get( $posts_url, $this->auth_handler->format_get_args( array() ) );
		
		if ( is_wp_error( $posts_response ) ) {
			return $posts_response;
		}

		$posts_body = wp_remote_retrieve_body( $posts_response );

		if ( is_wp_error( $posts_body ) ) {
			return $posts_body;
		}

		$posts = json_decode( $posts_body, true );
		$formatted_posts = array();

		if ( empty( $id ) ) {
			foreach ( $posts as $post ) {
				$formatted_posts[] = $this->mapping_handler->to_wp_post( $post );
			}

			$total_posts = wp_remote_retrieve_header( $posts_response, 'X-WP-Total' );
			if ( empty( $total_posts ) ) {
				$total_posts = count( $formatted_posts );
			}

			return apply_filters( 'sy_remote_get', [
				'items'       => $formatted_posts,
				'total_items' => $total_posts,
			], $args, $this );
		} else {
			return apply_filters( 'sy_remote_get', $this->mapping_handler->to_wp_post( $posts ), $args, $this );
		}
	}

	/**
	 * Pull items
	 * 
	 * @param  array $items
	 * @since  1.0
	 * @return array
	 */
	public function pull( $items ) {
		$created_posts = array();

		foreach ( $items as $item_id ) {
			$post = $this->remote_get( [ 'id' => $item_id ] );

			if ( is_wp_error( $post ) ) {
				$created_posts[] = $post;
				continue;
			}

			$post_props = get_object_vars( $post );
			$post_array = array();

			foreach ( $post_props as $key => $value ) {
				$post_array[ $key ] = $value;
			}

			unset( $post_array['ID'] );

			// Remove date stuff
			unset( $post_array['post_date'] );
			unset( $post_array['post_date_gmt'] );
			unset( $post_array['post_modified'] );
			unset( $post_array['post_modified_gmt'] );

			$new_post = wp_insert_post( apply_filters( 'sy_pull_post_args', $post_array, $item_id, $post, $this ) );

			do_action( 'sy_pull_post', $new_post, $this );

			$created_posts[] = $new_post;
		}

		return $created_posts;
	}

	/**
	 * Push a post to an external connection
	 * 
	 * @param  int $post_id
	 * @param  array $args
	 * @since  1.0
	 * @return bool|WP_Error
	 */
	public function push( $post_id, $args = array() ) {
		if ( empty( $post_id ) ) {
			return new WP_Error( 'no-push-post-id' , esc_html__( 'Post id required to push', 'syndicate' ) );
		}

		$post = get_post( $post_id );

		$post_type = get_post_type( $post_id );

		$path = self::$namespace;

		/**
		 * First let's get the actual route. We don't know the "plural" of our post type
		 */

		$response = wp_remote_get( untrailingslashit( $this->base_url ) . '/' . $path . '/types' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		try {
			$body_array = json_decode( $body, true );
			$type_url = $body_array[ $post_type ]['_links']['wp:items'][0]['href'];
		} catch ( \Exception $e ) {
			return new WP_Error( 'no-push-post-type', esc_html__( 'Could not determine remote post type endpoint', 'syndicate' ) );
		}

		/**
		 * Now let's push
		 */
		$post_body = array(
			'title'   => get_the_title( $post_id ),
			'content' => apply_filters( 'the_content', $post->post_content ),
			'type'    => $post->post_type,
			'status'  => 'draft',
			'excerpt' => $post->post_excerpt,
		);

		// Map to remote ID if a push has already happened
		if ( ! empty( $args['remote_post_id'] ) ) {
			$existing_post_url = untrailingslashit( $type_url ) . '/' . $args['remote_post_id'];

			// Check to make sure remote post still exists
			$post_exists_response = wp_remote_get( $existing_post_url, $this->auth_handler->format_get_args( array() ) );

			if ( ! is_wp_error( $post_exists_response ) ) {
				$post_exists_response_code = wp_remote_retrieve_response_code( $post_exists_response );

				if ( 200 === (int) $post_exists_response_code ) {
					$type_url = $existing_post_url;
				}
			}
		}

		$response = wp_remote_post( $type_url, $this->auth_handler->format_post_args( array( 'body' =>  apply_filters( 'sy_push_post_args', $post_body, $post, $this ) ) ) );

		do_action( 'sy_push_post', $response, $post_body, $type_url, $post_id, $args, $this );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		try {
			$body_array = json_decode( $body, true );
			$remote_id = $body_array['id'];
		} catch ( \Exception $e ) {
			return new WP_Error( 'no-push-post-remote-id', esc_html__( 'Could not determine remote post id.', 'syndicate' ) );
		}

		return $remote_id;
	}

	/**
	 * Check what we can do with a given external connection (push or pull)
	 *
	 * @since  1.0
	 * @return array
	 */
	public function check_connections() {
		$output = array(
			'errors'              => array(),
			'can_post'            => array(),
			'can_get'             => array(),
			'endpoint_suggestion' => false,
		);

		$response = wp_remote_get( untrailingslashit( $this->base_url ), $this->auth_handler->format_get_args( array() ) );
		$body = wp_remote_retrieve_body( $response );

		if ( is_wp_error( $response ) || is_wp_error( $body ) ) {
			$output['errors']['no_external_connection']  = 'no_external_connection';
			return $output;
		}

		$response_headers = wp_remote_retrieve_headers( $response );
		$link_headers = (array) $response_headers['Link'];
		$correct_endpoint = false;

		foreach ( $link_headers as $link_header ) {
			if ( strpos( $link_header, 'rel="https://api.w.org/"' ) !== false ) {
				$correct_endpoint = preg_replace( '#.*<([^>]+)>.*#', '$1', $link_header );
			}
		}

		if ( empty( $correct_endpoint ) ) {
			$output['errors']['no_external_connection'] = 'no_external_connection';
			return $output;
		}

		if ( ! empty( $correct_endpoint ) && untrailingslashit( $this->base_url ) !== untrailingslashit( $correct_endpoint ) ) {
			$output['errors']['no_external_connection'] = 'no_external_connection';
			$output['endpoint_suggestion'] = untrailingslashit( $correct_endpoint );
			return $output;
		}

		$types_response = wp_remote_get( untrailingslashit( $this->base_url ) . '/' . self::$namespace . '/types', $this->auth_handler->format_get_args( array() ) );
		$types_body = wp_remote_retrieve_body( $types_response );

		if ( is_wp_error( $types_response ) || is_wp_error( $types_body ) ) {
			$output['errors']['no_external_connection']  = 'no_external_connection';
		} else {
			$types = json_decode( $types_body, true );

			if ( empty( $types ) ) {
				$output['errors']['no_external_connection']  = 'no_external_connection';
			} else {
				$data = json_decode( $body, true );
				$routes = $data['routes'];

				$can_get = array();
				$can_post = array();

				foreach ( $types as $type_key => $type ) {
					$link = $type['_links']['wp:items'][0]['href'];
					$route = str_replace( untrailingslashit( $this->base_url ), '', $link );

					if ( ! empty( $routes[ $route ] ) ) {
						if ( in_array( 'GET',  $routes[ $route ]['methods'] ) ) {
							$type_response = wp_remote_get( $link, $this->auth_handler->format_get_args( array() ) );
							if ( ! is_wp_error( $type_response ) ) {
								$code = (int) wp_remote_retrieve_response_code( $type_response );

								if ( 401 !== $code ) {
									$can_get[] = $type_key;
								}
							}
						}

						if ( in_array( 'POST',  $routes[ $route ]['methods'] ) ) {
							$type_response = wp_remote_post( $link, $this->auth_handler->format_post_args( array() ) );
							
							if ( ! is_wp_error( $type_response ) ) {
								$code = (int) wp_remote_retrieve_response_code( $type_response );

								if ( 401 !== $code ) {
									$can_post[] = $type_key;
								}
							}
						}
					}
				}

				$output['can_get'] = $can_get;
				$output['can_post'] = $can_post;
			}
		}

		return $output;
	}
}