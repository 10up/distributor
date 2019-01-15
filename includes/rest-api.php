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
 * @param stdClass        $prepared_post An object representing a single post prepared.
 * @param WP_REST_Request $request       Request object.
 *
 * @return stdClass $prepared_post The filtered post object.
 */
function filter_distributor_content( $prepared_post, $request ) {

	if ( \Distributor\Utils\is_using_gutenberg() && isset( $request['distributor_raw_content'] ) ) {
		if ( \Distributor\Utils\dt_use_block_editor_for_post_type( $prepared_post->post_type ) ) {
			$prepared_post->post_content = $request['distributor_raw_content'];
		}
	}
	return $prepared_post;
}

/**
 * When an API push is being received, handle Distributor specific attributes
 *
 * @param WP_Post         $post Post object.
 * @param WP_REST_Request $request Request object.
 * @param bool            $update Update or create.
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
	 * Action fired after an API push is handled by Distributor.
	 *
	 * @since 1.0
	 *
	 * @param WP_Post         $post    Inserted or updated post object.
	 * @param WP_REST_Request $request Request object.
	 * @param bool            $update  True when creating a post, false when updating.
	 */
	do_action( 'dt_process_distributor_attributes', $post, $request, $update );
}

/**
 * Filter the data requested over REST API when a post is pulled.
 *
 * @param WP_REST_Response $response Response object.
 * @param WP_Post          $post     Post object.
 * @param WP_REST_Request  $request  Request object.
 *
 * @return WP_REST_Response $response The filtered response object.
 */
function prepare_distributor_content( $response, $post, $request ) {

	// Only adjust distributor requests.
	if ( '1' !== $request->get_param( 'distributor_request' ) ) {
		return $response;
	}
	// Is the local site is running Gutenberg?
	if (  \Distributor\Utils\is_using_gutenberg() ) {
		$post_data = $response->get_data();
		$post_data['is_using_gutenberg'] = true;
		$response->set_data( $post_data );
	}

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
				return get_bloginfo( 'name' );
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
				return home_url();
			},
			'update_callback' => function( $value, $post ) { },
			'schema'          => array(
				'description' => esc_html__( 'Original site url for Distributor.', 'distributor' ),
				'type'        => 'string',
			),
		)
	);
}
