<?php

namespace Distributor\PushUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
			add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
			add_action( 'wp_ajax_dt_push', __NAMESPACE__ . '\ajax_push' );
			add_action( 'admin_bar_menu', __NAMESPACE__ . '\menu_button', 999 );
			add_action( 'wp_footer', __NAMESPACE__ . '\menu_content', 10, 1 );
			add_action( 'admin_footer', __NAMESPACE__ . '\menu_content', 10, 1 );
		}
	);
}


/**
 * Check if post is syndicatable
 *
 * @since   0.8
 * @return  bool
 */
function syndicatable() {
	if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
		return false;
	}

	// Only published posts can be distributed.
	if ( 'publish' !== get_post_status() && ! apply_filters( 'dt_drafts_can_be_distributed', false ) ) {
		return false;
	}

	if ( is_admin() ) {
		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return false;
		}

		if ( ! in_array( get_post_type(), \Distributor\Utils\distributable_post_types(), true ) || ( ! empty( $_GET['post_type'] ) && 'dt_ext_connection' === $_GET['post_type'] ) ) {
			return false;
		}
	} else {
		if ( ! is_single() ) {
			return false;
		}

		if ( ! in_array( get_post_type(), \Distributor\Utils\distributable_post_types(), true ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Handle ajax pushing
 *
 * @since  0.8
 */
function ajax_push() {
	if ( ! check_ajax_referer( 'dt-push', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( empty( $_POST['post_id'] ) ) {
		wp_send_json_error();
		exit;
	}

	if ( empty( $_POST['connections'] ) ) {
		wp_send_json_success();
		exit;
	}

	$connection_map = get_post_meta( intval( $_POST['post_id'] ), 'dt_connection_map', true );
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
	$internal_push_results = array();

	foreach ( $_POST['connections'] as $connection ) {
		if ( 'external' === $connection['type'] ) {
			$external_connection_type = get_post_meta( $connection['id'], 'dt_external_connection_type', true );
			$external_connection_url  = get_post_meta( $connection['id'], 'dt_external_connection_url', true );
			$external_connection_auth = get_post_meta( $connection['id'], 'dt_external_connection_auth', true );

			if ( empty( $external_connection_auth ) ) {
				$external_connection_auth = array();
			}

			if ( ! empty( $external_connection_type ) && ! empty( $external_connection_url ) ) {
				$external_connection_class = \Distributor\Connections::factory()->get_registered()[ $external_connection_type ];

				$auth_handler = new $external_connection_class::$auth_handler_class( $external_connection_auth );

				$external_connection = new $external_connection_class( get_the_title( $connection['id'] ), $external_connection_url, $connection['id'], $auth_handler );

				$push_args = array();

				if ( ! empty( $connection_map['external'][ (int) $connection['id'] ] ) && ! empty( $connection_map['external'][ (int) $connection['id'] ]['post_id'] ) ) {
					$push_args['remote_post_id'] = (int) $connection_map['external'][ (int) $connection['id'] ]['post_id'];
				}

				if ( ! empty( $_POST['draft'] ) ) {
					$push_args['post_status'] = 'draft';
				}

				$remote_id = $external_connection->push( intval( $_POST['post_id'] ), $push_args );

				/**
				 * Record the external connection id's remote post id for this local post
				 */

				if ( ! is_wp_error( $remote_id ) ) {
					$connection_map['external'][ (int) $connection['id'] ] = array(
						'post_id' => (int) $remote_id,
						'time'    => time(),
					);

					$external_push_results[ (int) $connection['id'] ] = array(
						'post_id' => (int) $remote_id,
						'date'    => date( 'F j, Y g:i a' ),
						'status'  => 'success',
					);
				} else {
					$external_push_results[ (int) $connection['id'] ] = array(
						'post_id' => (int) $remote_id,
						'date'    => date( 'F j, Y g:i a' ),
						'status'  => 'fail',
					);
				}
			}
		} else {
			$internal_connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $connection['id'] ) );
			$push_args           = array();

			if ( ! empty( $connection_map['internal'][ (int) $connection['id'] ] ) && ! empty( $connection_map['internal'][ (int) $connection['id'] ]['post_id'] ) ) {
				$push_args['remote_post_id'] = (int) $connection_map['internal'][ (int) $connection['id'] ]['post_id'];
			}

			if ( ! empty( $_POST['draft'] ) ) {
				$push_args['post_status'] = 'draft';
			}

			$remote_id = $internal_connection->push( intval( $_POST['post_id'] ), $push_args );

			/**
			 * Record the internal connection id's remote post id for this local post
			 */
			if ( ! is_wp_error( $remote_id ) ) {
				switch_to_blog( intval( $connection['id'] ) );
				$remote_url = get_permalink( $remote_id );
				restore_current_blog();

				$connection_map['internal'][ (int) $connection['id'] ] = array(
					'post_id' => (int) $remote_id,
					'time'    => time(),
				);

				$internal_push_results[ (int) $connection['id']  ] = array(
					'post_id' => (int) $remote_id,
					'url'     => esc_url_raw( $remote_url ),
					'date'    => date( 'F j, Y g:i a' ),
					'status'  => 'success',
				);
			} else {
				$internal_push_results[ (int) $connection['id'] ] = array(
					'post_id' => (int) $remote_id,
					'date'    => date( 'F j, Y g:i a' ),
					'status'  => 'fail',
				);
			}
		}
	}

	update_post_meta( intval( $_POST['post_id'] ), 'dt_connection_map', $connection_map );

	wp_send_json_success(
		array(
			'results' => array(
				'internal' => $internal_push_results,
				'external' => $external_push_results,
			),
		)
	);

	exit;
}

/**
 * Enqueue scripts/styles for push
 *
 * @param  string $hook
 * @since  0.8
 */
function enqueue_scripts( $hook ) {
	if ( ! syndicatable() ) {
		return;
	}

	wp_enqueue_style( 'dt-push', plugins_url( '/dist/css/push.min.css', __DIR__ ), array(), DT_VERSION );
	wp_enqueue_script( 'dt-push', plugins_url( '/dist/js/push.min.js', __DIR__ ), array( 'jquery', 'underscore', 'hoverIntent' ), DT_VERSION, true );
	wp_localize_script(
		'dt-push', 'dt', array(
			'nonce'   => wp_create_nonce( 'dt-push' ),
			'post_id' => (int) get_the_ID(),
			'ajaxurl' => esc_url( admin_url( 'admin-ajax.php' ) ),

			/**
			 * Filter whether front end ajax requests should use xhrFields credentials:true.
			 *
			 * Front end ajax requests may require xhrFields with credentials when the front end and
			 * back end domains do not match. This filter lets themes opt in.
			 * See https://vip.wordpress.com/documentation/handling-frontend-file-uploads/#handling-ajax-requests
			 */
			'usexhr'  => apply_filters( 'dt_ajax_requires_with_credentials', false ),
		)
	);
}

/**
 * Let's setup our distributor menu in the toolbar
 *
 * @param object $wp_admin_bar
 * @since  0.8
 */
function menu_button( $wp_admin_bar ) {
	if ( ! syndicatable() ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'distributor',
			'title' => esc_html__( 'Distributor', 'distributor' ),
			'href'  => '#',
		)
	);
}

/**
 * Build distributor push menu dropdown HTML
 *
 * @since 0.8
 */
function menu_content() {
	global $post;

	if ( ! syndicatable() ) {
		return;
	}

	$unlinked         = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );
	$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

	if ( ! empty( $original_blog_id ) && ! empty( $original_post_id ) && ! $unlinked ) {
		switch_to_blog( $original_blog_id );
		$post_url  = get_permalink( $original_post_id );
		$site_url  = home_url();
		$blog_name = get_bloginfo( 'name' );
		restore_current_blog();

		$post_type_object = get_post_type_object( $post->post_type );

		?>
		<div id="distributor-push-wrapper">
			<div class="inner">
				<p class="syndicated-notice">
					<?php printf( esc_html__( 'This %s has been distributed from', 'distributor' ), esc_html( strtolower( $post_type_object->labels->singular_name ) ) ); ?>
					<a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $blog_name ); ?></a>.

					<?php esc_html_e( 'You can ', 'distributor' ); ?>
					<a href="<?php echo esc_url( $post_url ); ?>"><?php esc_html_e( 'view the original', 'distributor' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	} else {
		$connection_map = (array) get_post_meta( $post->ID, 'dt_connection_map', true );

		$dom_connections = [];

		if ( empty( $connection_map['external'] ) ) {
			$connection_map['external'] = [];
		}

		if ( empty( $connection_map['internal'] ) ) {
			$connection_map['internal'] = [];
		}

		if ( ! empty( \Distributor\Connections::factory()->get_registered()['networkblog'] ) ) {
			$sites = \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites();

			foreach ( $sites as $site_array ) {
				if ( in_array( $post->post_type, $site_array['post_types'], true ) ) {
					$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site_array['site'] );

					$syndicated = false;
					if ( ! empty( $connection_map['internal'][ (int) $connection->site->blog_id ] ) ) {
						switch_to_blog( $connection->site->blog_id );
						$syndicated = get_permalink( $connection_map['internal'][ (int) $connection->site->blog_id ]['post_id'] );
						restore_current_blog();

						if ( empty( $syndicated ) ) {
							$syndicated = true; // In case it was deleted
						}
					}

					$dom_connections[ 'internal' . $connection->site->blog_id ] = [
						'type'       => 'internal',
						'id'         => $connection->site->blog_id,
						'url'        => untrailingslashit( preg_replace( '#(https?:\/\/|www\.)#i', '', get_site_url( $connection->site->blog_id ) ) ),
						'name'       => $connection->site->blogname,
						'syndicated' => $syndicated,
					];
				}
			}
		}

		$external_connections_query = new \WP_Query(
			array(
				'post_type'      => 'dt_ext_connection',
				'posts_per_page' => 200,
				'no_found_rows'  => true,
				'post_status'    => 'publish',
			)
		);

		$current_post_type = get_post_type();

		if ( ! empty( $_GET['post_type'] ) ) {
			$current_post_type = sanitize_key( $_GET['post_type'] );
		}

		if ( empty( $current_post_type ) ) {
			// Serious problem
			return;
		}

		foreach ( $external_connections_query->posts as $external_connection ) {
			$external_connection_type = get_post_meta( $external_connection->ID, 'dt_external_connection_type', true );

			if ( empty( \Distributor\Connections::factory()->get_registered()[ $external_connection_type ] ) ) {
				continue;
			}

			$external_connection_status = get_post_meta( $external_connection->ID, 'dt_external_connections', true );
			$allowed_roles              = get_post_meta( $external_connection->ID, 'dt_external_connection_allowed_roles', true );
			if ( empty( $allowed_roles ) ) {
				$allowed_roles = array( 'administrator', 'editor' );
			}

			if ( empty( $external_connection_status ) ) {
				continue;
			}

			if ( ! empty( $external_connection_status['errors'] ) && ! empty( $external_connection_status['errors']['no_distributor'] ) ) {
				continue;
			}

			if ( ! in_array( $current_post_type, $external_connection_status['can_post'], true ) ) {
				continue;
			}

			// If not admin lets make sure the current user can push to this connection
			if ( ! current_user_can( apply_filters( 'dt_push_capabilities', 'manage_options' ) ) ) {
				$current_user_roles = (array) wp_get_current_user()->roles;

				if ( count( array_intersect( $current_user_roles, $allowed_roles ) ) < 1 ) {
					continue;
				}
			}

			$connection = \Distributor\ExternalConnection::instantiate( $external_connection->ID );

			if ( ! is_wp_error( $connection ) ) {
				$dom_connections[ 'external' . $connection->id ] = [
					'type'       => 'external',
					'id'         => $connection->id,
					'url'        => $connection->base_url,
					'name'       => $connection->name,
					'syndicated' => ( ! empty( $connection_map['external'][ (int) $external_connection->ID ] ) ) ? true : false,
				];
			}
		}
		?>
		<script type="text/javascript">
		var dtConnections = <?php echo wp_json_encode( $dom_connections ); ?>;
		</script>

		<script id="dt-add-connection" type="text/html">
			<div class="<# if (selectedConnections[connection.type + connection.id]) { #>added<# }#> add-connection <# if (connection.syndicated) { #>syndicated<# } #>" data-connection-type="{{ connection.type }}" data-connection-id="{{ connection.id }}">
				<# if ('internal' === connection.type) { #>
					<span>{{ connection.url }}</span>
				<# } else { #>
					<span>{{{ connection.name }}}</span>
				<# } #>

				<# if ('internal' === connection.type && connection.syndicated) { #>
					<a href="{{ connection.syndicated }}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
				<# } #>
			</div>
		</script>

		<div id="distributor-push-wrapper">
			<div class="inner">

				<?php if ( ! empty( $dom_connections ) ) : ?>
					<p><?php echo sprintf( esc_html__( 'Distribute &quot;%s&quot; to other connections.', 'distributor' ), get_the_title( $post->ID ) ); ?></p>

					<div class="connections-selector">
						<div>
							<?php if ( 5 < count( $dom_connections ) ) : ?>
								<input type="text" id="dt-connection-search" placeholder="<?php esc_html_e( 'Search available connections', 'distributor' ); ?>">
							<?php endif; ?>

							<div class="new-connections-list">
								<?php foreach ( $dom_connections as $connection ) : ?>
									<?php if ( 'external' === $connection['type'] ) : ?>
										<div class="add-connection
										<?php
										if ( ! empty( $connection['syndicated'] ) ) :
?>
syndicated<?php endif; ?>" data-connection-type="external" data-connection-id="<?php echo (int) $connection['id']; ?>">
											<span><?php echo wp_kses_post( get_the_title( $connection['id'] ) ); ?></span>
										</div>
									<?php else : ?>
										<div class="add-connection
										<?php
										if ( ! empty( $connection['syndicated'] ) ) :
?>
syndicated<?php endif; ?>" data-connection-type="internal" data-connection-id="<?php echo (int) $connection['id']; ?>">
											<span><?php echo esc_html( $connection['url'] ); ?></span>
											<?php if ( ! empty( $connection['syndicated'] ) ) : ?>
												<a href="<?php echo esc_url( $connection['syndicated'] ); ?>"><?php esc_html_e( 'View', 'distributor' ); ?></a>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
					<div class="connections-selected empty">
						<header class="with-selected"><?php esc_html_e( 'Selected connections', 'distributor' ); ?></header>
						<header class="no-selected"><?php esc_html_e( 'No connections selected', 'distributor' ); ?></header>

						<div class="selected-connections-list"></div>

						<div class="action-wrapper">
							<?php
							/**
							 * Only show the "As Draft" checkbox If draft distribution is enabled and the post
							 * status is not draft, posts can only be pushed as drafts. Draft posts always
							 * distribute as drafts.
							 */
							if ( ! apply_filters( 'dt_drafts_can_be_distributed', false ) || 'draft' !== get_post_status() ) :
							?>
								<button class="syndicate-button"><?php esc_html_e( 'Distribute', 'distributor' ); ?></button> <label class="as-draft" for="dt-as-draft"><input type="checkbox" id="dt-as-draft" checked> <?php esc_html_e( 'As draft', 'distributor' ); ?></label>
							<?php endif; ?>
						</div>
					</div>

					<div class="messages">
						<div class="dt-success">
							<?php esc_html_e( 'Post successfully distributed.', 'distributor' ); ?>
						</div>
						<div class="dt-error">
							<?php esc_html_e( 'There was an issue distributing the post.', 'distributor' ); ?>
						</div>
					</div>

				<?php else : ?>
					<p class="no-connections-notice">
						<?php esc_html_e( 'No connections available for distribution.', 'distributor' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

