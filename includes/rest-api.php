<?php
/**
 * REST API functionality
 *
 * @package  distributor
 */

namespace Distributor\RestApi;

use Distributor\DistributorPost;
use Distributor\Utils;
use WP_Error;

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
	} else {
		// Remove any previously set featured image.
		delete_post_meta( $post->ID, '_thumbnail_id' );
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
			'callback'            => __NAMESPACE__ . '\\get_pull_content_list',
			'permission_callback' => __NAMESPACE__ . '\\get_pull_content_permissions',
			'args'                => get_pull_content_list_args(),
		)
	);
}

/**
 * Set the accepted arguments for the pull content list endpoint.
 *
 * @since 2.0.0 Introduced the include, order and orderby arguments.
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
		'include'        => array(
			'description'       => esc_html__( 'Ensure result set includes specific IDs.', 'distributor' ),
			'type'              => array( 'array', 'integer' ),
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => function( $param ) {
				if ( ! is_array( $param ) ) {
					$param = array( $param );
				}

				return wp_parse_id_list( $param );
			},
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
			'type'              => array( 'array', 'string' ),
			'items'             => array(
				'type' => 'string',
			),
			'default'           => array( 'post' ),
			'validate_callback' => function( $param ) {
				if ( is_string( $param ) ) {
					return sanitize_key( $param ) === $param;
				}

				foreach ( $param as $post_type ) {
					if ( sanitize_key( $post_type ) !== $post_type ) {
						return false;
					}
				}

				return true;
			},
			'sanitize_callback' => function( $param ) {
				if ( is_string( $param ) ) {
					$param = array( $param );
				}

				$allowed_post_types = array_keys(
					get_post_types(
						array(
							'show_in_rest' => true,
						)
					)
				);

				/*
				 * Only post types viewable on the front end should be allowed.
				 *
				 * Some post types may be visible in the REST API but not intended
				 * to be viewed on the front end. This removes any such posts from the
				 * list of allowed post types.
				 *
				 * `is_post_type_viewable()` is used to filter the results as
				 * WordPress applies different rules for custom and built in post
				 * types to determine whether they are viewable on the front end.
				 */
				$allowed_post_types = array_filter( $allowed_post_types, 'is_post_type_viewable' );

				if ( in_array( 'any', $param, true ) ) {
					$param = $allowed_post_types;
				} else {
					$param = array_intersect( $param, $allowed_post_types );
				}

				$param = array_filter(
					$param,
					function( $post_type ) {
						$post_type_object = get_post_type_object( $post_type );
						return current_user_can( $post_type_object->cap->edit_posts );
					}
				);

				if ( empty( $param ) ) {
					// This will cause the parameter to fall back to the default.
					$param = null;
				}

				return $param;
			},
		),
		'search'         => array(
			'description'       => esc_html__( 'Limit results to those matching a string.', 'distributor' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		),
		'post_status'    => array(
			'default'           => array( 'publish' ),
			'description'       => esc_html__( 'Limit result set to content assigned one or more statuses.', 'distributor' ),
			'type'              => array( 'array', 'string' ),
			'items'             => array(
				'type' => 'string',
			),
			'validate_callback' => function( $param ) {
				if ( is_string( $param ) ) {
					return sanitize_key( $param ) === $param;
				}

				foreach ( $param as $post_status ) {
					if ( sanitize_key( $post_status ) !== $post_status ) {
						return false;
					}
				}

				return true;
			},
			'sanitize_callback' => function( $param ) {
				if ( is_string( $param ) ) {
					$param = array( $param );
				}

				/*
				 * Only show viewable post statues.
				 *
				 * `is_post_status_viewable()` is used to filter the results as
				 * WordPress applies a complex set of rules to determine if a post
				 * status is viewable.
				 */
				$allowed_statues = array_keys( array_filter( get_post_stati(), 'is_post_status_viewable' ) );

				if ( in_array( 'any', $param, true ) ) {
					return $allowed_statues;
				}

				$param = array_intersect( $param, $allowed_statues );

				if ( empty( $param ) ) {
					// This will cause the parameter to fall back to the default.
					$param = null;
				}

				return $param;
			},
		),
		'order'          => array(
			'description' => esc_html__( 'Order sort attribute ascending or descending.', 'distributor' ),
			'type'        => 'string',
			'default'     => 'desc',
			'enum'        => array( 'asc', 'desc' ),
		),
		'orderby'        => array(
			'description' => esc_html__( 'Sort collection by object attribute.', 'distributor' ),
			'type'        => 'string',
			'default'     => 'date',
			'enum'        => array(
				'author',
				'date',
				'id',
				'include',
				'modified',
				'parent',
				'relevance',
				'slug',
				'title',
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
	/*
	 * Ensure Distributor requests are coming from a supported version.
	 *
	 * Changes to this endpoint in Distributor 2.0.0 require both the source and remote
	 * sites use a 2.x release of Distributor. This check ensures that the remote site
	 * is running a version of Distributor that supports the new endpoint.
	 *
	 * Development versions of the plugin and Non-Distributor requests are allowed
	 * to pass through this check.
	 */
	if (
		false === Utils\is_development_version()
		&& null !== $request->get_param( 'distributor_request' )
		&& (
			null === $request->get_header( 'X-Distributor-Version' )
			|| version_compare( $request->get_header( 'X-Distributor-Version' ), '2.0.0', '<' )
		)
	) {
		return new \WP_Error(
			'distributor_pull_content_permissions',
			esc_html__( 'Pulling content from external connections requires Distributor version 2.0.0 or later.', 'distributor' ),
			array( 'status' => 403 )
		);

	}

	$post_types = $request->get_param( 'post_type' );
	if ( empty( $post_types ) ) {
		return false;
	}

	if ( is_string( $post_types ) ) {
		$post_types = array( $post_types );
	}

	foreach ( $post_types as $post_type ) {
		$post_type_object = get_post_type_object( $post_type );
		if ( ! $post_type_object ) {
			return false;
		}

		if ( ! current_user_can( $post_type_object->cap->edit_posts ) ) {
			return false;
		}
	}

	// User can edit all post types.
	return true;
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
		'core_has_application_passwords'       => true,
		'core_application_passwords_available' => ! wp_is_application_passwords_available() ? false : true,
		'core_application_passwords_endpoint'  => admin_url( 'authorize-application.php' ),
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
		$caps = $type->cap;

		if ( current_user_can( $caps->edit_posts ) ) {
			$response['can_get'][] = $type->name;
		}

		if ( current_user_can( $caps->edit_posts ) && current_user_can( $caps->create_posts ) && current_user_can( $caps->publish_posts ) ) {
			$response['can_post'][] = $type->name;
		}
	}

	return $response;
}

/**
 * Get a list of content to show on the Pull screen
 *
 * @since 2.0.0 Renamed from get_pull_content() to get_pull_content_list().
 *
 * @param \WP_Rest_Request $request API request arguments
 * @return \WP_REST_Response|\WP_Error
 */
function get_pull_content_list( $request ) {
	$args = [
		'posts_per_page' => isset( $request['posts_per_page'] ) ? $request['posts_per_page'] : 20,
		'paged'          => isset( $request['page'] ) ? $request['page'] : 1,
		'post_type'      => isset( $request['post_type'] ) ? $request['post_type'] : 'post',
		'post_status'    => isset( $request['post_status'] ) ? $request['post_status'] : array( 'any' ),
		'order'          => ! empty( $request['order'] ) ? strtoupper( $request['order'] ) : 'DESC',
	];

	if ( ! empty( $request['search'] ) ) {
		$args['s']       = rawurldecode( $request['search'] );
		$args['orderby'] = 'relevance';
	}

	if ( ! empty( $request['exclude'] ) && ! empty( $request['include'] ) ) {
		/*
		 * Use only `post__in` if both `include` and `exclude` are populated.
		 *
		 * Excluded posts take priority over included posts, if the same post is
		 * included in both arrays, it will be excluded.
		 */
		$args['post__in'] = array_diff( $request['include'], $request['exclude'] );
	} elseif ( ! empty( $request['exclude'] ) ) {
		$args['post__not_in'] = $request['exclude'];
	} elseif ( ! empty( $request['include'] ) ) {
		$args['post__in'] = $request['include'];
	}

	if ( ! empty( $request['orderby'] ) ) {
		$args['orderby'] = $request['orderby'];

		if ( 'id' === $request['orderby'] ) {
			// Flip the case to uppercase for WP_Query.
			$args['orderby'] = 'ID';
		} elseif ( 'slug' === $request['orderby'] ) {
			$args['orderby'] = 'name';
		} elseif ( 'relevance' === $request['orderby'] ) {
			$args['orderby'] = 'relevance';

			// If ordering by relevance, a search term must be defined.
			if ( empty( $request['search'] ) ) {
				return new WP_Error(
					'rest_no_search_term_defined',
					__( 'You need to define a search term to order by relevance.', 'distributor' ),
					array( 'status' => 400 )
				);
			}
		} elseif ( 'include' === $request['orderby'] ) {
			$args['orderby'] = 'post__in';
		}
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

		$dt_post           = new DistributorPost( $post->ID );
		$formatted_posts[] = $dt_post->to_pull_list();
	}

	$response = rest_ensure_response( $formatted_posts );

	$response->header( 'X-WP-Total', (int) $total_posts );
	$response->header( 'X-WP-TotalPages', (int) $max_pages );

	return $response;
}

/**
 * Get a list of content to show on the Pull screen
 *
 * @since 2.0.0 Deprecated in favour of get_pull_content_list().
 *
 * @param array ...$args Arguments.
 * @return \WP_REST_Response|\WP_Error
 */
function get_pull_content( ...$args ) {
	_deprecated_function( __FUNCTION__, '2.0.0', __NAMESPACE__ . '\\get_pull_content_list' );
	return get_pull_content_list( ...$args );
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
