<?php

namespace Distributor\PullUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
add_action( 'plugins_loaded', function() {
	add_action( 'admin_menu', __NAMESPACE__  . '\action_admin_menu' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\admin_enqueue_scripts' );
	add_action( 'load-distributor_page_pull', __NAMESPACE__ . '\setup_list_table' );
	add_filter( 'set-screen-option', __NAMESPACE__ . '\set_screen_option', 10, 3 );
} );

/**
 * Create list table
 *
 * @since 0.8
 */
function setup_list_table() {
	global $connection_list_table;
	global $connection_now;
	global $dt_pull_messages;

	if ( ! empty( $_COOKIE['dt-skipped'] ) ) {
		$dt_pull_messages['skipped'] = 1;

		setcookie( 'dt-skipped', 1, time() - 60, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
	} elseif ( ! empty( $_COOKIE['dt-syndicated'] ) ) {
		$dt_pull_messages['syndicated'] = 1;

		setcookie( 'dt-syndicated', 1, time() - 60, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
	}

	$external_connections = new \WP_Query( array(
		'post_type'      => 'dt_ext_connection',
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'posts_per_page' => 100,
	) );

	$connection_list_table = new \Distributor\PullListTable();

	global $connection_now;

	$sites = \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites();

	foreach ( $sites as $site_array ) {
		$internal_connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site_array['site'] );
		$connection_list_table->connection_objects[] = $internal_connection;

		if ( ! empty( $_GET['connection_id'] ) && ! empty( $_GET['connection_type'] ) && 'internal' === $_GET['connection_type'] && (int) $internal_connection->site->blog_id === (int) $_GET['connection_id'] ) {
			$connection_now = $internal_connection;
		}
	}

	foreach ( $external_connections->posts as $external_connection_id ) {
		$external_connection_status = get_post_meta( $external_connection_id, 'dt_external_connections', true );

		if ( empty( $external_connection_status ) || empty( $external_connection_status['can_get'] ) ) {
			continue;
		}

		$external_connection = \Distributor\ExternalConnection::instantiate( $external_connection_id );

		if ( ! is_wp_error( $external_connection ) ) {
			$connection_list_table->connection_objects[] = $external_connection;

			if ( ! empty( $_GET['connection_id'] ) && ! empty( $_GET['connection_type'] ) && 'external' === $_GET['connection_type'] && (int) $external_connection_id === (int) $_GET['connection_id'] ) {
				$connection_now = $external_connection;
			}
		}
	}

	if ( empty( $connection_now ) && ! empty( $connection_list_table->connection_objects ) ) {
		$connection_now = $connection_list_table->connection_objects[0];
	}

	process_actions();
}

/**
 * Enqueue admin scripts for pull
 *
 * @param  string $hook
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( 'distributor_page_pull' !== $hook || empty( $_GET['page'] ) || 'pull' !== $_GET['page'] ) {
		return;
	}

	if ( defined( SCRIPT_DEBUG ) && SCRIPT_DEBUG ) {
		$js_path = '/assets/js/src/admin-pull.js';
		$css_path = '/assets/css/admin-pull-table.css';
	} else {
		$js_path = '/assets/js/admin-pull.min.js';
		$css_path = '/assets/css/admin-pull-table.min.css';
	}

	wp_enqueue_script( 'dt-admin-pull', plugins_url( $js_path, __DIR__ ), array( 'jquery' ), DT_VERSION, true );
	wp_enqueue_style( 'dt-admin-pull', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
}

/**
 * Set up admin menu
 *
 * @since 0.8
 */
function action_admin_menu() {
	$hook = add_submenu_page(
		'distributor',
		esc_html__( 'Pull Content', 'distributor' ),
		esc_html__( 'Pull Content', 'distributor' ),
		'manage_options',
		'pull',
		__NAMESPACE__  . '\dashboard'
	);

	add_action( "load-$hook", __NAMESPACE__  . '\screen_option' );
}

/**
 * Set screen option for posts per page
 * 
 * @param  string $status
 * @param  string $option
 * @param  mixed $value
 * @since  0.8
 * @return mixed
 */
function set_screen_option( $status, $option, $value ) {
	return $value;
}

/**
 * Set up screen options
 *
 * @since 0.8
 */
function screen_option() {

	$option = 'per_page';
	$args   = [
		'label'   => esc_html__( 'Posts per page', 'distributor' ),
		'option'  => 'pull_posts_per_page',
		'default' => get_option( 'posts_per_page' ),
	];

	add_screen_option( $option, $args );
}

/**
 * Process content changing actions
 *
 * @since  0.8
 */
function process_actions() {
	global $connection_list_table;

	switch ( $connection_list_table->current_action() ) {
		case 'syndicate':
		case 'bulk-syndicate':
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-distributor_page_pull' ) ) {
				exit;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die(
					'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to add this item.' ) . '</p>',
					403
				);
			}

			if ( empty( $_GET['connection_type'] ) || empty( $_GET['connection_id'] ) || empty( $_GET['post'] ) ) {
				break;
			}

			$posts = $_GET['post'];
			if ( ! is_array( $posts ) ) {
				$posts = [ $posts ];
			}

			if ( 'external' === $_GET['connection_type'] ) {
				$connection = \Distributor\ExternalConnection::instantiate( $_GET['connection_id'] );
				$new_posts = $connection->pull( $posts );
			} else {
				$site = get_site( $_GET['connection_id'] );
				$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site );
				$new_posts = $connection->pull( $posts );
			}

			$post_id_mappings = array();

			foreach ( $posts as $key => $old_post_id ) {
				$post_id_mappings[ $old_post_id ] = $new_posts[ $key ];
			}

			$connection->log_sync( $post_id_mappings );

			setcookie( 'dt-syndicated', 1, time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );

			wp_redirect( wp_get_referer() );
			exit;

			break;
		case 'bulk-skip':
		case 'skip':
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dt_skip' ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-syndicate_page_pull' ) ) {
				exit;
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die(
					'<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
					'<p>' . __( 'Sorry, you are not allowed to add this item.' ) . '</p>',
					403
				);
			}

			if ( empty( $_GET['connection_type'] ) || empty( $_GET['connection_id'] ) || empty( $_GET['post'] ) ) {
				break;
			}

			if ( 'external' === $_GET['connection_type'] ) {
				$connection = \Distributor\ExternalConnection::instantiate( $_GET['connection_id'] );
			} else {
				$site = get_site( $_GET['connection_id'] );
				$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site );
			}

			$posts = $_GET['post'];
			if ( ! is_array( $posts ) ) {
				$posts = [ $posts ];
			}

			$post_mapping = array();

			foreach ( $posts as $post_id ) {
				$post_mapping[ $post_id ] = false;
			}

			$connection->log_sync( $post_mapping );

			setcookie( 'dt-skipped', 1, time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );

			wp_redirect( wp_get_referer() );
			exit;

			break;
	}
}

/**
 * Output pull dashboard with custom list table
 *
 * @since 0.8
 */
function dashboard() {
	global $connection_list_table;
	global $connection_now;
	global $dt_pull_messages;

	$connection_list_table->prepare_items();

	$pagenum = $connection_list_table->get_pagenum();

	if ( ! empty( $connection_now ) ) {
		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$connection_type = 'external';
			$connection_id = $connection_now->id;
		} else {
			$connection_type = 'internal';
			$connection_id = $connection_now->site->blog_id;
		}
	}

	$internal_connection_group = array();
	$external_connection_group = array();

	foreach ( $connection_list_table->connection_objects as $connection ) {
		if ( is_a( $connection, '\Distributor\ExternalConnection' ) ) {
			$external_connection_group[] = $connection;
		} else {
			$internal_connection_group[] = $connection;
		}
	}
	?>
	<div class="wrap nosubsub">
		<h1>
			<?php if ( empty( $connection_list_table->connection_objects ) ) : $connection_now = 0; ?>
				<?php printf( __( 'No Connections to Pull from, <a href="%s">Create One</a>?', 'distributor' ), esc_url( admin_url( 'post-new.php?post_type=dt_ext_connection' ) ) ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Pull Content from', 'distributor' ); ?>
				<select id="pull_connections" name="connection" method="get">
					<?php if ( ! empty( $internal_connection_group ) ) : ?>
						<?php if ( ! empty( $external_connection_group ) ) : ?>
							<optgroup label="<?php esc_html_e( 'Network Connections', 'distributor' ); ?>">
						<?php endif; ?>
							<?php foreach ( $internal_connection_group as $connection ) :
								$selected = false;
								$type = 'internal';
								$name = untrailingslashit( $connection->site->domain . $connection->site->path );
								$id = $connection->site->blog_id;

								if ( (int) $connection_now->site->blog_id === (int) $id ) {
									$selected = true;
								}
								?>
								<option <?php selected( true, $selected ); ?> data-pull-url="<?php echo esc_url( admin_url( 'admin.php?page=pull&connection_type=' . $type .'&connection_id=' . $id ) ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						<?php if ( ! empty( $external_connection_group ) ) : ?>
							</optgroup>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $external_connection_group ) ) : ?>
						<?php if ( ! empty( $internal_connection_group ) ) : ?>
							<optgroup label="<?php esc_html_e( 'External Connections (beta)', 'distributor' ); ?>">
						<?php endif; ?>
							<?php foreach ( $external_connection_group as $connection ) :
								$type = 'external';
								$selected = false;
								$name = $connection->name;
								$id = $connection->id;

								if ( (int) $connection_now->id === (int) $id ) {
									$selected = true;
								}
								?>
								<option <?php selected( true, $selected ); ?> data-pull-url="<?php echo esc_url( admin_url( 'admin.php?page=pull&connection_type=' . $type .'&connection_id=' . $id ) ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						<?php if ( ! empty( $internal_connection_group ) ) : ?>
							</optgroup>
						<?php endif; ?>
					<?php endif; ?>
				</select>
			<?php endif; ?>
		</h1>

		<?php if ( ! empty( $connection_now ) && is_a( $connection_now, '\Distributor\ExternalConnection' ) ) : ?>
			<div class="network-connections-notice">
				<strong><?php esc_html_e( "External connections are in beta. We can't push or pull meta data or images from external websites.", 'distributor' ); ?></strong>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['skipped'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been marked as skipped.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['syndicated'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been pulled.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php $connection_list_table->views(); ?>

		<form id="posts-filter" class="status-<?php echo ( ! empty( $_GET['status'] ) ) ? esc_attr( $_GET['status'] ) : 'new'; ?>" method="get">
			<input type="hidden" name="connection_type" value="<?php echo esc_attr( $connection_type ); ?>">
			<input type="hidden" name="connection_id" value="<?php echo esc_attr( $connection_id ); ?>">
			<input type="hidden" name="page" value="pull">

			<?php $connection_list_table->search_box( esc_html__( 'Search', 'distributor' ), 'post' ); ?>

			<?php $connection_list_table->display(); ?>
		</form>
	</div>
	<?php
}
