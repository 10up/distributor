<?php
/**
 * Push UI functionality
 *
 * @package  distributor
 */

namespace Distributor\PushUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
			add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
			add_filter( 'amp_dev_mode_element_xpaths', __NAMESPACE__ . '\add_element_xpaths' );
			add_filter( 'script_loader_tag', __NAMESPACE__ . '\add_dev_mode_to_assets', 10, 2 );
			add_action( 'wp_ajax_dt_load_connections', __NAMESPACE__ . '\get_connections' );
			add_action( 'wp_ajax_dt_push', __NAMESPACE__ . '\ajax_push' );
			add_action( 'admin_bar_menu', __NAMESPACE__ . '\menu_button', 999 );
			add_action( 'wp_footer', __NAMESPACE__ . '\menu_content', 10, 1 );
			add_action( 'admin_footer', __NAMESPACE__ . '\menu_content', 10, 1 );
		}
	);
}

/**
 * Check if we're on a syndicatable admin post edit view or single post template.
 *
 * @since   0.8
 * @return  bool
 */
function syndicatable() {
	/**
	 * Filter Distributor capabilities allowed to syndicate content.
	 *
	 * @hook dt_syndicatable_capabilities
	 *
	 * @param string edit_posts The capability allowed to syndicate content.
	 */
	if ( ! is_user_logged_in() || ! current_user_can( apply_filters( 'dt_syndicatable_capabilities', 'edit_posts' ) ) ) {
		return false;
	}

	if ( is_admin() ) {

		global $pagenow;

		if ( 'post.php' !== $pagenow ) {
			return false;
		}
	} else {
		if ( ! is_singular( \Distributor\Utils\distributable_post_types() ) ) {
			return false;
		}
	}

	global $post;

	if ( empty( $post ) ) {
		return;
	}

	if ( ! in_array( $post->post_status, \Distributor\Utils\distributable_post_statuses(), true ) ) {
		return false;
	}

	if ( ! in_array( get_post_type(), \Distributor\Utils\distributable_post_types(), true ) || ( ! empty( $_GET['post_type'] ) && 'dt_ext_connection' === $_GET['post_type'] ) ) { // @codingStandardsIgnoreLine Nonce not required
		return false;
	}

	return true;
}

/**
 * Get available connections for use in the Push UI.
 *
 * @return void
 */
function get_connections() {
	if ( ! check_ajax_referer( 'dt-load-connections', 'loadConnectionsNonce', false ) ) {
		wp_send_json_error();
	}

	if ( empty( $_POST['postId'] ) ) {
		wp_send_json_error();
	}

	$post            = get_post( intval( $_POST['postId'] ) );
	$connection_map  = (array) get_post_meta( $post->ID, 'dt_connection_map', true );
	$dom_connections = [];

	if ( empty( $connection_map['external'] ) ) {
		$connection_map['external'] = [];
	}

	if ( empty( $connection_map['internal'] ) ) {
		$connection_map['internal'] = [];
	}

	if ( ! empty( \Distributor\Connections::factory()->get_registered()['networkblog'] ) ) {
		$sites = \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites( 'push' );

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
					'name'       => html_entity_decode( $connection->site->blogname, ENT_QUOTES, get_bloginfo( 'charset' ) ),
					'syndicated' => $syndicated,
				];
			}
		}
	}

	$external_connections_query = new \WP_Query(
		array(
			'post_type'      => 'dt_ext_connection',
			'posts_per_page' => 200, // @codingStandardsIgnoreLine This high pagination limit is purposeful
			'no_found_rows'  => true,
			'post_status'    => 'publish',
		)
	);

	$current_post_type = get_post_type( $post );

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
		/**
		 * Filter Distributor capabilities allowed to push content.
		 *
		 * @since 1.0.0
		 * @hook dt_push_capabilities
		 *
		 * @param {string} 'manage_options' The capability allowed to push content.
		 *
		 * @return {string} The capability allowed to push content.
		 */
		if ( ! current_user_can( apply_filters( 'dt_push_capabilities', 'manage_options' ) ) ) {
			$current_user_roles = (array) wp_get_current_user()->roles;

			if ( count( array_intersect( $current_user_roles, $allowed_roles ) ) < 1 ) {
				continue;
			}
		}

		$connection = \Distributor\ExternalConnection::instantiate( $external_connection->ID );

		if ( ! is_wp_error( $connection ) ) {
			$syndicated = false;

			if ( ! empty( $connection_map['external'][ (int) $external_connection->ID ] ) ) {
				$post_syndicated = $connection_map['external'][ (int) $external_connection->ID ];
				if ( ! empty( $post_syndicated['post_id'] ) ) {
					$syndicated = sprintf(
						'%1$s/?p=%2$d',
						get_site_url_from_rest_url( $connection->base_url ),
						$post_syndicated['post_id']
					);
				}
			}

			$dom_connections[ 'external' . $connection->id ] = [
				'type'       => 'external',
				'id'         => $connection->id,
				'url'        => $connection->base_url,
				'name'       => html_entity_decode( $connection->name, ENT_QUOTES, get_bloginfo( 'charset' ) ),
				'syndicated' => $syndicated,
			];
		}
	}

	wp_send_json_success( $dom_connections );
}

/**
 * Handle ajax pushing
 *
 * @since  0.8
 */
function ajax_push() {
	if ( ! check_ajax_referer( 'dt-push', 'nonce', false ) ) {
		wp_send_json_error( new \WP_Error( 'invalid-referal', __( 'Invalid Ajax referer.', 'distributor' ) ) );
		exit;
	}

	if ( empty( $_POST['postId'] ) ) {
		wp_send_json_error( new \WP_Error( 'no-post-id', __( 'No post ID provided.', 'distributor' ) ) );
		exit;
	}

	if ( empty( $_POST['connections'] ) ) {
		wp_send_json_error( new \WP_Error( 'no-connection', __( 'No connection provided.', 'distributor' ) ) );
		exit;
	}

	$connection_map = get_post_meta( intval( $_POST['postId'] ), 'dt_connection_map', true );
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

				if ( ! empty( $_POST['postStatus'] ) ) {
					$push_args['post_status'] = $_POST['postStatus'];
				}

				$remote_post = $external_connection->push( intval( $_POST['postId'] ), $push_args );

				/**
				 * Record the external connection id's remote post id for this local post
				 */

				if ( ! is_wp_error( $remote_post ) ) {
					$connection_map['external'][ (int) $connection['id'] ] = array(
						'post_id' => (int) $remote_post['id'],
						'time'    => time(),
					);

					$external_push_results[ (int) $connection['id'] ] = array(
						'post_id' => (int) $remote_post['id'],
						'date'    => gmdate( 'F j, Y g:i a' ),
						'status'  => 'success',
						'url'     => sprintf(
							'%1$s/?p=%2$d',
							get_site_url_from_rest_url( $external_connection_url ),
							(int) $remote_post['id']
						),
						'errors'  => empty( $remote_post['push-errors'] ) ? array() : $remote_post['push-errors'],
					);

					$external_connection->log_sync( array( (int) $remote_post['id'] => $_POST['postId'] ) );
				} else {
					$external_push_results[ (int) $connection['id'] ] = array(
						'date'   => gmdate( 'F j, Y g:i a' ),
						'status' => 'fail',
						'errors' => array( $remote_post->get_error_message() ),
					);
				}
			}
		} else {
			$internal_connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $connection['id'] ) );
			$push_args           = array();

			if ( ! empty( $connection_map['internal'][ (int) $connection['id'] ] ) && ! empty( $connection_map['internal'][ (int) $connection['id'] ]['post_id'] ) ) {
				$push_args['remote_post_id'] = (int) $connection_map['internal'][ (int) $connection['id'] ]['post_id'];
			}

			if ( ! empty( $_POST['postStatus'] ) ) {
				$push_args['post_status'] = esc_attr( $_POST['postStatus'] );
			}

			$remote_post = $internal_connection->push( intval( $_POST['postId'] ), $push_args );

			/**
			 * Record the internal connection id's remote post id for this local post
			 */
			if ( ! is_wp_error( $remote_post ) ) {
				$origin_site = get_current_blog_id();
				switch_to_blog( intval( $connection['id'] ) );
				$remote_url = get_permalink( $remote_post['id'] );
				$internal_connection->log_sync( array( $_POST['postId'] => $remote_post['id'] ), $origin_site );
				restore_current_blog();

				$connection_map['internal'][ (int) $connection['id'] ] = array(
					'post_id' => (int) $remote_post['id'],
					'time'    => time(),
				);

				$internal_push_results[ (int) $connection['id'] ] = array(
					'post_id' => (int) $remote_post['id'],
					'url'     => esc_url_raw( $remote_url ),
					'date'    => gmdate( 'F j, Y g:i a' ),
					'status'  => 'success',
					'errors'  => empty( $remote_post['push-errors'] ) ? array() : $remote_post['push-errors'],
				);
			} else {
				$internal_push_results[ (int) $connection['id'] ] = array(
					'errors' => array( $remote_post->get_error_message() ),
					'date'   => gmdate( 'F j, Y g:i a' ),
					'status' => 'fail',
				);
			}
		}
	}

	update_post_meta( intval( $_POST['postId'] ), 'dt_connection_map', $connection_map );

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
 * @param  string $hook WP hook.
 * @since  0.8
 */
function enqueue_scripts( $hook ) {
	if ( ! syndicatable() ) {
		return;
	}

	wp_enqueue_style( 'dt-push', plugins_url( '/dist/css/push.min.css', __DIR__ ), array(), DT_VERSION );
	wp_enqueue_script( 'dt-push', plugins_url( '/dist/js/push.min.js', __DIR__ ), array( 'jquery', 'underscore' ), DT_VERSION, true );
	wp_localize_script(
		'dt-push',
		'dt',
		array(
			'nonce'                => wp_create_nonce( 'dt-push' ),
			'loadConnectionsNonce' => wp_create_nonce( 'dt-load-connections' ),
			'postId'               => (int) get_the_ID(),
			'ajaxurl'              => esc_url( admin_url( 'admin-ajax.php' ) ),
			'messages'             => array(
				'ajax_error'   => __( 'Ajax error:', 'distributor' ),
				'empty_result' => __( 'Received empty result.', 'distributor' ),
			),

			/**
			 * Filter whether front end ajax requests should use xhrFields credentials:true.
			 *
			 * Front end ajax requests may require xhrFields with credentials when the front end and
			 * back end domains do not match. This filter lets themes opt in.
			 * See {@link https://vip.wordpress.com/documentation/handling-frontend-file-uploads/#handling-ajax-requests}
			 *
			 * @since 1.0.0
			 * @hook dt_ajax_requires_with_credentials
			 *
			 * @param {bool} false Whether front end ajax requests should use xhrFields credentials:true.
			 *
			 * @return {bool} Whether front end ajax requests should use xhrFields credentials:true.
			 */
			'usexhr'               => apply_filters( 'dt_ajax_requires_with_credentials', false ),
		)
	);
}

/**
 * Add the elements we want amp dev mode added to
 *
 * @param array $xpaths Current array of element paths
 * @return array
 */
function add_element_xpaths( $xpaths = [] ) {
	if ( ! syndicatable() ) {
		return $xpaths;
	}

	$ids = [
		'dt-push-css',
		'dt-push-js',
		'dt-push-js-extra',
	];

	foreach ( $ids as $id ) {
		$xpaths[] = sprintf( '//*[ @id = "%s" ]', $id );
	}

	return $xpaths;
}

/**
 * Add the amp dev mode to assets we need for distribution to work
 *
 * @param string $tag The `<script>` tag for the enqueued script.
 * @param string $handle The script's registered handle.
 * @return string
 */
function add_dev_mode_to_assets( $tag, $handle ) {
	if ( is_admin() || ! syndicatable() || ! function_exists( 'amp_is_request' ) || ! amp_is_request() ) {
		return $tag;
	}

	$script_handles = [
		'jquery',
		'jquery-core',
		'underscore',
	];

	if ( in_array( $handle, $script_handles, true ) ) {
		$tag = preg_replace(
			'/(?<=<script)(?=\s|>)/i',
			' data-ampdevmode',
			$tag
		);
	}

	return $tag;
}

/**
 * Let's setup our distributor menu in the toolbar
 *
 * @param object $wp_admin_bar Admin bar object.
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

	$wp_admin_bar->add_node(
		array(
			'parent' => 'distributor',
			'id'     => 'distributor-placeholder',
			'title'  => esc_html__( 'Distributor', 'distributor' ),
			'href'   => '#',
		)
	);
}

/**
 * Get Site URL from REST URL.
 * We can not assume the REST API prefix is wp-json because it can be changed to
 * a custom prefix.
 *
 * @param string $rest_url REST URL. Eg: domain.com/wp-json
 *
 * @return string Site URL.
 */
function get_site_url_from_rest_url( $rest_url ) {
	$_url = explode( '/', untrailingslashit( $rest_url ) );

	if ( count( $_url ) < 2 ) {
		return $rest_url;
	}

	array_pop( $_url );
	$url = implode( '/', $_url );

	if ( false === strpos( $url, 'http' ) ) {
		$url = '//' . $url;
	}

	return $url;
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

	if ( ! empty( $original_blog_id ) && ! empty( $original_post_id ) && ! $unlinked && is_multisite() ) {
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
					<?php /* translators: %s: post type name */ ?>
					<?php printf( esc_html__( 'This %s has been distributed from', 'distributor' ), esc_html( strtolower( $post_type_object->labels->singular_name ) ) ); ?>
					<a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $blog_name ); ?></a>.

					<?php esc_html_e( 'You can ', 'distributor' ); ?>
					<a href="<?php echo esc_url( $post_url ); ?>"><?php esc_html_e( 'view the original', 'distributor' ); ?></a>
				</p>
			</div>
		</div>
		<?php
	} else {
		if ( function_exists( 'amp_is_request' ) && amp_is_request() && ! is_admin() ) {
			include DT_PLUGIN_PATH . 'templates/show-connections-amp.php';
			include DT_PLUGIN_PATH . 'templates/add-connection-amp.php';
		} else {
			include DT_PLUGIN_PATH . 'templates/show-connections.php';
			include DT_PLUGIN_PATH . 'templates/add-connection.php';
		}
		?>

		<div id="distributor-push-wrapper">
			<div class="inner">
				<div class="loader-item">
					<div class="loader-col-8">
						<div class="loader-row border">
							<div class="loader-col-12 big odd"></div>
							<div class="loader-col-12 big"></div>
						</div>
					</div>
					<div class="loader-col-4">
						<div class="loader-row">
							<div class="loader-col-12 odd bottom"></div>
							<div class="loader-col-12 big odd"></div>
						</div>
					</div>
				</div>
				<div class="loader-messages messages">
					<div class="dt-error">
						<?php esc_html_e( 'There was an issue loading connections.', 'distributor' ); ?>
					</div>
				</div>
			</div>
		</div>

		<?php
	}
}
