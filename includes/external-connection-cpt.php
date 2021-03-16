<?php
/**
 * External connections functionality
 *
 * @package  distributor
 */

namespace Distributor\ExternalConnectionCPT;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'init', __NAMESPACE__ . '\setup_cpt' );
			add_filter( 'enter_title_here', __NAMESPACE__ . '\filter_enter_title_here', 10, 2 );
			add_filter( 'post_updated_messages', __NAMESPACE__ . '\filter_post_updated_messages' );
			add_action( 'save_post', __NAMESPACE__ . '\save_post' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
			add_action( 'wp_ajax_dt_verify_external_connection', __NAMESPACE__ . '\ajax_verify_external_connection' );
			add_action( 'wp_ajax_dt_get_remote_info', __NAMESPACE__ . '\get_remote_distributor_info' );
			add_filter( 'manage_dt_ext_connection_posts_columns', __NAMESPACE__ . '\filter_columns' );
			add_action( 'manage_dt_ext_connection_posts_custom_column', __NAMESPACE__ . '\action_custom_columns', 10, 2 );
			add_action( 'admin_menu', __NAMESPACE__ . '\add_menu_item' );
			add_action( 'admin_menu', __NAMESPACE__ . '\add_submenu_item', 11 );
			add_action( 'load-toplevel_page_distributor', __NAMESPACE__ . '\setup_list_table' );
			add_filter( 'set-screen-option', __NAMESPACE__ . '\set_screen_option', 10, 3 );
			add_action( 'wp_ajax_dt_begin_authorization', __NAMESPACE__ . '\ajax_begin_authorization' );
			add_action( 'manage_dt_ext_connection_posts_custom_column', __NAMESPACE__ . '\output_status_column', 10, 2 );
			add_filter( 'manage_dt_ext_connection_posts_columns', __NAMESPACE__ . '\add_status_column' );
		}
	);
}


/**
 * Add status column to post table to indicate a connections status
 *
 * @since  1.0
 * @param  array $columns Admin columns.
 * @return array
 */
function add_status_column( $columns ) {
	unset( $columns['date'] );
	$columns['dt_status'] = esc_html__( 'Status', 'distributor' );/*'<span class="connection-status green"></span>'*/

	$columns['date'] = esc_html__( 'Date', 'distributor' );

	return $columns;
}

/**
 * Output status column
 *
 * @param  string $column_name Column name.
 * @param  int    $post_id Post ID.
 * @since  1.0
 */
function output_status_column( $column_name, $post_id ) {
	if ( 'dt_status' === $column_name ) {
		$external_connection_status = get_post_meta( $post_id, 'dt_external_connections', true );
		$last_checked               = get_post_meta( $post_id, 'dt_external_connection_check_time', true );

		$status = 'valid';

		if ( empty( $external_connection_status ) ) {
			$status = 'error';
		} else {
			if ( ! empty( $external_connection_status['errors'] ) && ! empty( $external_connection_status['errors']['no_distributor'] ) ) {
				$status = 'error';
			}

			if ( empty( $external_connection_status['can_post'] ) ) {
				$status = 'warning';
			}
		}

		?>
		<span
			class="connection-status <?php echo esc_attr( $status ); ?>"
			<?php if ( ! empty( $last_checked ) ) : ?>
				<?php /* translators: %s: human readable time difference */ ?>
				title="<?php printf( esc_html__( 'Last Checked on %s' ), esc_html( gmdate( 'F j, Y, g:i a', ( $last_checked + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) ) ); ?>"
			<?php endif; ?>
		></span>
		<a href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"><?php esc_html_e( '(Verify)', 'distributor' ); ?></a>
		<?php
	}
}

/**
 * Save the external connection, returning the post ID so the authorization process can continue.
 */
function ajax_begin_authorization() {
	if ( ! check_ajax_referer( 'dt-verify-ext-conn', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error();
		exit;
	}

	if ( empty( $_POST['title'] ) || empty( $_POST['id'] ) ) {
		wp_send_json_error();
		exit;
	}

	// Create the external connection, and return the post ID.
	$post = wp_update_post(
		array(
			'ID'          => sanitize_key( wp_unslash( $_POST['id'] ) ),
			'post_title'  => sanitize_text_field( wp_unslash( $_POST['title'] ) ),
			'post_type'   => 'dt_ext_connection',
			'post_status' => 'publish',
		)
	);

	if ( is_wp_error( $post ) || 0 === $post ) {
		wp_send_json_error();
		exit;
	}

	// Set the connection type for the newly created connection.
	update_post_meta( $post, 'dt_external_connection_type', 'wpdotcom' );

	// Send back the id of the created post with a 201 "Created" status.
	wp_send_json_success( array( 'id' => $post ), 201 );
}

/**
 * Set screen option for posts per page
 *
 * @param  string $status Option status.
 * @param  string $option Option.
 * @param  mixed  $value Option value.
 * @since  0.8
 * @return mixed
 */
function set_screen_option( $status, $option, $value ) {
	return 'connections_per_page' === $option ? $value : $status;
}

/**
 * Setup list table and process actions
 *
 * @since  0.8
 */
function setup_list_table() {
	global $connection_list_table;

	$connection_list_table = new \Distributor\ExternalConnectionListTable();

	$doaction = $connection_list_table->current_action();

	if ( ! empty( $doaction ) ) {
		check_admin_referer( 'bulk-posts' );

		if ( 'bulk-delete' === $doaction ) {
			$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'locked', 'ids' ), wp_get_referer() );

			$deleted  = 0;
			$post_ids = array_map( 'intval', $_REQUEST['post'] );

			foreach ( (array) $post_ids as $post_id ) {
				wp_delete_post( $post_id );

				$deleted++;
			}
			$sendback = add_query_arg( 'deleted', $deleted, $sendback );

			$sendback = remove_query_arg( array( 'action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status', 'post', 'bulk_edit', 'post_view' ), $sendback );

			wp_safe_redirect( $sendback );
			exit;
		}

		exit;
	}
}

/**
 * Add url column to posts table
 *
 * @param  array $columns Admin columns.
 * @since  0.8
 * @return array
 */
function filter_columns( $columns ) {
	$columns['dt_external_connection_url'] = esc_html__( 'URL', 'distributor' );

	unset( $columns['date'] );
	return $columns;
}

/**
 * Output url column
 *
 * @param  string $column Column name.
 * @param  int    $post_id Post ID.
 * @since  0.8
 */
function action_custom_columns( $column, $post_id ) {
	if ( 'dt_external_connection_url' === $column ) {
		$url = get_post_meta( $post_id, 'dt_external_connection_url', true );

		if ( ! empty( $url ) ) {
			echo esc_url( $url );
		} else {
			esc_html_e( 'None', 'distributor' );
		}
	}
}

/**
 * Check push and pull connections via AJAX
 *
 * @since  0.8
 */
function ajax_verify_external_connection() {
	if ( ! check_ajax_referer( 'dt-verify-ext-conn', 'nonce', false ) ) {
		wp_send_json_error();
		exit;
	}

	if ( empty( $_POST['url'] ) || empty( $_POST['type'] ) ) {
		wp_send_json_error();
		exit;
	}

	$auth = array();
	if ( ! empty( $_POST['auth'] ) ) {
		$auth = $_POST['auth'];
	}

	$current_auth = get_post_meta( intval( sanitize_key( $_POST['endpointId'] ) ), 'dt_external_connection_auth', true );

	if ( ! empty( $current_auth ) ) {
		$auth = array_merge( $auth, (array) $current_auth );
	}

	// Create an instance of the connection to test connections
	$external_connection_class = \Distributor\Connections::factory()->get_registered()[ sanitize_key( $_POST['type'] ) ];

	$auth_handler = new $external_connection_class::$auth_handler_class( $auth );

	// Init with placeholders since we haven't created yet
	$external_connection = new $external_connection_class( 'connection-test', $_POST['url'], 0, $auth_handler );

	$external_connections = $external_connection->check_connections();

	wp_send_json_success( $external_connections );
	exit;
}

/**
 * Enqueue admin scripts for external connection editor
 *
 * @param  string $hook WP hook.
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( ( 'post.php' === $hook && 'dt_ext_connection' === get_post_type() ) || ( 'post-new.php' === $hook && ! empty( $_GET['post_type'] ) && 'dt_ext_connection' === $_GET['post_type'] ) ) { // @codingStandardsIgnoreLine Nonce not required.

		wp_enqueue_style( 'dt-admin-external-connection', plugins_url( '/dist/css/admin-external-connection.min.css', __DIR__ ), array(), DT_VERSION );
		wp_enqueue_script( 'dt-admin-external-connection', plugins_url( '/dist/js/admin-external-connection.min.js', __DIR__ ), array( 'jquery', 'underscore', 'wp-a11y' ), DT_VERSION, true );

		$blog_name     = get_bloginfo( 'name ' );
		$wizard_return = get_wizard_return_data();

		wp_localize_script(
			'dt-admin-external-connection',
			'dt',
			array(
				'nonce'                               => wp_create_nonce( 'dt-verify-ext-conn' ),
				'bad_connection'                      => esc_html__( 'No connection found.', 'distributor' ),
				'good_connection'                     => esc_html__( 'Connection established.', 'distributor' ),
				'limited_connection'                  => esc_html__( 'Limited connection established.', 'distributor' ),
				'no_push'                             => esc_html__( 'Push distribution unavailable.', 'distributor' ),
				'no_permissions'                      => esc_html__( 'Authentication succeeded but your account does not have permissions to create posts on the external site.', 'distributor' ),
				'pull_limited'                        => esc_html__( 'Pull distribution limited to basic content, i.e. title and content body.', 'distributor' ),
				'endpoint_suggestion'                 => esc_html__( 'Did you mean: ', 'distributor' ),
				'endpoint_checking_message'           => esc_html__( 'Checking endpoint...', 'distributor' ),
				'bad_auth'                            => esc_html__( 'Authentication failed due to invalid credentials.', 'distributor' ),
				'change'                              => esc_html__( 'Change', 'distributor' ),
				'cancel'                              => esc_html__( 'Cancel', 'distributor' ),
				'invalid_url'                         => esc_html__( 'Please enter a valid URL, including the HTTP(S).', 'distributor' ),
				'norest'                              => esc_html__( 'No REST API endpoint was located for this site.', 'distributor' ),
				'noconnection'                        => esc_html__( 'Unable to connect to site.', 'distributor' ),
				'minversion'                          => esc_html__( 'Remote site requires Distributor version 1.6.0 or greater. Upgrade Distributor on the remote site to use the Authentication Wizard.', 'distributor' ),
				'no_distributor'                      => esc_html__( 'Distributor not installed on remote site.', 'distributor' ),
				'roles_warning'                       => esc_html__( 'Be careful assigning less trusted roles push privileges as they will inherit the capabilities of the user on the remote site.', 'distributor' ),
				'admin_url'                           => admin_url(),
				/* translators: %1$s: site name, %2$s: site URL */
				'distributor_from'                    => sprintf( esc_html__( 'Distributor on %1$s (%2$s)', 'distributor' ), $blog_name, esc_url( home_url() ) ),
				'wizard_return'                       => $wizard_return,
				'application_passwords_not_available' => __( 'Application Passwords is not available on the remote site. Please set up connection manually!', 'distributor' ),
			)
		);

		wp_dequeue_script( 'autosave' );
	}

	if ( ! empty( $_GET['page'] ) && 'distributor' === $_GET['page'] ) { // @codingStandardsIgnoreLine Nonce not required
		wp_enqueue_style( 'dt-admin-external-connections', plugins_url( '/dist/css/admin-external-connections.min.css', __DIR__ ), array(), DT_VERSION );
	}
}

/**
 * Get the data returned as part of the external applications passwords flow.
 */
function get_wizard_return_data() {
	$wizard_return = false;
	if ( isset( $_GET['setupStatus'] ) && 'success' === sanitize_key( $_GET['setupStatus'] ) ) { // @codingStandardsIgnoreLine Nonce isn't needed here.
		$wizard_return = array(
			'titleField'           => isset( $_GET['titleField'] ) ? sanitize_text_field( urldecode( $_GET['titleField'] ) ) : '', // @codingStandardsIgnoreLine Nonce isn't needed here.
			'externalSiteUrlField' => isset( $_GET['externalSiteUrlField'] ) ? sanitize_text_field( urldecode( $_GET['externalSiteUrlField'] ) ) : '', // @codingStandardsIgnoreLine Nonce isn't needed here.
			'restRoot' => isset( $_GET['restRoot'] ) ? sanitize_text_field( urldecode( $_GET['restRoot'] ) ) : '', // @codingStandardsIgnoreLine Nonce isn't needed here.
			'user_login'           => isset( $_GET['user_login'] ) ? sanitize_text_field( $_GET['user_login'] ) : '', // @codingStandardsIgnoreLine Nonce isn't needed here.
			'password'             => isset( $_GET['password'] ) ? sanitize_text_field( $_GET['password'] ) : '', // @codingStandardsIgnoreLine Nonce isn't needed here.
		);
	}
	return $wizard_return;
}

/**
 * Change title text box label
 *
 * @param  string $label Title text.
 * @param  int    $post Post object.
 * @since  0.8
 * @return string
 */
function filter_enter_title_here( $label, $post = 0 ) {
	if ( 'dt_ext_connection' !== get_post_type( $post->ID ) ) {
		return $label;
	}

	return esc_html__( 'Label this external connection', 'distributor' );
}

/**
 * Save external connection stuff
 *
 * @param int $post_id Post ID.
 * @since 0.8
 */
function save_post( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' === get_post_type( $post_id ) ) {
		return;
	}

	if ( empty( $_POST['dt_external_connection_details'] ) || ! wp_verify_nonce( $_POST['dt_external_connection_details'], 'dt_external_connection_details_action' ) ) {
		return;
	}

	if ( empty( $_POST['dt_external_connection_type'] ) ) {
		delete_post_meta( $post_id, 'dt_external_connection_type' );
	} else {
		update_post_meta( $post_id, 'dt_external_connection_type', sanitize_text_field( $_POST['dt_external_connection_type'] ) );
	}

	if ( empty( $_POST['dt_external_connection_allowed_roles'] ) ) {
		delete_post_meta( $post_id, 'dt_external_connection_allowed_roles' );
	} else {
		update_post_meta( $post_id, 'dt_external_connection_allowed_roles', array_map( 'sanitize_text_field', $_POST['dt_external_connection_allowed_roles'] ) );
	}

	if ( empty( $_POST['dt_external_connection_url'] ) ) {
		delete_post_meta( $post_id, 'dt_external_connection_url' );
		delete_post_meta( $post_id, 'dt_external_connections' );
		delete_post_meta( $post_id, 'dt_external_connection_check_time' );
	} else {
		update_post_meta( $post_id, 'dt_external_connection_url', sanitize_text_field( $_POST['dt_external_connection_url'] ) );

		// Create an instance of the connection to test connections
		$external_connection_class = \Distributor\Connections::factory()->get_registered()[ sanitize_key( $_POST['dt_external_connection_type'] ) ];

		$auth = array();
		if ( ! empty( $_POST['dt_external_connection_auth'] ) ) {
			$auth = $_POST['dt_external_connection_auth'];
		}

		$current_auth = get_post_meta( $post_id, 'dt_external_connection_auth', true );

		if ( ! empty( $current_auth ) ) {
			$auth = array_merge( $auth, (array) $current_auth );
		}

		$auth_handler = new $external_connection_class::$auth_handler_class( $auth );

		$external_connection = new $external_connection_class( get_the_title( $post_id ), $_POST['dt_external_connection_url'], $post_id, $auth_handler );

		$external_connections = $external_connection->check_connections();

		update_post_meta( $post_id, 'dt_external_connections', $external_connections );
		update_post_meta( $post_id, 'dt_external_connection_check_time', time() );
	}

	if ( ! empty( $_POST['dt_external_connection_auth'] ) ) {
		$current_auth = get_post_meta( $post_id, 'dt_external_connection_auth', true );
		if ( empty( $current_auth ) ) {
			$current_auth = array();
		}

		$connection_class         = \Distributor\Connections::factory()->get_registered()[ sanitize_key( $_POST['dt_external_connection_type'] ) ];
		$auth_handler_class_again = $connection_class::$auth_handler_class;

		$auth_creds = $auth_handler_class_again::prepare_credentials( array_merge( (array) $current_auth, $_POST['dt_external_connection_auth'] ) );

		$auth_handler_class_again::store_credentials( $post_id, $auth_creds );
	}
}

/**
 * Register meta boxes
 *
 * @since 0.8
 */
function add_meta_boxes() {
	add_meta_box( 'dt_external_connection_details', esc_html__( 'External Connection Details', 'distributor' ), __NAMESPACE__ . '\meta_box_external_connection_details', 'dt_ext_connection', 'normal', 'core' );
}

/**
 * Output connection options meta box
 *
 * @since 0.8
 * @param \WP_Post $post Post object.
 */
function meta_box_external_connection_details( $post ) {
	wp_nonce_field( 'dt_external_connection_details_action', 'dt_external_connection_details' );

	$external_connection_type = get_post_meta( $post->ID, 'dt_external_connection_type', true );

	$auth = get_post_meta( $post->ID, 'dt_external_connection_auth', true );
	if ( empty( $auth ) ) {
		$auth = array();
	}

	$external_connection_url = get_post_meta( $post->ID, 'dt_external_connection_url', true );
	if ( empty( $external_connection_url ) ) {
		$external_connection_url = '';
	}

	$external_connection_status = get_post_meta( $post->ID, 'dt_external_connections', true );

	$post_types = get_post_types(
		array(
			'show_in_rest' => true,
		),
		'objects'
	);

	$registered_external_connection_types = \Distributor\Connections::factory()->get_registered();

	foreach ( $registered_external_connection_types as $slug => $class ) {

		if (
			'Distributor\ExternalConnection' !== get_parent_class( $class ) &&
			'Distributor\ExternalConnections\WordPressExternalConnection' !== get_parent_class( $class )
		) {
			unset( $registered_external_connection_types[ $slug ] );
		}
	}

	$allowed_roles = get_post_meta( $post->ID, 'dt_external_connection_allowed_roles', true );

	if ( empty( $allowed_roles ) ) {
		$allowed_roles = array( 'administrator', 'editor' );
	} else {
		$allowed_roles[] = 'administrator';
	}
	?>
	<?php
		if ( isset( $_GET['setupStatus'] ) && 'failure' === sanitize_key( $_GET['setupStatus'] ) ) { // @codingStandardsIgnoreLine Nonce is checked above.
		?>
		<div class="updated is-dismissible error">
			<p>
		<?php esc_html_e( 'Authorization rejected, please try again.', 'distributor' ); ?>
			</p>
		</div>
		<?php
	}
	?>
	<?php
	if ( 1 === count( $registered_external_connection_types ) ) :
		$registered_connection_types_keys = array_keys( $registered_external_connection_types );
		?>
		<input id="dt_external_connection_type" class="external-connection-type-field" type="hidden" name="dt_external_connection_type" value="<?php echo esc_attr( $registered_connection_types_keys[0] ); ?>">
	<?php else : ?>
		<div class="choose-authentication">
			<label for="dt_external_connection_type"><?php esc_html_e( 'Authentication Method', 'distributor' ); ?></label><br>
			<select name="dt_external_connection_type" class="external-connection-type-field" id="dt_external_connection_type">
				<?php foreach ( $registered_external_connection_types as $slug => $external_connection_class ) : ?>
					<option <?php selected( $slug, $external_connection_type ); ?> value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_attr( $external_connection_class::$label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	<?php endif; ?>

	<?php
	$index = 1;
	foreach ( $registered_external_connection_types as $external_connection_class ) :
		$auth_handler_class_again = $external_connection_class::$auth_handler_class;
		if ( ! $auth_handler_class_again::$requires_credentials ) {
			continue; }
		$selected  = $external_connection_class::$slug === $external_connection_type ||
			( '' === $external_connection_type && 1 === $index );
		$is_hidden = ! $selected;
		$index++;
		?>
		<div class="auth-credentials <?php echo esc_attr( $auth_handler_class_again::$slug ); ?> <?php echo esc_attr( $external_connection_class::$slug ); ?>">
			<?php $auth_handler_class_again::credentials_form( $auth ); ?>
		</div>
	<?php endforeach; ?>
	<div class="connection-field-wrap hide-until-authed">
		<label for="dt_external_connection_url"><?php esc_html_e( 'External Connection URL', 'distributor' ); ?></label><br>
		<span class="external-connection-url-field-wrapper">
			<input value="<?php echo esc_url( $external_connection_url ); ?>" type="text" name="dt_external_connection_url" id="dt_external_connection_url" class="widefat external-connection-url-field">
		</span>

		<span class="description endpoint-result"></span>
		<ul class="endpoint-errors"></ul>
	</div>

	<?php if ( ! empty( $external_connection_status ) ) : ?>
		<div class="post-types-permissions hide-until-authed">
			<h4><?php esc_html_e( 'Post types permissions', 'distributor' ); ?></h4>

			<table class="wp-list-table widefat">
				<thead>
					<th><?php esc_html_e( 'Post types', 'distributor' ); ?></th>
					<th><?php esc_html_e( 'Can pull?', 'distributor' ); ?></th>
					<th><?php esc_html_e( 'Can push?', 'distributor' ); ?></th>
				</thead>
				<tbody>
					<?php foreach ( $post_types as $post_type ) : ?>
						<?php
						if ( 'dt_subscription' === $post_type->name ) {
							continue;
						}
						?>
						<tr>
							<td><?php echo esc_html( $post_type->label ); ?></td>
							<td><?php echo in_array( $post_type->name, $external_connection_status['can_get'] ) ? esc_html__( 'Yes', 'distributor' ) : esc_html__( 'No', 'distributor' ); ?></td>
							<td><?php echo in_array( $post_type->name, $external_connection_status['can_post'] ) ? esc_html__( 'Yes', 'distributor' ) : esc_html__( 'No', 'distributor' ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php endif; ?>

	<fieldset class="dt-roles-allowed hide-until-authed">
		<legend><?php esc_html_e( 'Roles Allowed to Push', 'distributor' ); ?></legend>

		<?php
		$editable_roles = get_editable_roles();
		foreach ( $editable_roles as $role => $details ) {
			$name = translate_user_role( $details['name'] );
			?>

			<input class="dt-role-checkbox" name="dt_external_connection_allowed_roles[]" id="dt-role-<?php echo esc_attr( $role ); ?>" type="checkbox" <?php checked( true, in_array( $role, $allowed_roles, true ) ); ?> value="<?php echo esc_attr( $role ); ?>"> <label for="dt-role-<?php echo esc_attr( $role ); ?>"><?php echo esc_html( $name ); ?></label><br>

			<?php
		}
		?>
		<span class="description"><?php esc_html_e( 'Select the roles of users on this site that will be allowed to push content to this connection. Keep in mind that pushing will use the permissions of the user credentials provided for this connection.', 'distributor' ); ?></span>
	</fieldset>

	<p class="dt-submit-connection hide-until-authed">
		<input type="hidden" name="post_status" value="publish">
		<input type="hidden" name="original_post_status" value="<?php echo esc_attr( $post->post_status ); ?>">

		<?php if ( 0 < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>

			<input name="save" type="submit" class="button button-primary button-large" id="create-connection" value="<?php esc_attr_e( 'Update Connection', 'distributor' ); ?>">

			<a class="delete-link" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?> "><?php esc_html_e( 'Move to Trash', 'distributor' ); ?></a>
		<?php else : ?>
			<input name="create-connection" type="submit" class="button button-primary button-large" id="create-connection" value="<?php esc_attr_e( 'Create Connection', 'distributor' ); ?>">
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Output pull dashboard with custom list table
 *
 * @since 0.8
 */
function dashboard() {
	global $connection_list_table;

	$_GET['post_type']     = 'dt_ext_connection';
	$_REQUEST['all_posts'] = true; // Default to replacite "All" tab

	$connection_list_table->prepare_items();
	?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'External Connections', 'distributor' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dt_ext_connection' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'distributor' ); ?></a>
		<hr class="wp-header-end">

		<h2 class="screen-reader-text"><?php esc_html_e( 'Filter connections list', 'distributor' ); ?></h2>
		<?php $connection_list_table->views(); ?>

		<form id="posts-filter" method="get">

		<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty( $_REQUEST['post_status'] ) ? esc_attr( sanitize_key( $_REQUEST['post_status'] ) ) : 'all'; // @codingStandardsIgnoreLine Nonce not required ?>">
		<input type="hidden" name="post_type" class="post_type_page" value="dt_ext_connection">
		<input type="hidden" name="page" value="distributor">

		<?php $connection_list_table->display(); ?>

		</form>
	</div>
	<?php
}

/**
 * Add a screen option for posts per page
 *
 * @since  0.8
 */
function screen_option() {

	$option = 'per_page';
	$args   = [
		'label'   => esc_html__( 'External connections per page: ', 'distributor' ),
		'default' => get_option( 'posts_per_page' ),
		'option'  => 'connections_per_page',
	];

	add_screen_option( $option, $args );
}

/**
 * Set up top menu item
 *
 * @since 0.8
 */
function add_menu_item() {
	$hook = add_menu_page(
		'Distributor',
		'Distributor',
		/**
		 * Filter Distributor capabilities allowed to view external connections.
		 *
		 * @since 1.0.0
		 * @hook dt_capabilities
		 *
		 * @param {string} 'manage_options' The capability allowed to view external connections.
		 *
		 * @return {string} The capability allowed to view external connections.
		 */
		apply_filters( 'dt_capabilities', 'manage_options' ),
		'distributor',
		__NAMESPACE__ . '\dashboard',
		'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYiIGhlaWdodD0iMzkiIHZpZXdCb3g9IjAgMCAzNiAzOSIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48dGl0bGU+ZGlzdHJpYnV0b3ItaWNvbjwvdGl0bGU+PGRlc2M+Q3JlYXRlZCB1c2luZyBGaWdtYTwvZGVzYz48ZyBpZD0iQ2FudmFzIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtNDAyNCAzNjUpIj48ZyBpZD0iZGlzdHJpYnV0b3ItaWNvbiI+PHVzZSB4bGluazpocmVmPSIjcGF0aDBfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjx1c2UgeGxpbms6aHJlZj0iI3BhdGgxX2ZpbGwiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQwMjQuNzEgLTM2NSkiIGZpbGw9IiNBMEE1QUEiLz48dXNlIHhsaW5rOmhyZWY9IiNwYXRoMl9maWxsIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0MDI0LjcxIC0zNjUpIiBmaWxsPSIjQTBBNUFBIi8+PHVzZSB4bGluazpocmVmPSIjcGF0aDNfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjx1c2UgeGxpbms6aHJlZj0iI3BhdGg0X2ZpbGwiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQwMjQuNzEgLTM2NSkiIGZpbGw9IiNBMEE1QUEiLz48dXNlIHhsaW5rOmhyZWY9IiNwYXRoNV9maWxsIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0MDI0LjcxIC0zNjUpIiBmaWxsPSIjQTBBNUFBIi8+PHVzZSB4bGluazpocmVmPSIjcGF0aDZfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjwvZz48L2c+PGRlZnM+PHBhdGggaWQ9InBhdGgwX2ZpbGwiIGQ9Ik0gNy45MzgxNCAyMy4xMTA4QyA3LjAwNzQzIDIzLjExMDggNi4yOTE1IDIzLjkwNDUgNi4yOTE1IDI0LjkzNjJDIDYuMjkxNSAyNS44ODg3IDcuMDA3NDMgMjYuNzYxNyA3Ljg2NjU0IDI2Ljc2MTdDIDguOTQwNDMgMjguNDI4NCAxMC4zNzIzIDI5Ljc3NzcgMTIuMDE4OSAzMC43MzAxQyAxMy41OTQgMzEuNjAzMiAxNS4zMTIyIDMyIDE2Ljk1ODggMzJDIDIxLjExMTIgMzIgMjUuMTIwNCAyOS40NjAyIDI3LjEyNSAyNS4wMTU2QyAyOC40ODUyIDIxLjk5OTYgMjguNjI4NCAxOC42NjYyIDI3LjY5NzcgMTUuNDkxNUMgMjYuNjk1NCAxMi4zMTY3IDI0LjY5MDggOS43NzY5NiAyMS45NzAzIDguMjY4OTZDIDE2LjM4NjEgNS4yNTI5OCA5LjY1NjM2IDcuNzkyNzYgNi44NjQyNSAxMy45ODM1TCA2LjY0OTQ3IDE0LjM4MDNMIDYuNzIxMDYgMTQuMzgwM0MgNi40MzQ2OSAxNS4wMTUyIDYuMjE5OTEgMTUuODg4MyA2Ljc5MjY1IDE2LjIwNThMIDEyLjczNDggMTguNzQ1NUMgMTIuNzM0OCAxOC45ODM2IDEyLjY2MzMgMTkuMjIxOCAxMi42NjMzIDE5LjQ1OTlDIDEyLjY2MzMgMjIuMDc5IDE0LjU5NjMgMjQuMjIxOSAxNi45NTg4IDI0LjIyMTlDIDE5LjMyMTQgMjQuMjIxOSAyMS4yNTQ0IDIyLjA3OSAyMS4yNTQ0IDE5LjQ1OTlDIDIxLjI1NDQgMTYuODQwNyAxOS4zMjE0IDE0LjY5NzggMTYuOTU4OCAxNC42OTc4QyAxNS41MjcgMTQuNjk3OCAxNC4yMzgzIDE1LjQ5MTUgMTMuNDUwOCAxNi42ODJMIDguNTgyNDcgMTQuNjE4NEMgOS43Mjc5NSAxMi4yMzc0IDExLjU4OTQgMTAuNDExOSAxMy44ODAzIDkuNTM4ODVDIDE2LjI0MjkgOC42NjU4IDE4LjgyMDIgOC44MjQ1NCAyMS4xMTEyIDEwLjAxNTFDIDI1Ljc2NDcgMTIuNTU0OCAyNy42OTc3IDE4LjgyNDkgMjUuNDA2OCAyMy45ODM4QyAyNC4zMzI5IDI2LjUyMzYgMjIuMzk5OSAyOC4zNDkxIDE5Ljk2NTcgMjkuMzAxNUMgMTcuNjAzMSAzMC4xNzQ1IDE1LjAyNTggMzAuMDE1OCAxMi43MzQ4IDI4LjgyNTNDIDExLjM3NDYgMjguMTExIDEwLjIyOTEgMjYuOTk5OCA5LjI5ODQgMjUuNjUwNkMgOS4zNjk5OSAyNS40MTI1IDkuNDQxNTggMjUuMTc0NCA5LjQ0MTU4IDI0LjkzNjJDIDkuNTg0NzcgMjMuOTgzOCA4Ljc5NzI1IDIzLjExMDggNy45MzgxNCAyMy4xMTA4WiIvPjxwYXRoIGlkPSJwYXRoMV9maWxsIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0gMTIuMDE4OSAzMC43MzAxQyAxMC4zNzIzIDI5Ljc3NzcgOC45NDA0MyAyOC40Mjg0IDcuODY2NTQgMjYuNzYxN0MgNy4wMDc0MyAyNi43NjE3IDYuMjkxNSAyNS44ODg3IDYuMjkxNSAyNC45MzYyQyA2LjI5MTUgMjMuOTA0NSA3LjAwNzQzIDIzLjExMDggNy45MzgxNCAyMy4xMTA4QyA4Ljc5NzI1IDIzLjExMDggOS41ODQ3NyAyMy45ODM4IDkuNDQxNTggMjQuOTM2MkMgOS40NDE1OCAyNS4xNzQ0IDkuMzY5OTkgMjUuNDEyNSA5LjI5ODQgMjUuNjUwNkMgMTAuMjI5MSAyNi45OTk4IDExLjM3NDYgMjguMTExIDEyLjczNDggMjguODI1M0MgMTUuMDI1OCAzMC4wMTU4IDE3LjYwMzEgMzAuMTc0NSAxOS45NjU3IDI5LjMwMTVDIDIyLjM5OTkgMjguMzQ5MSAyNC4zMzI5IDI2LjUyMzYgMjUuNDA2OCAyMy45ODM4QyAyNy42OTc3IDE4LjgyNDkgMjUuNzY0NyAxMi41NTQ4IDIxLjExMTIgMTAuMDE1MUMgMTguODIwMiA4LjgyNDU0IDE2LjI0MjkgOC42NjU4IDEzLjg4MDMgOS41Mzg4NUMgMTEuNjc2NSAxMC4zNzg3IDkuODcwMDcgMTIuMDk5OSA4LjcxNjE2IDE0LjM0OTNDIDguNjcwNTYgMTQuNDM4MiA4LjYyNjAyIDE0LjUyNzkgOC41ODI0NyAxNC42MTg0TCAxMy40NTA4IDE2LjY4MkMgMTQuMjM4MyAxNS40OTE1IDE1LjUyNyAxNC42OTc4IDE2Ljk1ODggMTQuNjk3OEMgMTkuMzIxNCAxNC42OTc4IDIxLjI1NDQgMTYuODQwNyAyMS4yNTQ0IDE5LjQ1OTlDIDIxLjI1NDQgMjIuMDc5IDE5LjMyMTQgMjQuMjIxOSAxNi45NTg4IDI0LjIyMTlDIDE0LjU5NjMgMjQuMjIxOSAxMi42NjMzIDIyLjA3OSAxMi42NjMzIDE5LjQ1OTlDIDEyLjY2MzMgMTkuMzQwOCAxMi42ODExIDE5LjIyMTggMTIuNjk5IDE5LjEwMjdDIDEyLjcxNjkgMTguOTgzNyAxMi43MzQ4IDE4Ljg2NDYgMTIuNzM0OCAxOC43NDU1TCA2Ljc5MjY1IDE2LjIwNThDIDYuMzA2MDcgMTUuOTM2IDYuMzg3ODQgMTUuMjY1MyA2LjU5OTA3IDE0LjY4MDNDIDYuNjE1MTcgMTQuNjM1NyA2LjYzMjAyIDE0LjU5MTcgNi42NDk0MiAxNC41NDgzQyA2LjY2ODA4IDE0LjUwMTggNi42ODczOCAxNC40NTYxIDYuNzA3MDggMTQuNDExNUMgNi43MTE3IDE0LjQwMTEgNi43MTY0IDE0LjM5MDYgNi43MjEwNiAxNC4zODAzTCA2LjY0OTQ3IDE0LjM4MDNMIDYuODY0MjUgMTMuOTgzNUMgOS42NTYzNiA3Ljc5Mjc2IDE2LjM4NjEgNS4yNTI5OCAyMS45NzAzIDguMjY4OTZDIDI0LjY5MDggOS43NzY5NiAyNi42OTU0IDEyLjMxNjcgMjcuNjk3NyAxNS40OTE1QyAyOC42Mjg0IDE4LjY2NjIgMjguNDg1MiAyMS45OTk2IDI3LjEyNSAyNS4wMTU2QyAyNS4xMjA0IDI5LjQ2MDIgMjEuMTExMiAzMiAxNi45NTg4IDMyQyAxNS4zMTIyIDMyIDEzLjU5NCAzMS42MDMyIDEyLjAxODkgMzAuNzMwMVpNIDYuNTk1MjIgMTMuODUwMkMgOS40NTI5OCA3LjUyNzIzIDE2LjM2MDQgNC44OTgxOSAyMi4xMTI4IDguMDA1MDJMIDIyLjExNTcgOC4wMDY1OEMgMjQuOTA5MSA5LjU1NDk1IDI2Ljk2MDQgMTIuMTU5NiAyNy45ODM4IDE1LjQwMTFMIDI3Ljk4NTYgMTUuNDA3MUMgMjguOTI2MiAxOC42Mjk5IDI4Ljc3NzEgMjIuMDY5IDI3LjM5ODQgMjUuMTM5QyAyNS4zNDg5IDI5LjY4MzIgMjEuMjM3NiAzMi4zIDE2Ljk1ODggMzIuM0MgMTUuMjc4MiAzMi4yOTgxIDEzLjQ4NjQgMzEuODgzMiAxMS44NzM0IDMwLjk5MjVMIDExLjg2ODYgMzAuOTg5OUMgMTAuMjM1NiAzMC4wNDMxIDguNzg1MjYgMjguNjkxMSA3LjY5ODU5IDI3LjA1M0MgNi43MTQyMyAyNi45NDk3IDUuOTkxNDYgMjUuOTQ5NiA1Ljk5MTQ2IDI0LjkzNjNDIDUuOTkxNDYgMjMuNzY4NiA2LjgxMzM1IDIyLjgxMDggNy45MzgwOSAyMi44MTA4QyA4Ljk3ODc4IDIyLjgxMDggOS44OTcyOSAyMy44MzkyIDkuNzQxMDUgMjQuOTYxMkMgOS43MzUwMyAyNS4xODExIDkuNjgzMDYgMjUuMzk5OCA5LjYyNjc5IDI1LjU5NzJDIDEwLjUyMjcgMjYuODY4MiAxMS41ODkxIDI3Ljg4NzkgMTIuODczMiAyOC41NTkxQyAxNS4wODkgMjkuNzEwNiAxNy41NzczIDI5Ljg2MzQgMTkuODU5IDI5LjAyMTFDIDIyLjIxNzYgMjguMDk3NSAyNC4wODg4IDI2LjMzMDQgMjUuMTMwNCAyMy44NjdMIDI1LjEzMjUgMjMuODYyMUMgMjcuMzY1OSAxOC44MzI3IDI1LjQ3MTQgMTIuNzM4NiAyMC45NzAxIDEwLjI3OTlDIDE4Ljc1NDUgOS4xMjk0NiAxNi4yNjY4IDguOTc3MjQgMTMuOTg1NyA5LjgxOTc0QyAxMS44NjY4IDEwLjYyNzcgMTAuMTE3OCAxMi4yODQ3IDguOTkzMTMgMTQuNDY2N0wgMTMuMzQzOSAxNi4zMTA5QyAxNC4xODgyIDE1LjE2MjIgMTUuNDk3OCAxNC4zOTc4IDE2Ljk1ODggMTQuMzk3OEMgMTkuNTE1NCAxNC4zOTc4IDIxLjU1NDMgMTYuNzA0OSAyMS41NTQzIDE5LjQ1OTlDIDIxLjU1NDMgMjIuMjE0OSAxOS41MTU0IDI0LjUyMTkgMTYuOTU4OCAyNC41MjE5QyAxNC40MDIyIDI0LjUyMTkgMTIuMzYzMiAyMi4yMTQ5IDEyLjM2MzIgMTkuNDU5OUMgMTIuMzY0IDE5LjMxODUgMTIuMzg0NCAxOS4xNzcyIDEyLjQwMjMgMTkuMDU4MUwgMTIuNDA1IDE5LjA0MDVDIDEyLjQxMDQgMTkuMDA0MyAxMi40MTU2IDE4Ljk3MDEgMTIuNDIgMTguOTM3M0wgNi42NjA1OSAxNi40NzU2TCA2LjY0NzE2IDE2LjQ2ODJDIDYuMjE2ODUgMTYuMjI5NiA2LjExNDI3IDE1Ljc4ODEgNi4xMzUwNiAxNS4zOTYyQyA2LjE0ODkzIDE1LjE2NTkgNi4yMDY3NiAxNC45MTU3IDYuMjgzNjUgMTQuNjgwM0wgNi4xNDU5MyAxNC42ODAzTCA2LjU5NTIyIDEzLjg1MDJaIi8+PHBhdGggaWQ9InBhdGgyX2ZpbGwiIGQ9Ik0gMzIuMTI4MSAxMC4wMTY4QyAzMi4xMjgxIDkuMDA2MyAzMS4zOTU1IDguMjI4OTkgMzAuNDQzMiA4LjIyODk5QyAyOS40OTA4IDguMjI4OTkgMjguNzU4MiA5LjAwNjMgMjguNzU4MiAxMC4wMTY4QyAyOC43NTgyIDEwLjk0OTYgMjkuNDkwOCAxMS43MjY5IDMwLjM2OTkgMTEuODA0NkMgMzIuODYwNyAxNi4zOTA4IDMzLjA4MDUgMjIuMDY1MSAzMC44ODI3IDI2Ljg4NDVDIDI5LjA1MTIgMzAuNzcxIDI1Ljk3NDMgMzMuNjQ3MSAyMi4xNjQ4IDM0Ljk2ODVDIDE4LjM1NTMgMzYuMzY3NyAxNC4yNTI4IDM2LjA1NjcgMTAuNjYzMSAzNC4xOTEyQyAzLjE5MDYyIDMwLjMwNDYgMC4xMTM3MjQgMjAuNjY2IDMuNzc2NyAxMi43Mzc0QyA1Ljk3NDQ4IDcuOTk1OCAxMC4yOTY4IDQuNzMxMDkgMTUuMTMxOSAzLjk1Mzc4QyAxNS40MjUgNC4zNDI0NCAxNS44NjQ1IDQuNTc1NjMgMTYuMzc3MyA0LjU3NTYzQyAxNy4zMjk3IDQuNTc1NjMgMTguMDYyMyAzLjc5ODMyIDE4LjA2MjMgMi43ODc4MkMgMTguMDYyMyAxLjc3NzMxIDE3LjMyOTcgMSAxNi4zNzczIDFDIDE1LjcxOCAxIDE1LjIwNTIgMS4zODg2NiAxNC45MTIxIDEuOTMyNzdDIDkuNDE3NjggMi43ODc4MiA0LjUwOTMgNi41MTg5MSAyLjAxODQ3IDExLjg4MjRDIDAuMDQwNDY1NiAxNi4yMzUzIC0wLjI1MjU3MyAyMS4xMzI0IDEuMjEyNjIgMjUuNzE4NUMgMi42Nzc4MSAzMC4zMDQ2IDUuNzU0NzEgMzMuOTU4IDkuNzgzOTggMzYuMTM0NUMgMTIuMjAxNSAzNy4zNzgyIDE0Ljc2NTYgMzggMTcuMjU2NCAzOEMgMjMuNTU2OCAzOCAyOS42MzczIDM0LjI2ODkgMzIuNTY3NyAyNy44OTVDIDM1LjA1ODUgMjIuNDUzOCAzNC44Mzg3IDE2LjAwMjEgMzEuOTgxNiAxMC43OTQxQyAzMi4wNTQ5IDEwLjU2MDkgMzIuMTI4MSAxMC4zMjc3IDMyLjEyODEgMTAuMDE2OFoiLz48cGF0aCBpZD0icGF0aDNfZmlsbCIgZmlsbC1ydWxlPSJldmVub2RkIiBkPSJNIDIuMDE4NDcgMTEuODgyNEMgNC41MDkzIDYuNTE4OTEgOS40MTc2OCAyLjc4NzgyIDE0LjkxMjEgMS45MzI3N0MgMTUuMjA1MiAxLjM4ODY2IDE1LjcxOCAxIDE2LjM3NzMgMUMgMTcuMzI5NyAxIDE4LjA2MjMgMS43NzczMSAxOC4wNjIzIDIuNzg3ODJDIDE4LjA2MjMgMy43OTgzMiAxNy4zMjk3IDQuNTc1NjMgMTYuMzc3MyA0LjU3NTYzQyAxNS44NjQ1IDQuNTc1NjMgMTUuNDI1IDQuMzQyNDQgMTUuMTMxOSAzLjk1Mzc4QyAxMC4yOTY4IDQuNzMxMDkgNS45NzQ0OCA3Ljk5NTggMy43NzY3IDEyLjczNzRDIDAuMTEzNzI0IDIwLjY2NiAzLjE5MDYyIDMwLjMwNDYgMTAuNjYzMSAzNC4xOTEyQyAxNC4yNTI4IDM2LjA1NjcgMTguMzU1MyAzNi4zNjc3IDIyLjE2NDggMzQuOTY4NUMgMjUuOTc0MyAzMy42NDcxIDI5LjA1MTIgMzAuNzcxIDMwLjg4MjcgMjYuODg0NUMgMzMuMDgwNSAyMi4wNjUxIDMyLjg2MDcgMTYuMzkwOCAzMC4zNjk5IDExLjgwNDZDIDI5LjQ5MDggMTEuNzI2OSAyOC43NTgyIDEwLjk0OTYgMjguNzU4MiAxMC4wMTY4QyAyOC43NTgyIDkuMDA2MyAyOS40OTA4IDguMjI4OTkgMzAuNDQzMiA4LjIyODk5QyAzMS4zOTU1IDguMjI4OTkgMzIuMTI4MSA5LjAwNjMgMzIuMTI4MSAxMC4wMTY4QyAzMi4xMjgxIDEwLjMyNzcgMzIuMDU0OSAxMC41NjA5IDMxLjk4MTYgMTAuNzk0MUMgMzQuODM4NyAxNi4wMDIxIDM1LjA1ODUgMjIuNDUzOCAzMi41Njc3IDI3Ljg5NUMgMjkuNjM3MyAzNC4yNjg5IDIzLjU1NjggMzggMTcuMjU2NCAzOEMgMTQuNzY1NiAzOCAxMi4yMDE1IDM3LjM3ODIgOS43ODM5OCAzNi4xMzQ1QyA1Ljc1NDcxIDMzLjk1OCAyLjY3NzgxIDMwLjMwNDYgMS4yMTI2MiAyNS43MTg1QyAtMC4yNTI1NzMgMjEuMTMyNCAwLjA0MDQ2NTYgMTYuMjM1MyAyLjAxODQ3IDExLjg4MjRaTSAzMi4zMDU1IDEwLjc2MTZDIDMyLjM2NzEgMTAuNTU0NiAzMi40MjUzIDEwLjMxMjkgMzIuNDI4MiAxMC4wMTY4QyAzMi40MjgyIDguODU3NjIgMzEuNTc3NyA3LjkyOSAzMC40NDMyIDcuOTI5QyAyOS4zMDg2IDcuOTI5IDI4LjQ1ODIgOC44NTc2MiAyOC40NTgyIDEwLjAxNjhDIDI4LjQ1ODIgMTEuMDQ2IDI5LjIyIDExLjkxMzggMzAuMTc4MiAxMi4wODE3QyAzMi41NTYgMTYuNTYwOSAzMi43NDcgMjIuMDcyIDMwLjYxMDYgMjYuNzU4MkMgMjguODEwMyAzMC41Nzc2IDI1Ljc5MjYgMzMuMzkyNiAyMi4wNjY1IDM0LjY4NTFMIDIyLjA2MTQgMzQuNjg2OUMgMTguMzMzNiAzNi4wNTYxIDE0LjMxODcgMzUuNzUyOSAxMC44MDE2IDMzLjkyNUMgMy40ODA1NiAzMC4xMTczIDAuNDQ4ODY2IDIwLjY1NjIgNC4wNDkgMTIuODYzNEMgNi4xODA3MiA4LjI2NDQ2IDEwLjM0NjEgNS4wODY4MiAxNS4wMTE4IDQuMjc4MDFDIDE1LjM1NTUgNC42NTMxMSAxNS44MzM0IDQuODc1NjQgMTYuMzc3NCA0Ljg3NTY0QyAxNy41MTE5IDQuODc1NjQgMTguMzYyMyAzLjk0NzAyIDE4LjM2MjMgMi43ODc4M0MgMTguMzYyMyAxLjYyODYzIDE3LjUxMTkgMC43MDAwMTIgMTYuMzc3NCAwLjcwMDAxMkMgMTUuNjQyMiAwLjcwMDAxMiAxNS4wNjk3IDEuMTA1NDggMTQuNzI0MyAxLjY1OTA1QyA5LjE4NDAxIDIuNTcxNzcgNC4yNTYxMiA2LjM1MTg5IDEuNzQ2NDEgMTEuNzU2TCAxLjc0NTM4IDExLjc1ODNDIC0wLjI1MDY3OCAxNi4xNjMxIC0wLjU1MDIxIDIxLjE2OTcgMC45MjY4NzkgMjUuODA5OEMgMi40MTU1NyAzMC40Njk1IDUuNTQzNTggMzQuMTg0OSA5LjY0MTQzIDM2LjM5ODRMIDkuNjQ2NzcgMzYuNDAxMkMgMTIuMDk2OSAzNy42NTkzIDE0LjczMDEgMzguMjk3OSAxNy4yNTY1IDM4LjNDIDIzLjY3NiAzOC4zIDI5Ljg2MTggMzQuNDk4OSAzMi44NDAzIDI4LjAyMDNDIDM1LjM1MjQgMjIuNTMyNiAzNS4xNTA4IDE2LjAzMzggMzIuMzA1NSAxMC43NjE2WiIvPjxwYXRoIGlkPSJwYXRoNF9maWxsIiBkPSJNIDE5LjI5MTUgMi41QyAxOS4yOTE1IDMuODgwNzEgMTguMTcyMiA1IDE2Ljc5MTUgNUMgMTUuNDEwOCA1IDE0LjI5MTUgMy44ODA3MSAxNC4yOTE1IDIuNUMgMTQuMjkxNSAxLjExOTI5IDE1LjQxMDggMCAxNi43OTE1IDBDIDE4LjE3MjIgMCAxOS4yOTE1IDEuMTE5MjkgMTkuMjkxNSAyLjVaIi8+PHBhdGggaWQ9InBhdGg1X2ZpbGwiIGQ9Ik0gMzMuMjkxNSAxMC41QyAzMy4yOTE1IDExLjg4MDcgMzIuMTcyMiAxMyAzMC43OTE1IDEzQyAyOS40MTA4IDEzIDI4LjI5MTUgMTEuODgwNyAyOC4yOTE1IDEwLjVDIDI4LjI5MTUgOS4xMTkyOSAyOS40MTA4IDggMzAuNzkxNSA4QyAzMi4xNzIyIDggMzMuMjkxNSA5LjExOTI5IDMzLjI5MTUgMTAuNVoiLz48cGF0aCBpZD0icGF0aDZfZmlsbCIgZD0iTSAxMC4yOTE1IDI1LjVDIDEwLjI5MTUgMjYuODgwNyA5LjE3MjIyIDI4IDcuNzkxNSAyOEMgNi40MTA3OSAyOCA1LjI5MTUgMjYuODgwNyA1LjI5MTUgMjUuNUMgNS4yOTE1IDI0LjExOTMgNi40MTA3OSAyMyA3Ljc5MTUgMjNDIDkuMTcyMjIgMjMgMTAuMjkxNSAyNC4xMTkzIDEwLjI5MTUgMjUuNVoiLz48L2RlZnM+PC9zdmc+'
	);

	add_action( "load-$hook", __NAMESPACE__ . '\screen_option' );
}

/**
 * Set up sub menu item to be last
 *
 * @since 0.8
 */
function add_submenu_item() {
	global $submenu;
	unset( $submenu['distributor'][0] );
	add_submenu_page(
		'distributor',
		esc_html__( 'External Connections', 'distributor' ),
		esc_html__( 'External Connections', 'distributor' ),
		/**
		 * Filter Distributor capabilities allowed to manage external connections.
		 *
		 * @since 1.0.0
		 * @hook dt_external_capabilities
		 *
		 * @param {string} 'manage_options' The capability allowed to manage external connections.
		 *
		 * @return {string} The capability allowed to manage external connections.
		 */
		apply_filters( 'dt_external_capabilities', 'manage_options' ),
		'distributor'
	);
}

/**
 * Register connection post type
 *
 * @since 0.8
 */
function setup_cpt() {

	$labels = array(
		'name'               => esc_html__( 'External Connections', 'distributor' ),
		'singular_name'      => esc_html__( 'External Connection', 'distributor' ),
		'add_new'            => esc_html__( 'Add New', 'distributor' ),
		'add_new_item'       => esc_html__( 'Add New External Connection', 'distributor' ),
		'edit_item'          => esc_html__( 'Edit External Connection', 'distributor' ),
		'new_item'           => esc_html__( 'New External Connection', 'distributor' ),
		'all_items'          => esc_html__( 'All External Connections', 'distributor' ),
		'view_item'          => esc_html__( 'View External Connection', 'distributor' ),
		'search_items'       => esc_html__( 'Search External Connections', 'distributor' ),
		'not_found'          => esc_html__( 'No external connections found.', 'distributor' ),
		'not_found_in_trash' => esc_html__( 'No external connections found in trash.', 'distributor' ),
		'filter_items_list'  => esc_html__( 'Filter connections list', 'distributor' ),
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__( 'Distributor', 'distributor' ),
	);

	$args = array(
		'labels'               => $labels,
		'public'               => false,
		'publicly_queryable'   => false,
		'show_ui'              => true,
		'show_in_menu'         => false,
		'query_var'            => false,
		'rewrite'              => false,
		'capability_type'      => 'post',
		'hierarchical'         => false,
		'supports'             => array( 'title' ),
		'register_meta_box_cb' => __NAMESPACE__ . '\add_meta_boxes',
	);

	register_post_type( 'dt_ext_connection', $args );
}

/**
 * Filter CPT messages
 *
 * @param  array $messages Messages array.
 * @since  0.8
 * @return array
 */
function filter_post_updated_messages( $messages ) {
	global $post;

	$messages['dt_ext_connection'] = array(
		0  => '',
		1  => esc_html__( 'External connection updated.', 'distributor' ),
		2  => esc_html__( 'Custom field updated.', 'distributor' ),
		3  => esc_html__( 'Custom field deleted.', 'distributor' ),
		4  => esc_html__( 'External connection updated.', 'distributor' ),
		/* translators: %s: revision title */
		5  => isset( $_GET['revision'] ) ? sprintf( __( ' External connection restored to revision from %s', 'distributor' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // @codingStandardsIgnoreLine Nonce not required
		6  => esc_html__( 'External connection created.', 'distributor' ),
		7  => esc_html__( 'External connection saved.', 'distributor' ),
		8  => esc_html__( 'External connection submitted.', 'distributor' ),
		9  => sprintf(
			/* translators: %s: a date and time */
			__( 'External connection scheduled for: <strong>%1$s</strong>.', 'distributor' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
		),
		10 => esc_html__( 'External connection draft updated.', 'distributor' ),
	);

	return $messages;
}

/**
 * Get the REST API root of the remote site.
 *
 * @since  1.6.0
 *
 * @param string $site_url Remote site URL.
 *
 * @return false|string|WP_Error
 */
function get_rest_url( $site_url ) {

	$source = wp_remote_get( $site_url );

	if ( is_wp_error( $source ) ) {
		return $source;
	}

	$dom = new \DOMDocument();
	// The HTML may be imperfect, use @ to suppress warnings.
	@$dom->loadHTML( wp_remote_retrieve_body( $source ) ); // phpcs:ignore
	$links = $dom->getElementsByTagName( 'link' );

	foreach ( $links as $link ) {
		if ( 'https://api.w.org/' === $link->getAttribute( 'rel' ) ) {
			return $link->getAttribute( 'href' );
		}
	}

	return new \WP_Error( 'rest_api_uri_not_found', __( 'The external site is private or not a WordPress site.', 'distributor' ) );
}

/**
 * Get the Distributor version of the remote site.
 *
 * @since  1.6.0
 */
function get_remote_distributor_info() {
	if (
		! check_ajax_referer( 'dt-verify-ext-conn', 'nonce', false )
		|| empty( $_POST['url'] )
	) {
		wp_send_json_error();
		exit;
	}

	$rest_url = get_rest_url( $_POST['url'] );

	if ( is_wp_error( $rest_url ) ) {
		wp_send_json_error( $rest_url );
		exit;
	}

	$route = $rest_url . 'wp/v2/dt_meta';

	if ( function_exists( 'vip_safe_wp_remote_get' ) && \Distributor\Utils\is_vip_com() ) {
		$response = vip_safe_wp_remote_get( $route, false, 3, 3, 10 );
	} else {
		$response = wp_remote_get( $route, [ 'timeout' => 5 ] );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['version'] ) ) {
		wp_send_json_error( [ 'rest_url' => $rest_url ] );
		exit;
	}

	wp_send_json_success( array_merge( $body, [ 'rest_url' => $rest_url ] ) );
	exit;
}
