<?php

namespace Syndicate\PushUI;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', function() {
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_meta_boxes' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\admin_enqueue_scripts' );
	add_action( 'wp_ajax_sy_push', __NAMESPACE__  . '\ajax_push' );
} );

/**
 * Handle ajax pushing
 *
 * @since  1.0
 */
function ajax_push() {
	if ( ! check_ajax_referer( 'sy-push', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( empty( $_POST['post_id'] ) ) {
		wp_send_json_error();
		exit;
	}

	$connection_map = get_post_meta( $_POST['post_id'], 'sy_connection_map', true );
	if ( empty( $connection_map ) ) {
		$connection_map = array();
	}

	if ( empty( $connection_map['external'] ) ) {
		$connection_map['external'] = array();
	}

	if ( empty( $connection_map['internal'] ) ) {
		$connection_map['internal'] = array();
	}

	$external_push_results = array();

	if ( ! empty( $_POST['external_connections'] ) ) {
		foreach ( $_POST['external_connections'] as $external_connection_id ) {
			$external_connection_type = get_post_meta( $external_connection_id, 'sy_external_connection_type', true );
			$external_connection_url = get_post_meta( $external_connection_id, 'sy_external_connection_url', true );
			$external_connection_auth = get_post_meta( $external_connection_id, 'sy_external_connection_auth', true );

			if ( empty( $external_connection_auth ) ) {
				$external_connection_auth = array();
			}

			if ( ! empty( $external_connection_type ) && ! empty( $external_connection_url ) ) {
				$external_connection_class = \Syndicate\ExternalConnections::factory()->get_registered()[ $external_connection_type ];

				$auth_handler = new $external_connection_class::$auth_handler_class( $external_connection_auth );
				$mapping_handler = new $external_connection_class::$mapping_handler_class();

				$external_connection = new $external_connection_class( $external_connection_url, $auth_handler, $mapping_handler );

				$push_args = array();

				if ( ! empty( $connection_map['external'][ (int) $external_connection_id ] ) && ! empty( $connection_map['external'][ (int) $external_connection_id ]['post_id'] ) ) {
					$push_args['remote_post_id'] = (int) $connection_map['external'][ (int) $external_connection_id ]['post_id'];
				}

				$remote_id = $external_connection->push( $_POST['post_id'], $push_args );

				/**
				 * Record the external connection id's remote post id for this local post
				 */

				if ( ! is_wp_error( $remote_id ) ) {
					$connection_map['external'][ (int) $external_connection_id ] = array(
						'post_id' => (int) $remote_id,
						'time'    => time(),
					);

					$external_push_results[ (int) $external_connection_id ] = array(
						'post_id' => (int) $remote_id,
						'date'    => date( 'F j, Y g:i a' ),
						'status'  => 'success',
					);
				} else {
					$external_push_results[ (int) $external_connection_id ] = array(
						'post_id' => (int) $remote_id,
						'date'    => date( 'F j, Y g:i a' ),
						'status'  => 'fail',
					);
				}
			}
		}
	}

	$internal_push_results = array();

	if ( ! empty( $_POST['internal_connections'] ) ) {
		foreach ( $_POST['internal_connections'] as $blog_id ) {
			$internal_connection = new \Syndicate\InternalConnections\NetworkSiteConnection( get_site( $blog_id ) );
			$push_args = array();

			if ( ! empty( $connection_map['internal'][ (int) $blog_id ] ) && ! empty( $connection_map['internal'][ (int) $blog_id ]['post_id'] ) ) {
				$push_args['remote_post_id'] = (int) $connection_map['internal'][ (int) $blog_id ]['post_id'];
			}

			$remote_id = $internal_connection->push( $_POST['post_id'], $push_args );

			/**
			 * Record the internal connection id's remote post id for this local post
			 */
			if ( ! is_wp_error( $remote_id ) ) {
				$connection_map['internal'][ (int) $blog_id ] = array(
					'post_id' => (int) $remote_id,
					'time'    => time(),
				);

				$internal_push_results[ (int) $blog_id ] = array(
					'post_id' => (int) $remote_id,
					'date'    => date( 'F j, Y g:i a' ),
					'status'  => 'success',
				);
			} else {
				$internal_push_results[ (int) $blog_id ] = array(
					'post_id' => (int) $remote_id,
					'date'    => date( 'F j, Y g:i a' ),
					'status'  => 'fail',
				);
			}
		}
	}

	update_post_meta( $_POST['post_id'], 'sy_connection_map', $connection_map );


	wp_send_json_success( array(
		'results' => array(
			'internal' => $internal_push_results,
			'external' => $external_push_results,
		),
	) );

	exit;
}

/**
 * Enqueue admin scripts for push
 * 
 * @param  string $hook
 * @since  1.0
 */
function admin_enqueue_scripts( $hook ) {
    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
    	return;
    }

    if ( 'sy_ext_connection' === get_post_type() || ( ! empty( $_GET['post_type'] ) && 'sy_ext_connection' === $_GET['post_type'] ) ) {
    	return;
    }

    if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$js_path = '/assets/js/src/admin-push.js';
		$css_path = '/assets/css/admin-push.css';
	} else {
		$js_path = '/assets/js/admin-push.min.js';
		$css_path = '/assets/css/admin-push.min.css';
	}

	wp_enqueue_style( 'sy-admin-push', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );
    wp_enqueue_script( 'sy-admin-push', plugins_url( $js_path, __DIR__ ), array( 'jquery' ), SY_VERSION, true );
    wp_localize_script( 'sy-admin-push', 'sy', array(
		'nonce' => wp_create_nonce( 'sy-push' ),
		'successful_syndication' => esc_html__( 'Post successfully syndicated to all connections.', 'syndicate' ),
		'failed_syndication' => esc_html__( 'Post failed to syndicate to all connections.', 'syndicate' ),
		'half_successful_syndication' => esc_html__( 'Post syndicated successfully to some connections and unsuccessfully to others.', 'syndicate' ),
	) );
}


/**
 * Register meta boxes
 *
 * @since 1.0
 */
function add_meta_boxes() {
	$post_types = get_post_types( array(), 'object' );

	foreach ( $post_types as $post_type ) {
		if ( 'sy_ext_connection' === $post_type->name ) {
			continue;
		}

		add_meta_box( 'sy_push', esc_html__( 'Syndicate', 'syndicate' ), __NAMESPACE__ . '\meta_box', $post_type->name );
	}
}

/**
 * Output connection options meta box
 *
 * @since 1.0
 * @param WP_Post $post
 */
function meta_box( $post ) {
	wp_nonce_field( 'sy_external_connection_details_action', 'sy_external_connection_details' );

	$sites = \Syndicate\NetworkSiteConnections::factory()->get_available_authorized_sites();

	foreach ( $sites as $key => $site_array ) {
		if ( ! in_array( $post->post_type, $site_array['post_types'] ) ) {
			unset( $sites[ $key ] );
		}
	}

	$connection_map = get_post_meta( $post->ID, 'sy_connection_map', true );
	?>

	<div class="table-wrapper">
		<header><?php esc_html_e( 'Network Sites', 'syndicate' ); ?></header>

		<?php if ( ! empty( $sites ) ) : ?>
			<div class="table internal-connections-table">
				<div class="row header">
					<div class="checks"><a href="#" data-check="all" class="check"><?php esc_html_e( 'Check All', 'syndicate' ); ?></a> | <a data-check="none" class="check" href="#"><?php esc_html_e( 'Check None', 'syndicate' ); ?></a></div>
					<div><?php esc_html_e( 'Last Syndication', 'syndicate' ); ?></div>
				</div>
				<?php foreach ( $sites as $site_array ) : $site = $site_array['site']; ?>
					<div class="connection row" data-connection-id="<?php echo (int) $site->blog_id; ?>">
						<div><input id="int_check_<?php echo (int) $site->blog_id; ?>" type="checkbox" class="js-connection-checkbox" data-blog-id="<?php echo (int) $site->blog_id; ?>"> <label for="int_check_<?php echo (int) $site->blog_id; ?>"><?php echo esc_html( untrailingslashit( $site->domain . $site->path ) ); ?></label></div>
						<div class="last-syndicated">
							<?php if ( ! empty( $connection_map['internal'][ $site->blog_id ] ) ) : ?>
								<?php echo esc_html( date( 'F j, Y g:i a', $connection_map['internal'][ $site->blog_id ]['time'] ) ); ?>
							<?php else : ?>
								-
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p>
				<?php esc_html_e( 'There are no available sites.', 'syndicate' ); ?>
			</p>
		<?php endif; ?>

		<?php

		$external_connections_query = new \WP_Query( array(
			'post_type' => 'sy_ext_connection',
			'posts_per_page' => 200,
			'no_found_rows' => true,
			'post_status' => 'publish',
		) );

		$current_post_type = $post->post_type;
		$showing = 0;
		$external_connections = array();

		foreach ( $external_connections_query->posts as $external_connection ) {
			$external_connection_status = get_post_meta( $external_connection->ID, 'sy_external_connections', true );

			if ( empty( $external_connection_status ) || ! in_array( $current_post_type, $external_connection_status['can_post'] ) ) {
				continue;
			}

			$external_connections[] = $external_connection;
		}
		?>

		<header><?php esc_html_e( 'External Connections', 'syndicate' ); ?></header>

		<?php if ( ! empty( $external_connections ) ) : ?>
			<div class="table external-connections-table">
				<div class="row header">
					<div class="checks"><a href="#" data-check="all" class="check"><?php esc_html_e( 'Check All', 'syndicate' ); ?></a> | <a data-check="none" class="check" href="#"><?php esc_html_e( 'Check None', 'syndicate' ); ?></a></div>
					<div><?php esc_html_e( 'Last Syndication', 'syndicate' ); ?></div>
				</div>
				<?php foreach ( $external_connections as $external_connection ) : ?>
					<div class="connection row" data-connection-id="<?php echo (int) $external_connection->ID; ?>">
						<div><input type="checkbox" id="ext_check_<?php echo (int) $external_connection->ID; ?>" class="js-connection-checkbox" data-external-connection-id="<?php echo (int) $external_connection->ID; ?>"> <label for="ext_check_<?php echo (int) $external_connection->ID; ?>"><?php echo get_the_title( $external_connection->ID ); ?></label></div>
						<div class="last-syndicated">
							<?php if ( ! empty( $connection_map['external'][ $external_connection->ID ] ) && ! empty( $connection_map['external'][ $external_connection->ID ]['time'] ) ) : ?>
								<?php echo esc_html( date( 'F j, Y g:i a', $connection_map['external'][ $external_connection->ID ]['time'] ) ); ?>
							<?php else : ?>
								-
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p>
				<?php printf( __( 'There are no available external connections. <a href="%s">Create one?</a>', 'syndicate' ), esc_url( admin_url( 'post-new.php?post_type=sy_ext_connection' ) ) ); ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="push-button-wrapper">
		<button disabled class="button button-primary js-syndicate"><?php esc_html_e( 'Push Post', 'syndicate' ); ?></button>
	</div>
	<div class="message"></div>
	<p class="draft-message"><?php esc_html_e( 'All content is pushed in draft status.', 'syndicate' ); ?></p>

	<?php
}
