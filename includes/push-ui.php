<?php

namespace Syndicate\PushUI;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', function() {
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\enqueue_scripts' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__  . '\enqueue_scripts' );
	add_action( 'wp_ajax_sy_push', __NAMESPACE__  . '\ajax_push' );
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\menu_button', 999 );
	add_action( 'wp_footer', __NAMESPACE__ . '\menu_content', 10, 1 );
} );

/**
 * Let's setup our syndicate menu in the toolbar
 *
 * @param object $wp_admin_bar
 * @since  1.0
 */
function menu_button( $wp_admin_bar ) {
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	if ( ! is_single() ) {
		return;
	}

	$wp_admin_bar->add_node( array(
		'id' => 'syndicate',
		'title' => esc_html__( 'Syndicate', 'syndicate' ),
		'href' => '#',
	) );
}

function menu_content() {
	global $post;

	$sites = \Syndicate\NetworkSiteConnections::factory()->get_available_authorized_sites();
	foreach ( $sites as $key => $site_array ) {
		if ( in_array( $post->post_type, $site_array['post_types'] ) ) {
			$connections[] = new \Syndicate\InternalConnections\NetworkSiteConnection( $site_array['site'] );
		}
	}

	$external_connections_query = new \WP_Query( array(
		'post_type' => 'sy_ext_connection',
		'posts_per_page' => 200,
		'no_found_rows' => true,
		'post_status' => 'publish',
	) );

	foreach ( $external_connections_query->posts as $external_connection ) {
		$external_connection_status = get_post_meta( $external_connection->ID, 'sy_external_connections', true );
		$allowed_roles = get_post_meta( $external_connection->ID, 'sy_external_connection_allowed_roles', true );
		if ( empty( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator', 'editor' );
		}

		/*if ( empty( $external_connection_status ) || ! in_array( $current_post_type, $external_connection_status['can_post'] ) ) {
			continue;
		}

		// If not admin lets make sure the current user can push to this connection
		if ( ! current_user_can( 'manage_options' ) ) {
			$current_user_roles = (array) wp_get_current_user()->roles;
			
			if ( count( array_intersect( $current_user_roles, $allowed_roles ) ) < 1 ) {
				continue;
			}
		}*/

		$connection = \Syndicate\ExternalConnections::factory()->instantiate( $external_connection->ID );

		if ( ! is_wp_error( $connection ) ) {
			$connections[] = $connection;
		}
	}
	?>
	<div class="syndicate-push-wrapper">
		<div class="inner">
			<p><?php echo sprintf( __( 'Post &quot;%s&quot; to other connections.', 'syndicate' ), get_the_title( $post->ID ) ); ?></p>

			<?php if ( 1 < count( $connections ) ) : ?>
				<div class="connections-selector">
					<?php if ( 5 < count( $connections ) ) : ?>
						<input type="text" id="sy-connection-search" placeholder="<?php esc_html_e( 'Search available connections', 'syndicate' ); ?>">
					<?php endif; ?>

					<div class="new-connections-list">
						<?php $i = 0; foreach ( $connections as $connection ) : if ( $i >= 5 ) break; ?>
							<?php if ( is_a( $connection, '\Syndicate\ExternalConnection' ) ) : ?>
								<a class="add-connection" data-connection-type="external" data-connection-id="<?php echo (int) $connection->ID; ?>"><?php echo get_the_title( $connection->id ); ?></a>
							<?php else : ?>
								<a class="add-connection" data-connection-type="internal" data-connection-id="<?php echo (int) $connection->site->blog_id; ?>"><?php echo untrailingslashit( $connection->site->domain . $connection->site->path ); ?></a>
							<?php endif; ?>
						<?php $i++; endforeach; ?>
					</div>
				</div>
				<div class="connections-selected">
					<header><?php esc_html_e( 'Selected sites', 'syndicate' ); ?></header>

					<div class="selected-connections-list"></div>

					<div class="action-wrapper">
						<button><?php esc_html_e( 'Syndicate', 'syndicate' ); ?></button>
						<label for="syndicate-as-draft" class="as-draft"><input type="checkbox" id="syndicate-as-draft"> <?php esc_html_e( 'As draft', 'syndicate' ); ?></label>
					</div>
				</div>
			<?php else : ?>

			<?php endif; ?>
		</div>
	</div>
	<?php
}

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

				$external_connection = new $external_connection_class( get_the_title( $external_connection_id ), $external_connection_url, $external_connection_id, $auth_handler, $mapping_handler );

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
 * Enqueue scripts/styles for push
 * 
 * @param  string $hook
 * @since  1.0
 */
function enqueue_scripts( $hook ) {
    if ( is_admin() ) {
	    if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
	    	return;
	    }

	    if ( 'sy_ext_connection' === get_post_type() || ( ! empty( $_GET['post_type'] ) && 'sy_ext_connection' === $_GET['post_type'] ) ) {
	    	return;
	    }
	} else {
		if ( ! is_single() ) {
			return;
		}
	}

    if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$js_path = '/assets/js/src/push.js';
		$css_path = '/assets/css/push.css';
	} else {
		$js_path = '/assets/js/push.min.js';
		$css_path = '/assets/css/push.min.css';
	}

	wp_enqueue_style( 'sy-push', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );
    wp_enqueue_script( 'sy-push', plugins_url( $js_path, __DIR__ ), array( 'jquery' ), SY_VERSION, true );
    wp_localize_script( 'sy-push', 'sy', array(
		'nonce' => wp_create_nonce( 'sy-push' ),
	) );
}

