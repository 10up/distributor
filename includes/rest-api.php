<?php

namespace Distributor\RestApi;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'init', function() {
			add_action( 'rest_api_init', __NAMESPACE__ . '\register_endpoints' );

			$post_types = get_post_types(
				array(
					'show_in_rest' => true,
				)
			);

			foreach ( $post_types as $post_type ) {
				add_action( "rest_insert_{$post_type}", __NAMESPACE__ . '\process_distributor_attributes', 10, 3 );
			}
		}, 100
	);
}

/**
 * When an API push is being received, handle Distributor specific attributes
 *
 * @param WP_Post         $post
 * @param WP_REST_Request $request
 * @param bool            $update
 * @since 1.0
 */
function process_distributor_attributes( $post, $request, $update ) {
	if ( empty( $post ) || is_wp_error( $post ) ) {
		return;
	}

	if ( ! empty( $request['distributor_remote_post_id'] ) ) {
		update_post_meta( $post->ID, 'dt_original_post_id', (int) $request['distributor_remote_post_id'] );
	}

	if ( ! empty( $request['distributor_original_source_id'] ) ) {
		update_post_meta( $post->ID, 'dt_original_source_id', (int) $request['distributor_original_source_id'] );
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
		$post_types, 'distributor_meta', array(
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
		$post_types, 'distributor_terms', array(
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
		$post_types, 'distributor_media', array(
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
		$post_types, 'distributor_original_site_name', array(
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
		$post_types, 'distributor_original_site_url', array(
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
