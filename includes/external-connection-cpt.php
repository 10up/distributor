<?php

namespace Distributor\ExternalConnectionCPT;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'init', __NAMESPACE__ . '\setup_cpt' );
			add_filter( 'enter_title_here', __NAMESPACE__ . '\filter_enter_title_here', 10, 2 );
			add_filter( 'post_updated_messages', __NAMESPACE__ . '\filter_post_updated_messages' );
			add_action( 'save_post', __NAMESPACE__ . '\save_post' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
			add_action( 'wp_ajax_dt_verify_external_connection', __NAMESPACE__ . '\ajax_verify_external_connection' );
			add_action( 'wp_ajax_dt_verify_external_connection_endpoint', __NAMESPACE__ . '\ajax_verify_external_connection_endpoint' );
			add_filter( 'manage_dt_ext_connection_posts_columns', __NAMESPACE__ . '\filter_columns' );
			add_action( 'manage_dt_ext_connection_posts_custom_column', __NAMESPACE__ . '\action_custom_columns', 10, 2 );
			add_action( 'admin_menu', __NAMESPACE__ . '\add_menu_item' );
			add_action( 'admin_menu', __NAMESPACE__ . '\add_submenu_item', 11 );
			add_action( 'load-toplevel_page_distributor', __NAMESPACE__ . '\setup_list_table' );
			add_filter( 'set-screen-option', __NAMESPACE__ . '\set_screen_option', 10, 3 );
		}
	);
}

/**
 * Set screen option for posts per page
 *
 * @param  string $status
 * @param  string $option
 * @param  mixed  $value
 * @since  0.8
 * @return mixed
 */
function set_screen_option( $status, $option, $value ) {
	return $value;
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
 * @param  array $columns
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
 * @param  string $column
 * @param  int    $post_id
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

	$current_auth = get_post_meta( $_POST['endpoint_id'], 'dt_external_connection_auth', true );

	if ( ! empty( $current_auth ) ) {
		$auth = array_merge( $auth, (array) $current_auth );
	}

	// Create an instance of the connection to test connections
	$external_connection_class = \Distributor\Connections::factory()->get_registered()[ $_POST['type'] ];

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
 * @param  string $hook
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( ( 'post.php' === $hook && 'dt_ext_connection' === get_post_type() ) || ( 'post-new.php' === $hook && ! empty( $_GET['post_type'] ) && 'dt_ext_connection' === $_GET['post_type'] ) ) {

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$js_path  = '/assets/js/src/admin-external-connection.js';
			$css_path = '/assets/css/admin-external-connection.css';
		} else {
			$js_path  = '/assets/js/admin-external-connection.min.js';
			$css_path = '/assets/css/admin-external-connection.min.css';
		}

		wp_enqueue_style( 'dt-admin-external-connection', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
		wp_enqueue_script( 'dt-admin-external-connection', plugins_url( $js_path, __DIR__ ), array( 'jquery', 'underscore' ), DT_VERSION, true );

		wp_localize_script(
			'dt-admin-external-connection', 'dt', array(
				'nonce'                     => wp_create_nonce( 'dt-verify-ext-conn' ),
				'bad_connection'            => esc_html__( 'No connection found.', 'distributor' ),
				'good_connection'           => esc_html__( 'Connection established.', 'distributor' ),
				'limited_connection'        => esc_html__( 'Limited connection established.', 'distributor' ),
				'endpoint_suggestion'       => esc_html__( 'Did you mean: ', 'distributor' ),
				'endpoint_checking_message' => esc_html__( 'Checking endpoint...', 'distributor' ),
				'no_push'                   => esc_html__( 'Push unavailable.', 'distributor' ),
				'change'                    => esc_html__( 'Change', 'distributor' ),
				'cancel'                    => esc_html__( 'Cancel', 'distributor' ),
				'no_distributor'            => esc_html__( 'Distributor not installed on remote site.', 'distributor' ),
				'roles_warning'             => esc_html__( 'Be careful assigning less trusted roles push privileges as they will inherit the capabilities of the user on the remote site.', 'distributor' ),
			)
		);

		wp_dequeue_script( 'autosave' );
	}

	if ( ! empty( $_GET['page'] ) && 'distributor' === $_GET['page'] ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$css_path = '/assets/css/admin-external-connections.css';
		} else {
			$css_path = '/assets/css/admin-external-connections.min.css';
		}

		wp_enqueue_style( 'dt-admin-external-connections', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
	}

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$css_path = '/assets/css/admin.css';
	} else {
		$css_path = '/assets/css/admin.min.css';
	}

	wp_enqueue_style( 'dt-admin', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
}

/**
 * Change title text box label
 *
 * @param  string $label
 * @param  int    $post
 * @since  0.8
 * @return string
 */
function filter_enter_title_here( $label, $post = 0 ) {
	if ( 'dt_ext_connection' !== get_post_type( $post->ID ) ) {
		return $label;
	}

	return esc_html__( 'Enter external connection name', 'distributor' );
}

/**
 * Save external connection stuff
 *
 * @param int $post_id
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
		$external_connection_class = \Distributor\Connections::factory()->get_registered()[ $_POST['dt_external_connection_type'] ];

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

		$connection_class         = \Distributor\Connections::factory()->get_registered()[ $_POST['dt_external_connection_type'] ];
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
 * @param $post
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

	$registered_external_connection_types = \Distributor\Connections::factory()->get_registered();

	foreach ( $registered_external_connection_types as $slug => $class ) {
		if ( 'Distributor\ExternalConnection' !== get_parent_class( $class ) ) {
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
	if ( 1 === count( $registered_external_connection_types ) ) :
		$registered_connection_types_keys = array_keys( $registered_external_connection_types );
?>
		<input id="dt_external_connection_type" class="external-connection-type-field" type="hidden" name="dt_external_connection_type" value="<?php echo esc_attr( $registered_connection_types_keys[0] ); ?>">
	<?php else : ?>
		<p>
			<label for="dt_external_connection_type"><?php esc_html_e( 'External Connection Type', 'distributor' ); ?></label><br>
			<select name="dt_external_connection_type" class="external-connection-type-field" id="dt_external_connection_type">
				<?php foreach ( $registered_external_connection_types as $slug => $external_connection_class ) : ?>
					<option <?php selected( $slug, $external_connection_type ); ?> value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_attr( $external_connection_class::$label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="description"><?php esc_html_e( 'We need to know what type of API we are communicating with.', 'distributor' ); ?></span>
		</p>
	<?php endif; ?>

	<?php
	foreach ( $registered_external_connection_types as $external_connection_class ) :
		$auth_handler_class_again = $external_connection_class::$auth_handler_class;
		if ( ! $auth_handler_class_again::$requires_credentials ) {
			continue; }
		?>
		<div class="auth-credentials <?php echo esc_attr( $auth_handler_class_again::$slug ); ?>">
			<?php $auth_handler_class_again::credentials_form( $auth ); ?>
		</div>
	<?php endforeach; ?>
	<div class="connection-field-wrap">
		<label for="dt_external_connection_url"><?php esc_html_e( 'External Connection URL', 'distributor' ); ?></label><br>
		<span class="external-connection-url-field-wrapper">
			<input value="<?php echo esc_url( $external_connection_url ); ?>" type="text" name="dt_external_connection_url" id="dt_external_connection_url" class="widefat external-connection-url-field">
		</span>

		<span class="description endpoint-result"></span>
		<ul class="endpoint-errors"></ul>
	</div>

	<p class="dt-roles-allowed">
		<label><?php esc_html_e( 'Roles Allowed to Push', 'distributor' ); ?></label><br>

		<?php
		$editable_roles = get_editable_roles();
		foreach ( $editable_roles as $role => $details ) {
			$name = translate_user_role( $details['name'] );
			?>

			<label for="dt-role-<?php echo esc_attr( $role ); ?>"><input class="dt-role-checkbox" name="dt_external_connection_allowed_roles[]" id="dt-role-<?php echo esc_attr( $role ); ?>" type="checkbox" <?php checked( true, in_array( $role, $allowed_roles, true ) ); ?> value="<?php echo esc_attr( $role ); ?>"> <?php echo esc_html( $name ); ?></label><br>

			<?php
		}
		?>
		<span class="description"><?php esc_html_e( 'Please be warned all these users will inherit the permissions of the user on the remote site', 'distributor' ); ?></p>
	</p>

	<p>
		<input type="hidden" name="post_status" value="publish">
		<input type="hidden" name="original_post_status" value="<?php echo esc_attr( $post->post_status ); ?>">

		<?php if ( 0 < strtotime( $post->post_date_gmt . ' +0000' ) ) : ?>

			<input name="save" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Update Connection', 'distributor' ); ?>">

			<a class="delete-link" href="<?php echo esc_url( get_delete_post_link( $post->ID ) ); ?> "><?php esc_html_e( 'Move to Trash', 'distributor' ); ?></a>
		<?php else : ?>
			<input name="publish" type="submit" class="button button-primary button-large" id="publish" value="<?php esc_attr_e( 'Create Connection', 'distributor' ); ?>">
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

	$_GET['post_type'] = 'dt_ext_connection';

	$connection_list_table->prepare_items();
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'External Connections', 'distributor' ); ?> <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=dt_ext_connection' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'distributor' ); ?></a></h1>

		<?php $connection_list_table->views(); ?>

		<form id="posts-filter" method="get">

		<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty( $_REQUEST['post_status'] ) ? esc_attr( $_REQUEST['post_status'] ) : 'all'; ?>">
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
		apply_filters( 'dt_capabilities', 'manage_options' ),
		'distributor',
		__NAMESPACE__ . '\dashboard',
		'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHdpZHRoPSIxNnB4IiBoZWlnaHQ9IjE2cHgiIHZpZXdCb3g9IjAgMCAxNiAxNiIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4gICAgICAgIDx0aXRsZT5sb2dvLXN5bWJvbC1wYW5lbDwvdGl0bGU+ICAgIDxkZXNjPkNyZWF0ZWQgd2l0aCBTa2V0Y2guPC9kZXNjPiAgICA8ZGVmcz48L2RlZnM+ICAgIDxnIGlkPSJQYWdlLTEiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPiAgICAgICAgPGcgaWQ9ImxvZ28tc3ltYm9sLXBhbmVsIiBmaWxsPSIjQTBBNUFBIiBmaWxsLXJ1bGU9Im5vbnplcm8iPiAgICAgICAgICAgIDxnIGlkPSJWZWN0b3IiPiAgICAgICAgICAgICAgICA8ZyBpZD0icGF0aDBfZmlsbC1saW5rIj4gICAgICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik0xMS41MzU0MTU0LDAuODA5NDI0Njc1IEM3LjU1MzYwNzI0LC0xLjEwNTc5NzI4IDIuNzQzMjkxMywwLjQ5NDU5MjE1NyAwLjgxOTE3MDc2OSw0LjM3NzUwNTEyIEMtMS4xMzE2Njc1Nyw4LjI2MDM4Mzc0IDAuNTI1MjA5MTI4LDEyLjk4Mjg2NzkgNC40ODAzNDMzNCwxNC44OTgxMTkgQzUuNjI5NDY0NzksMTUuNDQ5MDU5NyA2LjgzMjA0Mzc4LDE1LjcxMTQxNTkgOC4wMzQ2MzAwNywxNS43MTE0MTU5IEM4LjE0MTUxNTkxLDE1Ljg2ODc4NTcgOC4zMjg1NDc4NywxNi4wMDAwMDA0IDguNTQyMzkyNjIsMTYuMDAwMDAwNCBDOC44ODk3ODk4OCwxNi4wMDAwMDA0IDkuMTU3MDQxMDIsMTUuNzM3NjQ0MiA5LjE1NzA0MTAyLDE1LjM5NjYwMzEgQzkuMTU3MDQxMDIsMTUuMDU1NDg4OSA4Ljg4OTc4OTg4LDE0Ljc5MzEzMjcgOC41NDIzOTI2MiwxNC43OTMxMzI3IEM4LjMyODU0Nzg3LDE0Ljc5MzEzMjcgOC4xNDE1MTU5MSwxNC44OTgxMTkgOC4wMzQ2MzAwNywxNS4wODE3MTcyIEM2LjkzODkzNjkzLDE1LjA4MTcxNzIgNS44MTY1MzMyOCwxNC44NDU1ODk0IDQuNzc0MzA0OTgsMTQuMzQ3MTA1MyBDMS4xMTMxMzI0MSwxMi41ODkzNzAxIC0wLjM4MzQwMjM3MSw4LjIzNDE1NTQzIDEuNDA3MDk0MDUsNC42NjYwOTY5MSBDMy4xOTc1OTYzMiwxLjA5ODAxNjQ2IDcuNjA3MDEzNjMsLTAuMzcxMTkxOTcyIDExLjI0MTQ5NzYsMS4zODY2MTU1NiBDMTQuNjg4ODc2NiwzLjAzOTQ3NDA0IDE2LjIxMjA5MTIsNy4wMjczMjQzNiAxNC44NDkxNjg4LDEwLjQ5MDQ0NzcgQzE0LjU1NTI1MSwxMC41MTY2NzYgMTQuMzE0NzM5NiwxMC43NzkwMzIyIDE0LjMxNDczOTYsMTEuMDkzOTE4MSBDMTQuMzE0NzM5NiwxMS40MzQ5NTkyIDE0LjU4MTk5MDcsMTEuNjk3MzE1MyAxNC45MjkzODgsMTEuNjk3MzE1MyBDMTUuMjc2Nzg1MywxMS42OTczMTUzIDE1LjU0NDAzNjQsMTEuNDM0OTU5MiAxNS41NDQwMzY0LDExLjA5MzkxODEgQzE1LjU0NDAzNjQsMTAuOTYyNzAzNCAxNS41MTcyOTY3LDEwLjg1Nzc5MDIgMTUuNDM3MTUwNiwxMC43NzkwMzIyIEMxNi45NjAzNjUyLDYuOTc0ODUzMTIgMTUuMzAzNTI1LDIuNjE5Njk2ODYgMTEuNTM1NDE1NCwwLjgwOTQyNDY3NSBaIiBpZD0icGF0aDBfZmlsbCI+PC9wYXRoPiAgICAgICAgICAgICAgICA8L2c+ICAgICAgICAgICAgPC9nPiAgICAgICAgICAgIDxnIGlkPSJWZWN0b3IiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDMuOTQ1MjA1LCAzLjY1Mjk2OCkiPiAgICAgICAgICAgICAgICA8ZyBpZD0icGF0aDFfZmlsbC1saW5rIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgwLjA1NDA2NCwgMC4wNjg2NzYpIj4gICAgICAgICAgICAgICAgICAgIDxwYXRoIGQ9Ik0wLjYxNDY0ODQwMiw1LjMyNTU4MTc0IEMwLjI2NzIzODcyMSw1LjMyNTU4MTc0IDAsNS41ODc5Mzc5IDAsNS45MjkwMDA5MSBDMCw2LjI0MzgzNTYyIDAuMjY3MjM4NzIxLDYuNTMyNDI3NCAwLjU4NzkyNTQ3OSw2LjUzMjQyNzQgQzAuOTg4Nzg1Mzg4LDcuMDgzMzgyNjUgMS41MjMyNTg0NSw3LjUyOTQyNDY2IDIuMTM3OTA2ODUsNy44NDQyMzc0NCBDMi43MjU4Mzc0NCw4LjEzMjgyMTkyIDMuMzY3MjAzNjUsOC4yNjM5NjM0NyAzLjk4MTg1OTM2LDguMjYzOTYzNDcgQzUuNTMxODQyOTIsOC4yNjM5NjM0NyA3LjAyODM3NjI2LDcuNDI0NDM4MzYgNy43NzY2NTc1Myw1Ljk1NTI0Mzg0IEM4LjI4NDQyMDA5LDQuOTU4Mjc1OCA4LjMzNzgyNjQ4LDMuODU2MzcyNiA3Ljk5MDQyOTIyLDIuODA2OTMzMzMgQzcuNjE2MjkyMjQsMS43NTc1MDEzNyA2Ljg2ODAzMjg4LDAuOTE3OTQ3MDMyIDUuODUyNTI5NjgsMC40MTk0Njg4NTggQzMuNzY4MDY1NzUsLTAuNTc3NDk0Nzk1IDEuMjU2MDIxOTIsMC4yNjIwNTM2OTkgMC4yMTM3OTA2ODUsMi4zMDg0NDkzMiBMMC4xMzM2MTg5OTUsMi40Mzk2MzQ3IEwwLjE2MDM0MzM3OSwyLjQzOTYzNDcgQzAuMDUzNDQ3ODE3NCwyLjY0OTUxOTYzIC0wLjAyNjcyMzk0NTIsMi45MzgxMTE0MiAwLjE4NzA2NzAzMiwzLjA0MzA1Mzg4IEwyLjQwNTE1MDY4LDMuODgyNjA4MjIgQzIuNDA1MTUwNjgsMy45NjEzMTUwNyAyLjM3ODQyNTU3LDQuMDQwMDIxOTIgMi4zNzg0MjU1Nyw0LjExODcyODc3IEMyLjM3ODQyNTU3LDQuOTg0NTExNDIgMy4wOTk5NjcxMiw1LjY5Mjg4MDM3IDMuOTgxODU5MzYsNS42OTI4ODAzNyBDNC44NjM3NDQyOSw1LjY5Mjg4MDM3IDUuNTg1Mjg1ODQsNC45ODQ1MTE0MiA1LjU4NTI4NTg0LDQuMTE4NzI4NzcgQzUuNTg1Mjg1ODQsMy4yNTI5NDYxMiA0Ljg2Mzc0NDI5LDIuNTQ0NTc3MTcgMy45ODE4NTkzNiwyLjU0NDU3NzE3IEMzLjQ0NzM3OSwyLjU0NDU3NzE3IDIuOTY2MzQ4ODYsMi44MDY5MzMzMyAyLjY3MjM4NzIxLDMuMjAwNDc0ODkgTDAuODU1MTY3MTIzLDIuNTE4MzQxNTUgQzEuMjgyNzQ3MDMsMS43MzEyNjU3NSAxLjk3NzU2MzQ3LDEuMTI3ODM5MjcgMi44MzI3MzA1OSwwLjgzOTI0MDE4MyBDMy43MTQ2MTU1MywwLjU1MDY0NzY3MSA0LjY3NjY3NTgsMC42MDMxMTk2MzUgNS41MzE4NDI5MiwwLjk5NjY2MTE4NyBDNy4yNjg4OTQ5OCwxLjgzNjIwODIyIDcuOTkwNDI5MjIsMy45MDg4NDM4NCA3LjEzNTI2OTQxLDUuNjE0MTczNTIgQzYuNzM0NDE0NjEsNi40NTM3MjA1NSA2LjAxMjg3MzA2LDcuMDU3MTQ3MDMgNS4xMDQyNTU3MSw3LjM3MTk4MTc0IEM0LjIyMjM3MDc4LDcuNjYwNTY2MjEgMy4yNjAzMTA1LDcuNjA4MTA5NTkgMi40MDUxNTA2OCw3LjIxNDU2MDczIEMxLjg5NzM5NTQzLDYuOTc4NDQwMTggMS40Njk4MTU1Myw2LjYxMTEzNDI1IDEuMTIyNDAzNjUsNi4xNjUxMjg3NyBDMS4xNDkxMjg3Nyw2LjA4NjQyMTkyIDEuMTc1ODQ2NTgsNi4wMDc3MTUwNyAxLjE3NTg0NjU4LDUuOTI5MDAwOTEgQzEuMjI5Mjk2OCw1LjYxNDE3MzUyIDAuOTM1MzM1MTYsNS4zMjU1ODE3NCAwLjYxNDY0ODQwMiw1LjMyNTU4MTc0IFoiIGlkPSJwYXRoMV9maWxsIj48L3BhdGg+ICAgICAgICAgICAgICAgIDwvZz4gICAgICAgICAgICA8L2c+ICAgICAgICAgICAgPGcgaWQ9IlZlY3RvciIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMS43NTM0MjUsIDEuNDYxMTg3KSI+ICAgICAgICAgICAgICAgIDxnIGlkPSJwYXRoMl9maWxsLWxpbmsiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAuMDM5NDUyLCAwLjAzMDM5MykiPiAgICAgICAgICAgICAgICAgICAgPHBhdGggZD0iTTExLjYxMzUxNiwzLjA0MzM2MDczIEMxMS42MTM1MTYsMi43MDIyOTc3MiAxMS4zNDYyNjQ4LDIuNDM5OTM0MjUgMTAuOTk4ODY3NiwyLjQzOTkzNDI1IEMxMC42NTEzOTczLDIuNDM5OTM0MjUgMTAuMzg0MTQ2MSwyLjcwMjI5NzcyIDEwLjM4NDE0NjEsMy4wNDMzNjA3MyBDMTAuMzg0MTQ2MSwzLjM1ODE5NTQzIDEwLjY1MTM5NzMsMy42MjA1NTE2IDEwLjk3MjEyNzksMy42NDY3ODcyMSBDMTEuODgwNjk0MSw1LjE5NDcwMzIgMTEuOTYwOTEzMiw3LjEwOTkyNTExIDExLjE1OTE1OTgsOC43MzY1ODQ0NyBDMTAuNDkxMTA1LDEwLjA0ODM2NTMgOS4zNjg2OTQwNiwxMS4wMTkxMDUgNy45NzkwMzE5NiwxMS40NjUwNTk0IEM2LjU4OTM5MTc4LDExLjkzNzMxNTEgNS4wOTI4NTg0NSwxMS44MzI0MDE4IDMuNzgzMzg2MywxMS4yMDI3MDMyIEMxLjA1NzU0ODg2LDkuODkwOTIyMzcgLTAuMDY0ODUwOTk1NCw2LjYzNzY3NjcxIDEuMjcxMzQyNDcsMy45NjE2MjE5MiBDMi4wNzMwNTkzNiwyLjM2MTIyNzQgMy42NDk3NjgwNCwxLjI1OTMyNDIgNS40MTM1NDUyMSwwLjk5Njk2MDczMSBDNS41MjA0MzgzNiwxLjEyODE0NjEyIDUuNjgwNzgxNzQsMS4yMDY4NTI5NyA1Ljg2Nzg1MDIzLDEuMjA2ODUyOTcgQzYuMjE1MjYyMSwxLjIwNjg1Mjk3IDYuNDgyNDk4NjMsMC45NDQ0ODk0OTggNi40ODI0OTg2MywwLjYwMzQyNTAyMyBDNi40ODI0OTg2MywwLjI2MjM1OTA4NyA2LjIxNTI2MjEsLTUuMDA0MDk4NjNlLTA4IDUuODY3ODUwMjMsLTUuMDA0MDk4NjNlLTA4IEM1LjYyNzMzMTUxLC01LjAwNDA5ODYzZS0wOCA1LjQ0MDI3MDMyLDAuMTMxMTc5NTQzIDUuMzMzMzY5ODYsMC4zMTQ4MzAzMiBDMy4zMjkwODEyOCwwLjYwMzQyNTAyMyAxLjUzODU3OSwxLjg2Mjc1MDY4IDAuNjI5OTY5NjgsMy42NzMwMjI4MyBDLTAuMDkxNTc0Nzk0NSw1LjE0MjIzMTk2IC0wLjE5ODQ3MDEzNyw2Ljc5NTA5MDQxIDAuMzM2MDA3MzA2LDguMzQzMDEzNyBDMC44NzA0ODc2NzEsOS44OTA5MjIzNyAxLjk5Mjg4NDAyLDExLjEyNDAxODMgMy40NjI2OTk1NCwxMS44NTg2MzAxIEM0LjM0NDU5MTc4LDEyLjI3ODQyOTIgNS4yNzk5MjY5NCwxMi40ODgyNTU3IDYuMTg4NTM2OTksMTIuNDg4MjU1NyBDOC40ODY3OTQ1MiwxMi40ODgyNTU3IDEwLjcwNDg3NjcsMTEuMjI4OTMxNSAxMS43NzM4MDgyLDkuMDc3NjI1NTcgQzEyLjY4MjQ0NzUsNy4yNDExMDMyIDEyLjYwMjIyODMsNS4wNjM1MjUxMSAxMS41NjAwMzY1LDMuMzA1NzI0MiBDMTEuNTg2Nzc2MywzLjIyNzAxMDA1IDExLjYxMzUxNiwzLjE0ODMwMzIgMTEuNjEzNTE2LDMuMDQzMzYwNzMgWiIgaWQ9InBhdGgyX2ZpbGwiPjwvcGF0aD4gICAgICAgICAgICAgICAgPC9nPiAgICAgICAgICAgIDwvZz4gICAgICAgIDwvZz4gICAgPC9nPjwvc3ZnPg=='
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
	add_submenu_page( 'distributor', esc_html__( 'External Connections', 'distributor' ), esc_html__( 'External Connections', 'distributor' ), apply_filters( 'dt_external_capabilities', 'manage_options' ), 'distributor' );
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
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__( 'Distributor', 'distributor' ),
	);

	$args = array(
		'labels'               => $labels,
		'public'               => true,
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
 * @param  array $messages
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
		5  => isset( $_GET['revision'] ) ? sprintf( __( ' External connection restored to revision from %s', 'distributor' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => esc_html__( 'External connection created.', 'distributor' ),
		7  => esc_html__( 'External connection saved.', 'distributor' ),
		8  => esc_html__( 'External connection submitted.', 'distributor' ),
		9  => sprintf(
			__( 'External connection scheduled for: <strong>%1$s</strong>.', 'distributor' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) )
		),
		10 => esc_html__( 'External connection draft updated.', 'distributor' ),
	);

	return $messages;
}

