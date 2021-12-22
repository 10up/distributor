<?php
/**
 * Pull UI functionality
 *
 * @package  distributor
 */

namespace Distributor\PullUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'admin_menu', __NAMESPACE__ . '\action_admin_menu' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
			add_action( 'load-distributor_page_pull', __NAMESPACE__ . '\setup_list_table' );
			add_filter( 'set-screen-option', __NAMESPACE__ . '\set_screen_option', 10, 3 );
		}
	);
}

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
	} elseif ( ! empty( $_COOKIE['dt-unskipped'] ) ) {
		$dt_pull_messages['unskipped'] = 1;

		setcookie( 'dt-unskipped', 1, time() - 60, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
	} elseif ( ! empty( $_COOKIE['dt-syndicated'] ) ) {
		$dt_pull_messages['syndicated'] = 1;

		setcookie( 'dt-syndicated', 1, time() - 60, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
	} elseif ( ! empty( $_COOKIE['dt-duplicated'] ) ) {
		$dt_pull_messages['duplicated'] = 1;

		setcookie( 'dt-duplicated', 1, time() - 60, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
	}

	$external_connections = new \WP_Query(
		array(
			'post_type'      => 'dt_ext_connection',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => 100,
		)
	);

	$connection_list_table = new \Distributor\PullListTable();

	if ( ! empty( \Distributor\Connections::factory()->get_registered()['networkblog'] ) ) {
		$sites = \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites( 'pull' );

		foreach ( $sites as $site_array ) {
			$internal_connection                         = new \Distributor\InternalConnections\NetworkSiteConnection( $site_array['site'] );
			$connection_list_table->connection_objects[] = $internal_connection;

			if ( ! empty( $_GET['connection_id'] ) && ! empty( $_GET['connection_type'] ) && 'internal' === $_GET['connection_type'] && (int) $internal_connection->site->blog_id === (int) $_GET['connection_id'] ) { // @codingStandardsIgnoreLine Content is type casted, no need for nonce.
				$connection_now = $internal_connection;
			}
		}
	}

	foreach ( $external_connections->posts as $external_connection_id ) {
		$external_connection_type = get_post_meta( $external_connection_id, 'dt_external_connection_type', true );

		if ( empty( \Distributor\Connections::factory()->get_registered()[ $external_connection_type ] ) ) {
			continue;
		}

		$external_connection_status = get_post_meta( $external_connection_id, 'dt_external_connections', true );

		if ( empty( $external_connection_status ) || empty( $external_connection_status['can_get'] ) ) {
			continue;
		}

		$external_connection = \Distributor\ExternalConnection::instantiate( $external_connection_id );

		if ( ! is_wp_error( $external_connection ) ) {
			$connection_list_table->connection_objects[] = $external_connection;

			if ( ! empty( $_GET['connection_id'] ) && ! empty( $_GET['connection_type'] ) && 'external' === $_GET['connection_type'] && (int) $external_connection_id === (int) $_GET['connection_id'] ) { // @codingStandardsIgnoreLine Content is type casted, no need for nonce.
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
 * @param  string $hook WP hook.
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( 'distributor_page_pull' !== $hook || empty( $_GET['page'] ) || 'pull' !== $_GET['page'] ) { // @codingStandardsIgnoreLine Comparing values, not using them.
		return;
	}

	wp_enqueue_script( 'dt-admin-pull', plugins_url( '/dist/js/admin-pull.min.js', __DIR__ ), array( 'jquery' ), DT_VERSION, true );
	wp_enqueue_style( 'dt-admin-pull', plugins_url( '/dist/css/admin-pull-table.min.css', __DIR__ ), array(), DT_VERSION );

	wp_localize_script(
		'dt-admin-pull',
		'dt',
		[
			'pull'     => __( 'Pull', 'distributor' ),
			'as_draft' => __( 'Pull as draft', 'distributor' ),
		]
	);
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
		/**
		 * Filter Distributor capabilities allowed to pull content.
		 *
		 * @since 1.0.0
		 * @hook dt_pull_capabilities
		 *
		 * @param {string} 'manage_options' The capability allowed to pull content.
		 *
		 * @return {string} The capability allowed to pull content.
		 */
		apply_filters( 'dt_pull_capabilities', 'manage_options' ),
		'pull',
		__NAMESPACE__ . '\dashboard'
	);

	add_action( "load-$hook", __NAMESPACE__ . '\screen_option' );
}

/**
 * Set screen option for posts per page
 *
 * @param  string $status Option status.
 * @param  string $option Option.
 * @param  mixed  $value New value.
 * @since  0.8
 * @return mixed
 */
function set_screen_option( $status, $option, $value ) {
	return 'pull_posts_per_page' === $option ? $value : $status;
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
	global $dt_pull_messages;

	switch ( $connection_list_table->current_action() ) {
		case 'syndicate':
		case 'bulk-syndicate':
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-distributor_page_pull' ) ) {
				exit;
			}

			// Filter documented above.
			if ( ! current_user_can( apply_filters( 'dt_pull_capabilities', 'manage_options' ) ) ) {
				wp_die(
					'<h1>' . esc_html__( 'Cheatin&#8217; uh?', 'distributor' ) . '</h1>' .
					'<p>' . esc_html__( 'Sorry, you are not allowed to add this item.', 'distributor' ) . '</p>',
					403
				);
			}

			if ( empty( $_GET['connection_type'] ) || empty( $_GET['connection_id'] ) || empty( $_GET['post'] ) ) {
				break;
			}

			$posts       = (array) $_GET['post'];
			$post_type   = sanitize_text_field( $_GET['pull_post_type'] );
			$post_status = ! empty( $_GET['dt_as_draft'] ) && 'draft' === $_GET['dt_as_draft'] ? 'draft' : '';

			$posts = array_map(
				function( $remote_post_id ) use ( $post_type, $post_status ) {
						return [
							'remote_post_id' => $remote_post_id,
							'post_type'      => $post_type,
							'post_status'    => $post_status,
						];
				},
				$posts
			);

			if ( 'external' === $_GET['connection_type'] ) {
				$connection = \Distributor\ExternalConnection::instantiate( intval( $_GET['connection_id'] ) );
				$new_posts  = $connection->pull( $posts );
				$error_key  = "external_{$connection->id}";

				foreach ( $posts as $key => $post_array ) {
					if ( is_wp_error( $new_posts[ $key ] ) ) {
						continue;
					}
					\Distributor\Subscriptions\create_remote_subscription( $connection, $post_array['remote_post_id'], $new_posts[ $key ] );
				}
			} else {
				$site       = get_site( intval( $_GET['connection_id'] ) );
				$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site );
				$new_posts  = $connection->pull( $posts );
				$error_key  = "internal_{$connection->site->blog_id}";
			}

			$post_id_mappings = array();
			$pull_errors      = array();

			foreach ( $posts as $key => $post_array ) {
				if ( is_wp_error( $new_posts[ $key ] ) ) {
					$pull_errors[ $post_array['remote_post_id'] ] = [ $new_posts[ $key ]->get_error_message() ];
					continue;
				}
				$post_id_mappings[ $post_array['remote_post_id'] ] = $new_posts[ $key ];

				$media_errors = get_transient( 'dt_media_errors_' . $new_posts[ $key ] );

				if ( ! empty( $media_errors ) ) {
					delete_transient( 'dt_media_errors_' . $new_posts[ $key ] );
					$pull_errors[ $post_array['remote_post_id'] ] = $media_errors;
				}
			}

			if ( ! empty( $pull_errors ) ) {
				set_transient( 'dt_connection_pull_errors_' . $error_key, $pull_errors, DAY_IN_SECONDS );
			}

			$connection->log_sync( $post_id_mappings );

			if ( empty( $dt_pull_messages['duplicated'] ) ) {
				setcookie( 'dt-syndicated', 1, time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
			}

			if ( ! empty( $dt_pull_messages['duplicated'] ) ) {
				setcookie( 'dt-duplicated', 1, time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );
			}

			// Redirect to the pulled content tab
			wp_safe_redirect( add_query_arg( 'status', 'pulled', wp_get_referer() ) );
			exit;
		case 'bulk-skip':
		case 'skip':
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dt_skip' ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-distributor_page_pull' ) ) {
				exit;
			}

			// Filter documented above.
			if ( ! current_user_can( apply_filters( 'dt_pull_capabilities', 'manage_options' ) ) ) {
				wp_die(
					'<h1>' . esc_html__( 'Cheatin&#8217; uh?', 'distributor' ) . '</h1>' .
					'<p>' . esc_html__( 'Sorry, you are not allowed to add this item.', 'distributor' ) . '</p>',
					403
				);
			}

			if ( empty( $_GET['connection_type'] ) || empty( $_GET['connection_id'] ) || empty( $_GET['post'] ) ) {
				break;
			}

			if ( 'external' === $_GET['connection_type'] ) {
				$connection = \Distributor\ExternalConnection::instantiate( intval( $_GET['connection_id'] ) );
			} else {
				$site       = get_site( intval( $_GET['connection_id'] ) );
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

			// Redirect to the skipped content tab
			wp_safe_redirect( add_query_arg( 'status', 'skipped', wp_get_referer() ) );
			exit;
		case 'bulk-unskip':
		case 'unskip':
			if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dt_unskip' ) && ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-distributor_page_pull' ) ) {
				exit;
			}

			// Filter documented above.
			if ( ! current_user_can( apply_filters( 'dt_pull_capabilities', 'manage_options' ) ) ) {
				wp_die(
					'<h1>' . esc_html__( 'Cheatin&#8217; uh?', 'distributor' ) . '</h1>' .
					'<p>' . esc_html__( 'Sorry, you are not allowed to add this item.', 'distributor' ) . '</p>',
					403
				);
			}

			if ( empty( $_GET['connection_type'] ) || empty( $_GET['connection_id'] ) || empty( $_GET['post'] ) ) {
				break;
			}

			if ( 'external' === $_GET['connection_type'] ) {
				$connection = \Distributor\ExternalConnection::instantiate( intval( $_GET['connection_id'] ) );
			} else {
				$site       = get_site( intval( $_GET['connection_id'] ) );
				$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site );
			}

			$posts = $_GET['post'];
			if ( ! is_array( $posts ) ) {
				$posts = [ $posts ];
			}

			$sync_log = $connection->get_sync_log( intval( $_GET['connection_id'] ) );

			foreach ( $posts as $post_id ) {
				if ( array_key_exists( $post_id, $sync_log ) ) {
					unset( $sync_log[ $post_id ] );
				}
			}

			$connection->log_sync( $sync_log, intval( $_GET['connection_id'] ), true );

			setcookie( 'dt-unskipped', 1, time() + DAY_IN_SECONDS, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, is_ssl() );

			// Redirect to the new content tab
			wp_safe_redirect( add_query_arg( 'status', 'new', wp_get_referer() ) );
			exit;
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

	if ( ! empty( $connection_now ) ) {
		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$connection_type = 'external';
			$connection_id   = $connection_now->id;
		} else {
			$connection_type = 'internal';
			$connection_id   = $connection_now->site->blog_id;
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
			<?php
			if ( empty( $connection_list_table->connection_objects ) ) :
				$connection_now = 0;
				?>
				<?php esc_html_e( 'No Connections to Pull from,', 'distributor' ); ?>

				<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dt_ext_connection' ) ); ?>"><?php esc_html_e( 'Create One?', 'distributor' ); ?></a>

			<?php else : ?>
				<?php esc_html_e( 'Pull Content from', 'distributor' ); ?>
				<select id="pull_connections" name="connection" method="get">
					<?php if ( ! empty( $internal_connection_group ) ) : ?>
						<?php if ( ! empty( $external_connection_group ) ) : ?>
							<optgroup label="<?php esc_html_e( 'Network Connections', 'distributor' ); ?>">
						<?php endif; ?>
							<?php
							foreach ( $internal_connection_group as $connection ) :
								$selected = false;
								$type     = 'internal';
								$name     = untrailingslashit( $connection->site->domain . $connection->site->path );
								$id       = $connection->site->blog_id;

								if ( is_a( $connection_now, '\Distributor\InternalConnections\NetworkSiteConnection' ) && (int) $connection_now->site->blog_id === (int) $id ) {
									$selected = true;
								}
								?>
								<option <?php selected( true, $selected ); ?> data-pull-url="<?php echo esc_url( admin_url( 'admin.php?page=pull&connection_type=' . $type . '&connection_id=' . $id ) ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						<?php if ( ! empty( $external_connection_group ) ) : ?>
							</optgroup>
						<?php endif; ?>
					<?php endif; ?>

					<?php if ( ! empty( $external_connection_group ) ) : ?>
						<?php if ( ! empty( $internal_connection_group ) ) : ?>
							<optgroup label="<?php esc_html_e( 'External Connections', 'distributor' ); ?>">
						<?php endif; ?>
							<?php
							foreach ( $external_connection_group as $connection ) :
								$type     = 'external';
								$selected = false;
								$name     = $connection->name;
								$id       = $connection->id;

								if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) && (int) $connection_now->id === (int) $id ) {
									$selected = true;
								}
								?>
								<option <?php selected( true, $selected ); ?> data-pull-url="<?php echo esc_url( admin_url( 'admin.php?page=pull&connection_type=' . $type . '&connection_id=' . $id ) ); ?>"><?php echo esc_html( $name ); ?></option>
							<?php endforeach; ?>
						<?php if ( ! empty( $internal_connection_group ) ) : ?>
							</optgroup>
						<?php endif; ?>
					<?php endif; ?>
				</select>

				<?php
				$connection_now->pull_post_types = \Distributor\Utils\available_pull_post_types( $connection_now, $connection_type );

				// Ensure we have at least one post type to pull.
				$connection_now->pull_post_type = '';
				if ( ! empty( $connection_now->pull_post_types ) ) {
					$connection_now->pull_post_type = ( 'internal' === $connection_type ) ? 'all' : $connection_now->pull_post_types[0]['slug'];
				}

				// Set the post type we want to pull (if any)
				// This is either from a query param, "post" post type, or the first in the list
				foreach ( $connection_now->pull_post_types as $post_type ) {
					if ( isset( $_GET['pull_post_type'] ) ) { // @codingStandardsIgnoreLine No nonce needed here.
						if ( $_GET['pull_post_type'] === $post_type['slug'] ) { // @codingStandardsIgnoreLine Comparing values, no nonce needed.
							$connection_now->pull_post_type = $post_type['slug'];
							break;
						}
					} else {
						if ( 'post' === $post_type['slug'] && 'external' === $connection_type ) {
							$connection_now->pull_post_type = $post_type['slug'];
							break;
						}
					}
				}
				?>

			<?php endif; ?>
		</h1>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['skipped'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been marked as skipped.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['unskipped'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been unskipped.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['syndicated'] ) ) : ?>
			<div id="message" class="updated notice is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been pulled.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $dt_pull_messages ) && ! empty( $dt_pull_messages['duplicated'] ) ) : ?>
			<div id="message" class="notice notice-warning is-dismissible">
				<p><?php esc_html_e( 'Post(s) have been already distributed.', 'distributor' ); ?></p>
			</div>
		<?php endif; ?>

		<?php output_pull_errors(); ?>

		<?php $connection_list_table->prepare_items(); ?>

		<?php if ( ! empty( $connection_list_table->pull_error ) ) : ?>
			<p><?php esc_html_e( 'Could not pull content from connection due to error.', 'distributor' ); ?></p>
			<ul>
				<?php foreach ( $connection_list_table->pull_error as $error ) : ?>
				<li>
					<ul>
						<li><?php echo esc_html( $error ); ?></li>
					</ul>
				</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<?php $connection_list_table->views(); ?>

			<form id="posts-filter" class="status-<?php echo ( ! empty( $_GET['status'] ) ) ? esc_attr( $_GET['status'] ) : 'new'; // @codingStandardsIgnoreLine Nonce not needed. ?>" method="get">
				<?php if ( ! empty( $connection_list_table->connection_objects ) ) : ?>
					<input type="hidden" name="connection_type" value="<?php echo esc_attr( $connection_type ); ?>">
					<input type="hidden" name="connection_id" value="<?php echo esc_attr( $connection_id ); ?>">
				<?php endif; ?>

				<input type="hidden" name="page" value="pull">

				<?php $connection_list_table->search_box( esc_html__( 'Search', 'distributor' ), 'post' ); ?>

				<?php $connection_list_table->display(); ?>
			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Get pull errors saved in transient and display it to user.
 *
 * @todo Log persistent
 */
function output_pull_errors() {
	global $connection_now;

	if ( empty( $_GET['connection_id'] ) ) {
		return;
	}

	if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
		$error_key = "external_{$connection_now->id}";
	} else {
		$error_key = "internal_{$connection_now->site->blog_id}";
	}

	$pull_errors = get_transient( 'dt_connection_pull_errors_' . $error_key );

	if ( empty( $pull_errors ) ) {
		return;
	}

	delete_transient( 'dt_connection_pull_errors_' . $error_key );

	$post_ids = array_keys( $pull_errors );

	$_posts = $connection_now->remote_get( [ 'post__in' => $post_ids ] );
	$posts  = [];

	if ( empty( $_posts ) ) {
		return;
	}

	foreach ( $_posts['items'] as $post ) {
		$posts[ $post->ID ] = $post->post_title;
	}
	?>

	<div id="pull-errors" class="notice notice-warning is-dismissible">
		<p><?php esc_html_e( 'Some errors occurred while pulling selected posts. Please review the list of the errors bellow for more details.', 'distributor' ); ?></p>
		<ul>
			<?php foreach ( $pull_errors as $id => $errors ) : ?>
			<li>
				<strong><?php echo esc_html( $posts[ $id ] ); ?>:</strong>
				<ul>
				<?php foreach ( $errors as $error ) : ?>
					<li><?php echo esc_html( $error ); ?></li>
				<?php endforeach; ?>
				</ul>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>

	<?php
}
