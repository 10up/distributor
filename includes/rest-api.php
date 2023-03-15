<?php
/**
 * REST API functionality
 *
 * @package  distributor
 */

namespace Distributor\RestApi;

use Distributor\Utils;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'init',
		function() {
			add_action( 'rest_api_init', __NAMESPACE__ . '\register_endpoints' );
			add_action( 'rest_api_init', __NAMESPACE__ . '\register_rest_routes' );
			add_action( 'rest_api_init', __NAMESPACE__ . '\register_push_errors_field' );

			$post_types = get_post_types(
				array(
					'show_in_rest' => true,
				)
			);

			foreach ( $post_types as $post_type ) {
				add_action( "rest_insert_{$post_type}", __NAMESPACE__ . '\process_distributor_attributes', 10, 3 );
				add_filter( "rest_pre_insert_{$post_type}", __NAMESPACE__ . '\filter_distributor_content', 1, 2 );
				add_filter( "rest_prepare_{$post_type}", __NAMESPACE__ . '\prepare_distributor_content', 10, 3 );
			}
		},
		100
	);
}

/**
 * Filter the data inserted by the REST API when a post is pushed.
 *
 * Use the raw content for Gutenberg->Gutenberg posts. Note: `distributor_raw_content`
 * is only sent when the origin supports Gutenberg.
 *
 * @param Object           $prepared_post An object representing a single post prepared.
 * @param \WP_REST_Request $request Request object.
 *
 * @return Object $prepared_post The filtered post object.
 */
function filter_distributor_content( $prepared_post, $request ) {
	if (
		isset( $request['distributor_raw_content'] ) &&
		\Distributor\Utils\dt_use_block_editor_for_post_type( $prepared_post->post_type )
	) {
			$prepared_post->post_content = $request['distributor_raw_content'];
	}
	return $prepared_post;
}

/**
 * When an API push is being received, handle Distributor specific attributes
 *
 * @param \WP_Post         $post Post object.
 * @param \WP_REST_Request $request Request object.
 * @param bool             $update Update or create.
 * @since 1.0
 */
function process_distributor_attributes( $post, $request, $update ) {
	if ( empty( $post ) || is_wp_error( $post ) ) {
		return;
	}

	/**
	 * Not Distributor push so ignore it. Other things use the REST API besides Distributor.
	 */
	if ( empty( $request['distributor_original_source_id'] ) ) {
		return;
	}

	if ( ! empty( $request['distributor_remote_post_id'] ) ) {
		update_post_meta( $post->ID, 'dt_original_post_id', (int) $request['distributor_remote_post_id'] );
	}

	if ( ! empty( $request['distributor_original_site_name'] ) ) {
		update_post_meta( $post->ID, 'dt_original_site_name', sanitize_text_field( $request['distributor_original_site_name'] ) );
	}

	if ( ! empty( $request['distributor_original_site_url'] ) ) {
		update_post_meta( $post->ID, 'dt_original_site_url', sanitize_text_field( $request['distributor_original_site_url'] ) );
	}

	if ( ! empty( $request['distributor_original_post_url'] ) ) {
		update_post_meta( $post->ID, 'dt_original_post_url', esc_url_raw( $request['distributor_original_post_url'] ) );
	}

	if ( ! empty( $request['distributor_signature'] ) ) {
		update_post_meta( $post->ID, 'dt_subscription_signature', sanitize_text_field( $request['distributor_signature'] ) );
	}

	update_post_meta( $post->ID, 'dt_syndicate_time', time() );

	update_post_meta( $post->ID, 'dt_full_connection', true );

	update_post_meta( $post->ID, 'dt_original_source_id', (int) $request['distributor_original_source_id'] );

	if ( isset( $request['distributor_meta'] ) ) {
		\Distributor\Utils\set_meta( $post->ID, $request['distributor_meta'] );
	}

	if ( isset( $request['distributor_terms'] ) ) {
		\Distributor\Utils\set_taxonomy_terms( $post->ID, $request['distributor_terms'] );
	}

	if ( isset( $request['distributor_media'] ) ) {
		\Distributor\Utils\set_media( $post->ID, $request['distributor_media'] );
	}

	/**
	 * Fires after an API push is handled by Distributor.
	 *
	 * @since 1.0
	 * @hook dt_process_distributor_attributes
	 *
	 * @param {WP_Post}         $post    Inserted or updated post object.
	 * @param {WP_REST_Request} $request Request object.
	 * @param {bool}            $update  True when creating a post, false when updating.
	 */
	do_action( 'dt_process_distributor_attributes', $post, $request, $update );
}

/**
 * Register custom routes to handle distributor specific functionality.
 */
function register_rest_routes() {
	register_rest_route(
		'wp/v2',
		'distributor/post-types-permissions',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\check_post_types_permissions',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'wp/v2',
		'distributor/list-pull-content',
		array(
			'methods'             => 'POST',
			'callback'            => __NAMESPACE__ . '\get_pull_content',
			'permission_callback' => __NAMESPACE__ . '\\get_pull_content_permissions',
			'args'                => get_pull_content_list_args(),
		)
	);
}

/**
 * Set the accepted arguments for the pull content list endpoint
 *
 * @return array
 */
function get_pull_content_list_args() {
	return array(
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		'exclude'        => array(
			'description' => esc_html__( 'Ensure result set excludes specific IDs.', 'distributor' ),
			'type'        => 'array',
			'items'       => array(
				'type' => 'integer',
			),
			'default'     => array(),
		),
		'page'           => array(
			'description'       => esc_html__( 'Current page of the collection.', 'distributor' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		),
		'posts_per_page' => array(
			'description'       => esc_html__( 'Maximum number of items to be returned in result set.', 'distributor' ),
			'type'              => 'integer',
			'default'           => 20,
			'minimum'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		),
		'post_type'      => array(
			'description'       => esc_html__( 'Limit results to content matching a certain type.', 'distributor' ),
			'type'              => 'string',
			'default'           => 'post',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		),
		'search'         => array(
			'description'       => esc_html__( 'Limit results to those matching a string.', 'distributor' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		),
		'post_status'    => array(
			'default'     => 'publish',
			'description' => esc_html__( 'Limit result set to content assigned one or more statuses.', 'distributor' ),
			'type'        => 'array',
			'items'       => array(
				'enum' => array_merge( array_keys( get_post_stati() ), array( 'any' ) ),
				'type' => 'string',
			),
		),
	);
}

/**
 * Check if the current user has permission to pull content.
 *
 * Checks whether the user can pull content for the specified post type.
 *
 * @since 1.9.1
 *
 * @param \WP_REST_Request $request Full details about the request.
 * @return bool Whether the current user has permission to pull content.
 */
function get_pull_content_permissions( $request ) {
	$post_type = $request->get_param( 'post_type' );
	if ( ! $post_type ) {
		return false;
	}

	$post_type_object = get_post_type_object( $post_type );
	if ( ! $post_type_object ) {
		return false;
	}

	return current_user_can( $post_type_object->cap->edit_posts );
}

/**
 * Filter the data requested over REST API when a post is pulled.
 *
 * @param \WP_REST_Response $response Response object.
 * @param \WP_Post          $post     Post object.
 * @param \WP_REST_Request  $request  Request object.
 *
 * @return \WP_REST_Response $response The filtered response object.
 */
function prepare_distributor_content( $response, $post, $request ) {

	// Only adjust distributor requests.
	if ( '1' !== $request->get_param( 'distributor_request' ) ) {
		return $response;
	}

	$post_data = $response->get_data();

	// Is the local site is running Gutenberg?
	if ( \Distributor\Utils\is_using_gutenberg( $post ) ) {
		$post_data['is_using_gutenberg'] = true;
	}

	$response->set_data( $post_data );

	return $response;
}

/**
 * We need to register distributor post fields for getting all the meta, terms, and media. This
 * is easier than modifying existing fields which other plugins may depend on.
 *
 * @since 1.0
 */
function register_endpoints() {
	$post_types = get_post_types(
		array(
			'show_in_rest' => true,
		)
	);

	register_rest_field(
		$post_types,
		'distributor_meta',
		array(
			'get_callback'    => function( $post_array ) {
				if ( ! current_user_can( 'edit_post', $post_array['id'] ) ) {
					return false;
				}

				return \Distributor\Utils\prepare_meta( $post_array['id'] );
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Post meta for Distributor.', 'distributor' ),
				'type'        => 'object',
			),
		)
	);

	register_rest_field(
		$post_types,
		'distributor_terms',
		array(
			'get_callback'    => function( $post_array ) {
				if ( ! current_user_can( 'edit_post', $post_array['id'] ) ) {
					return false;
				}

				return \Distributor\Utils\prepare_taxonomy_terms( $post_array['id'] );
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Taxonomy terms for Distributor.', 'distributor' ),
				'type'        => 'object',
			),
		)
	);

	register_rest_field(
		$post_types,
		'distributor_media',
		array(
			'get_callback'    => function( $post_array ) {
				if ( ! current_user_can( 'edit_post', $post_array['id'] ) ) {
					return false;
				}

				return \Distributor\Utils\prepare_media( $post_array['id'] );
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Media for Distributor.', 'distributor' ),
				'type'        => 'object',
			),
		)
	);

	register_rest_field(
		$post_types,
		'distributor_original_site_name',
		array(
			'get_callback'    => function( $post_array ) {
				$site_name = get_post_meta( $post_array['id'], 'dt_original_site_name', true );

				if ( ! $site_name ) {
					$site_name = get_bloginfo( 'name' );
				}

				return esc_html( $site_name );
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Original site name for Distributor.', 'distributor' ),
				'type'        => 'string',
			),
		)
	);

	register_rest_field(
		$post_types,
		'distributor_original_site_url',
		array(
			'get_callback'    => function( $post_array ) {
				$site_url = get_post_meta( $post_array['id'], 'dt_original_site_url', true );

				if ( ! $site_url ) {
					$site_url = home_url();
				}

				return esc_url_raw( $site_url );
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Original site url for Distributor.', 'distributor' ),
				'type'        => 'string',
			),
		)
	);

	// Register a distributor meta endpoint
	register_rest_route(
		'wp/v2',
		'/dt_meta',
		array(
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\distributor_meta',
			'permission_callback' => '__return_true',
		)
	);

}

/**
 * Return plugin meta information.
 */
function distributor_meta() {
	return array(
		'version'                              => DT_VERSION,
		'core_has_application_passwords'       => function_exists( 'wp_is_application_passwords_available' ),
		'core_application_passwords_available' => function_exists( 'wp_is_application_passwords_available' ) && ! wp_is_application_passwords_available() ? false : true,
	);
}

/**
 * Check user permissions for available post types
 */
function check_post_types_permissions() {
	$types = get_post_types(
		array(
			'show_in_rest' => true,
		),
		'objects'
	);

	$response = array(
		'can_get'          => array(),
		'can_post'         => array(),
		'is_authenticated' => get_current_user_id() ? 'yes' : 'no',
	);

	foreach ( $types as $type ) {
		$caps                  = $type->cap;
		$response['can_get'][] = $type->name;

		if ( current_user_can( $caps->edit_posts ) && current_user_can( $caps->create_posts ) && current_user_can( $caps->publish_posts ) ) {
			$response['can_post'][] = $type->name;
		}
	}

	return $response;
}

/**
 * Get a list of content to show on the Pull screen
 *
 * @param \WP_Rest_Request $request API request arguments
 * @return \WP_REST_Response|\WP_Error
 */
function get_pull_content( $request ) {
	$args = [
		'posts_per_page' => isset( $request['posts_per_page'] ) ? $request['posts_per_page'] : 20,
		'paged'          => isset( $request['page'] ) ? $request['page'] : 1,
		'post_type'      => isset( $request['post_type'] ) ? $request['post_type'] : 'post',
		'post_status'    => isset( $request['post_status'] ) ? $request['post_status'] : array( 'any' ),
	];

	if ( ! empty( $request['search'] ) ) {
		$args['s'] = rawurldecode( $request['search'] );
	}

	if ( ! empty( $request['exclude'] ) ) {
		$args['post__not_in'] = $request['exclude'];
	}

	/**
	 * Filters WP_Query arguments when querying posts via the REST API.
	 *
	 * Enables adding extra arguments or setting defaults for a post collection request.
	 *
	 * @hook dt_get_pull_content_rest_query_args
	 *
	 * @param {array}           $args    Array of arguments for WP_Query.
	 * @param {WP_REST_Request} $request The REST API request.
	 *
	 * @return {array} The array of arguments for WP_Query.
	 */
	$args = apply_filters( 'dt_get_pull_content_rest_query_args', $args, $request );

	// Only get posts that are editable by the user.
	$args['perm'] = 'editable';
	$query        = new \WP_Query( $args );

	if ( empty( $query->posts ) ) {
		return rest_ensure_response( array() );
	}

	$page        = (int) $args['paged'];
	$total_posts = $query->found_posts;

	$max_pages = ceil( $total_posts / (int) $query->query_vars['posts_per_page'] );

	if ( $page > $max_pages && $total_posts > 0 ) {
		return new \WP_Error(
			'rest_post_invalid_page_number',
			esc_html__( 'The page number requested is larger than the number of pages available.', 'distributor' ),
			array( 'status' => 400 )
		);
	}

	$formatted_posts = array();
	foreach ( $query->posts as $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			continue;
		}

		$formatted_posts[] = array(
			'id'             => $post->ID,
			'title'          => array( 'rendered' => $post->post_title ),
			'excerpt'        => array( 'rendered' => $post->post_excerpt ),
			'content'        => array( 'raw' => $post->post_content ),
			'password'       => $post->post_password,
			'date'           => $post->post_date,
			'date_gmt'       => $post->post_date_gmt,
			'guid'           => array( 'rendered' => $post->guid ),
			'modified'       => $post->post_modified,
			'modified_gmt'   => $post->post_modified_gmt,
			'type'           => $post->post_type,
			'link'           => get_the_permalink( $post ),
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
		);
	}

	$response = rest_ensure_response( $formatted_posts );

	$response->header( 'X-WP-Total', (int) $total_posts );
	$response->header( 'X-WP-TotalPages', (int) $max_pages );

	return $response;
}

/**
 * Checks if a post can be read.
 *
 * Copied from WordPress core.
 *
 * @param \WP_Post $post Post object.
 * @return bool
 */
function check_read_permission( $post ) {
	// Validate the post type.
	$post_type = \get_post_type_object( $post->post_type );

	if ( empty( $post_type ) || empty( $post_type->show_in_rest ) ) {
		return false;
	}

	// Is the post readable?
	if ( 'publish' === $post->post_status || \current_user_can( 'read_post', $post->ID ) ) {
		return true;
	}

	$post_status_obj = \get_post_status_object( $post->post_status );
	if ( $post_status_obj && $post_status_obj->public ) {
		return true;
	}

	// Can we read the parent if we're inheriting?
	if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
		$parent = \get_post( $post->post_parent );
		if ( $parent ) {
			return check_read_permission( $parent );
		}
	}

	/*
	 * When there isn't a parent, but the status is set to inherit, assume
	 * it's published (as per get_post_status()).
	 */
	if ( 'inherit' === $post->post_status ) {
		return true;
	}

	return false;
}

/**
 * Register push errors field so we can send errors over the REST API.
 */
function register_push_errors_field() {

	$post_types = get_post_types(
		array(
			'show_in_rest' => true,
		)
	);

	foreach ( $post_types as $post_type ) {
		register_rest_field(
			$post_type,
			'push-errors',
			array(
				'get_callback' => function( $params ) {
					$media_errors = get_transient( 'dt_media_errors_' . $params['id'] );

					if ( ! empty( $media_errors ) ) {
						delete_transient( 'dt_media_errors_' . $params['id'] );
						return $media_errors;
					}
					return false;
				},
			)
		);
	}
}
