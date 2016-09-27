<?php

namespace Syndicate\ExternalConnectionCPT;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', function() {
    add_action( 'init', __NAMESPACE__ . '\setup_cpt' );
    add_filter( 'enter_title_here', __NAMESPACE__ . '\filter_enter_title_here', 10, 2 );
	add_filter( 'post_updated_messages', __NAMESPACE__ . '\filter_post_updated_messages' );
	add_action( 'save_post', __NAMESPACE__  . '\save_post' );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\admin_enqueue_scripts' );
	add_action( 'wp_ajax_sy_verify_external_connection', __NAMESPACE__  . '\ajax_verify_external_connection' );
	add_action( 'admin_footer', __NAMESPACE__  . '\js_templates' );
	add_filter( 'manage_sy_ext_connection_posts_columns', __NAMESPACE__  . '\filter_columns' );
	add_action( 'manage_sy_ext_connection_posts_custom_column', __NAMESPACE__  . '\action_custom_columns', 10, 2 );
	add_action( 'admin_menu', __NAMESPACE__  . '\action_admin_menu' );
} );

/**
 * Add url column to posts table
 * 
 * @param  array $columns
 * @since  1.0
 * @return array
 */
function filter_columns( $columns ) {
	$columns['sy_external_connection_url'] = esc_html__( 'URL', 'syndicate' );
	// Move date column to the back
	unset($columns['date']);
	$columns['date'] = __( 'Date', 'syndicate' );
	return $columns;
}

/**
 * Output url column
 * 
 * @param  string $column
 * @param  int $post_id
 * @since  1.0
 */
function action_custom_columns( $column, $post_id ) {
	if ( 'sy_external_connection_url' == $column ) {
		$url = get_post_meta( $post_id, 'sy_external_connection_url', true );

		if ( ! empty( $url ) ) {
			echo esc_url( $url );
		} else {
			esc_html_e( 'None', 'syndicate' );
		}
	}
}

/**
 * Check push and pull connections via AJAX
 *
 * @since  1.0
 */
function ajax_verify_external_connection() {
	if ( ! check_ajax_referer( 'sy-verify-ext-conn', 'nonce', false ) ) {
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

	// Create an instance of the connection to test connections
	$external_connection_class = \Syndicate\ExternalConnections::factory()->get_registered()[ $_POST['type'] ];

	$auth_handler = new $external_connection_class::$auth_handler_class( $auth );
	$mapping_handler = new $external_connection_class::$mapping_handler_class();

	// Init with placeholders since we haven't created yet
	$external_connection = new $external_connection_class( 'connection-test', $_POST['url'], 0, $auth_handler, $mapping_handler );

	$external_connections = $external_connection->check_connections();

	wp_send_json_success( $external_connections );
	exit;
}

/**
 * Enqueue admin scripts for external connection editor
 * 
 * @param  string $hook
 * @since  1.0
 */
function admin_enqueue_scripts( $hook ) {
    if ( ( 'post.php' === $hook && 'sy_ext_connection' === get_post_type() ) || ( 'post-new.php' === $hook && ! empty( $_GET['post_type'] ) && 'sy_ext_connection' === $_GET['post_type'] ) ) {

	    if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$js_path = '/assets/js/src/admin-external-connection.js';
			$css_path = '/assets/css/admin-external-connection.css';
		} else {
			$js_path = '/assets/js/admin-external-connection.min.js';
			$css_path = '/assets/css/admin-external-connection.min.css';
		}

		wp_enqueue_style( 'sy-admin-external-connection', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );
	    wp_enqueue_script( 'sy-admin-external-connection', plugins_url( $js_path, __DIR__ ), array( 'jquery', 'underscore' ), SY_VERSION, true );

	    wp_localize_script( 'sy-admin-external-connection', 'sy', array(
	    	'nonce' => wp_create_nonce( 'sy-verify-ext-conn' ),
	    	'no_external_connection' => esc_html__( "Can't connect to API.", 'syndicate' ),
	    	'can_post' => esc_html__( 'Can push:', 'syndicate' ),
	    	'can_get' => esc_html__( 'Can pull:', 'syndicate' ),
	    ) );
	}

	if ( ! empty( $_GET['page'] ) && 'syndicate' === $_GET['page'] ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$css_path = '/assets/css/admin-external-connections.css';
		} else {
			$css_path = '/assets/css/admin-external-connections.min.css';
		}

		wp_enqueue_style( 'sy-admin-external-connections', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );
	}
}

/**
 * Change title text box label
 *
 * @param  string $label
 * @param  int $post
 * @since  1.0
 * @return string
 */
function filter_enter_title_here( $label, $post = 0 ) {
	if ( 'sy_ext_connection' !== get_post_type( $post->ID ) ) {
		return $label;
	}

	return esc_html__( 'Enter external connection name', 'syndicate' );
}

/**
 * Save external connection stuff 
 * 
 * @param int $post_id
 * @since 1.0 
 */
function save_post( $post_id ) {
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) || 'revision' == get_post_type( $post_id ) ) {
		return;
	}

	if ( empty( $_POST['sy_external_connection_details'] ) || ! wp_verify_nonce( $_POST['sy_external_connection_details'], 'sy_external_connection_details_action' ) ) {
		return;
	}

	if ( empty( $_POST['sy_external_connection_type'] ) ) {
		delete_post_meta( $post_id, 'sy_external_connection_type' );
	} else {
		update_post_meta( $post_id, 'sy_external_connection_type', sanitize_text_field( $_POST['sy_external_connection_type'] ) );
	}

	if ( empty( $_POST['sy_external_connection_url'] ) ) {
		delete_post_meta( $post_id, 'sy_external_connection_url' );
		delete_post_meta( $post_id, 'sy_external_connections' );
		delete_post_meta( $post_id, 'sy_external_connection_check_time' );
	} else {
		update_post_meta( $post_id, 'sy_external_connection_url', sanitize_text_field( $_POST['sy_external_connection_url'] ) );

		// Create an instance of the connection to test connections
		$external_connection_class = \Syndicate\ExternalConnections::factory()->get_registered()[ $_POST['sy_external_connection_type'] ];

		$auth_handler = new $external_connection_class::$auth_handler_class( $_POST['sy_external_connection_auth'] );
		$mapping_handler = new $external_connection_class::$mapping_handler_class();

		$external_connection = new $external_connection_class( $_POST['sy_external_connection_url'], $auth_handler, $mapping_handler );

		$external_connections = $external_connection->check_connections();

		update_post_meta( $post_id, 'sy_external_connections', $external_connections );
		update_post_meta( $post_id, 'sy_external_connection_check_time', time() );
	}

	if ( ! empty( $_POST['sy_external_connection_auth'] ) ) {
		$auth_creds = \Syndicate\ExternalConnections::factory()->get_registered()[ $_POST['sy_external_connection_type'] ]::$auth_handler_class::prepare_credentials( $_POST['sy_external_connection_auth'] );
		
		\Syndicate\ExternalConnections::factory()->get_registered()[ $_POST['sy_external_connection_type'] ]::$auth_handler_class::store_credentials( $post_id, $auth_creds );
	}
}

/**
 * Register meta boxes
 *
 * @since 1.0
 */
function add_meta_boxes() {
	add_meta_box( 'sy_external_connection_details', esc_html__( 'External Connection Details', 'syndicate' ), __NAMESPACE__ . '\meta_box_external_connection_details', 'sy_ext_connection', 'normal', 'core' );
	add_meta_box( 'sy_external_connection_connection', esc_html__( 'External Connection Status', 'syndicate' ), __NAMESPACE__ . '\meta_box_external_connection', 'sy_ext_connection', 'side', 'core' );
}

/**
 * Output connection meta box to show status of API
 * 
 * @param  WP_Post $post
 * @since  1.0
 */
function meta_box_external_connection( $post ) {
	$external_connections = get_post_meta( $post->ID, 'sy_external_connections', true );
	$check_time = get_post_meta( $post->ID, 'sy_external_connection_check_time', true );

	$lang = array(
		'no_external_connection' => esc_html__( "Can't connect to API.", 'syndicate' ),
    	'can_post'      => esc_html__( 'Can push:', 'syndicate' ),
    	'can_get'      => esc_html__( 'Can pull:', 'syndicate' ),
	);

	if ( ! empty( $connections ) ) : ?>
		<div class="external-connection-verification">
			<ul class="errors">
				<?php foreach ( $external_connections['errors'] as $error ) : ?>
					<li><?php echo esc_html( $lang[$error] ); ?></li>
				<?php endforeach; ?>

				<?php if ( empty( $external_connections['errors'] ) ) : ?>
					<?php if ( empty( $external_connections['can_get'] ) ) : ?>
						<li><?php esc_html_e( 'Can not pull any content types.', 'syndicate' ); ?></li>
					<?php endif; ?>

					<?php if ( empty( $external_connections['can_post'] ) ) : ?>
						<li><?php esc_html_e( 'Can not push any content types.', 'syndicate' ); ?></li>
					<?php endif; ?>
				<?php endif; ?>
			</ul>

			<ul class="successes">
				<?php if ( ! empty( $external_connections['can_get'] ) ) : ?>
					<li><?php echo esc_html( $lang['can_get'] . ' ' . implode( ', ', $external_connections['can_get']) ); ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $external_connections['can_post'] ) ) : ?>
					<li><?php echo esc_html( $lang['can_post'] . ' ' . implode( ', ', $external_connections['can_post']) ); ?></li>
				<?php endif; ?>
			</ul>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No external connection has been checked.', 'syndicate' ); ?></p>
	<?php
	endif;
}

/**
 * Output connection options meta box
 *
 * @since 1.0
 * @param $post
 */
function meta_box_external_connection_details( $post ) {
	wp_nonce_field( 'sy_external_connection_details_action', 'sy_external_connection_details' );

	$external_connection_type = get_post_meta( $post->ID, 'sy_external_connection_type', true );

	$auth = get_post_meta( $post->ID, 'sy_external_connection_auth', true );
	if ( empty( $auth ) ) {
		$auth = array();
	}

	$external_connection_url = get_post_meta( $post->ID, 'sy_external_connection_url', true );
	if ( empty( $external_connection_url ) ) {
		$external_connection_url = '';
	}
	?>
	<p>
		<label for="sy_external_connection_type"><?php esc_html_e( 'External Connection Type', 'syndicate' ); ?></label><br>
		<select name="sy_external_connection_type" class="external-connection-type-field" id="sy_external_connection_type">
			<?php foreach ( \Syndicate\ExternalConnections::factory()->get_registered() as $slug => $external_connection_class ) : ?>
				<option <?php selected( $slug, $external_connection_type ); ?> value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_attr( $external_connection_class::$label ); ?></option>
			<?php endforeach; ?>
		</select>
		<span class="description"><?php esc_html_e( 'We need to know what type of API we are communicating with.', 'syndicate' ); ?></span>
	</p>

	<?php foreach ( \Syndicate\ExternalConnections::factory()->get_registered() as $external_connection_class ) : if ( ! $external_connection_class::$auth_handler_class::$requires_credentials ) { continue; } ?>
		<div class="auth-credentials <?php echo esc_attr( $external_connection_class::$auth_handler_class::$slug ); ?>">
			<?php $external_connection_class::$auth_handler_class::credentials_form( $auth ); ?>
		</div>
	<?php endforeach; ?>

	<p>
		<label for="sy_external_connection_url"><?php esc_html_e( 'External Connection URL', 'syndicate' ); ?></label><br>
		<span class="external-connection-url-field-wrapper">
			<input value="<?php echo esc_url( $external_connection_url ); ?>" type="text" name="sy_external_connection_url" id="sy_external_connection_url" class="widefat external-connection-url-field">
		</span>
		<span class="description"><?php esc_html_e( 'We will confirm the API endpoint works.', 'syndicate' ); ?></span>
	</p>
	<?php
}

/**
 * Output pull dashboard with custom list table
 *
 * @since 1.0
 */
function dashboard() {
	$_GET['post_type'] = 'sy_ext_connection';

	$post_type_object = get_post_type_object( 'sy_ext_connection' );

	$wp_list_table = new \Syndicate\ExternalConnectionListTable();

	$wp_list_table->prepare_items();

	$pagenum = $wp_list_table->get_pagenum();
	?>

	<div class="wrap">
		<h1><?php esc_html_e( 'External Connections', 'syndicate' ); ?> <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=sy_ext_connection' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'syndicate' ); ?></a></h1>
		<div class="network-connections-notice">
			<?php esc_html_e( 'Network sites are automatically available and do not need to be configured.', 'syndicate' ); ?>
		</div>


		<?php $wp_list_table->views(); ?>

		<form id="posts-filter" method="get">

		<input type="hidden" name="post_status" class="post_status_page" value="<?php echo ! empty($_REQUEST['post_status']) ? esc_attr( $_REQUEST['post_status'] ) : 'all'; ?>">
		<input type="hidden" name="post_type" class="post_type_page" value="sy_ext_connection">

		<?php $wp_list_table->display(); ?>

		</form>
	</div>
	<?php
}

/**
 * Add a screen option for posts per page
 *
 * @since  1.0
 */
function screen_option() {

	$option = 'per_page';
	$args   = [
		'label'   => esc_html__( 'External connections per page: ', 'syndicate' ),
		'default' => 5,
		'option'  => 'connections_per_page'
	];

	add_screen_option( $option, $args );
}

/**
 * Set up admin menu
 *
 * @since 1.0
 */
function action_admin_menu() {
	$hook = add_menu_page(
		'Syndicate',
		'Syndicate',
		'manage_options',
		'syndicate',
		__NAMESPACE__  . '\dashboard'
	);

	add_action( "load-$hook", __NAMESPACE__  . '\screen_option' );

	add_submenu_page( 'syndicate', esc_html__( 'Add External Source', 'syndicate' ), esc_html__( 'Add External Source', 'syndicate' ), 'manage_options', 'temp-slug' );

	global $submenu;

	$submenu['syndicate'][0][0] = esc_html__( 'All External Connections', 'syndicate' );
	$submenu['syndicate'][1][2] = esc_url( admin_url( 'post-new.php?post_type=sy_ext_connection' ) );
}

/**
 * Register connection post type
 *
 * @since 1.0
 */
function setup_cpt() {

	$labels = array(
		'name'               => esc_html__( 'External Connections', 'syndicate' ),
		'singular_name'      => esc_html__( 'External Connection', 'syndicate' ),
		'add_new'            => esc_html__( 'Add New', 'syndicate' ),
		'add_new_item'       => esc_html__( 'Add New External Connection', 'syndicate' ),
		'edit_item'          => esc_html__( 'Edit External Connection', 'syndicate' ),
		'new_item'           => esc_html__( 'New External Connection', 'syndicate' ),
		'all_items'          => esc_html__( 'All External Connections', 'syndicate' ),
		'view_item'          => esc_html__( 'View External Connection', 'syndicate' ),
		'search_items'       => esc_html__( 'Search External Connections', 'syndicate' ),
		'not_found'          => esc_html__( 'No external connections found.', 'syndicate' ),
		'not_found_in_trash' => esc_html__( 'No external connections found in trash.', 'syndicate' ),
		'parent_item_colon'  => '',
		'menu_name'          => esc_html__( 'Syndicate', 'syndicate' ),
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

	register_post_type( 'sy_ext_connection', $args );
}

/**
 * Filter CPT messages
 *
 * @param  array $messages
 * @since  1.0
 * @return array
 */
function filter_post_updated_messages( $messages ) {
	global $post, $post_ID;

	$messages['sy_ext_connection'] = array(
		0 => '',
		1 => sprintf( __( 'External connection updated. <a href="%s">View external connection</a>', 'syndicate' ), esc_url( get_permalink( $post_ID ) ) ),
		2 => __( 'Custom field updated.', 'syndicate' ),
		3 => __( 'Custom field deleted.', 'syndicate' ),
		4 => __( 'External connection updated.', 'syndicate' ),
		5 => isset( $_GET['revision']) ? sprintf( __(' External connection restored to revision from %s', 'syndicate' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __( 'External connection published. <a href="%s">View external connection</a>', 'syndicate' ), esc_url( get_permalink( $post_ID) ) ),
		7 => __( 'External connection saved.', 'syndicate' ),
		8 => sprintf( __( 'External connection submitted. <a target="_blank" href="%s">Preview external connection</a>', 'syndicate' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		9 => sprintf( __( 'External connection scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview external connection</a>', 'syndicate' ),
			date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink( $post_ID ) ) ),
		10 => sprintf( __( 'External connection draft updated. <a target="_blank" href="%s">Preview external connection</a>', 'syndicate' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
	);

	return $messages;
}

/**
 * Output templates for working with external connections
 *
 * @since  1.0
 */
function js_templates() {
	?>
	<script type="text/html" id="sy-external-connection-verification">
		<div class="external-connection-verification">
			<ul class="errors">
				<# _.each(errors, function(error) {  #>
					<li>{{ sy[error] }}</li>
				<# }); #>

				<# if (0 === Object.keys(errors).length) { #>
					<# if (!can_get.length) { #>
						<li><?php esc_html_e( 'Can not pull any content types.', 'syndicate' ); ?></li>
					<# } #>

					<# if (!can_post.length) { #>
						<li><?php esc_html_e( 'Can not push any content types.', 'syndicate' ); ?></li>
					<# } #>
				<# } #>
			</ul>

			<ul class="successes">
				<# if (can_get.length) { #>
					<li>{{ sy.can_get }} {{ can_get.join(', ') }}</li>
				<# } #>

				<# if (can_post.length) { #>
					<li>{{ sy.can_post }} {{ can_post.join(', ') }}</li>
				<# } #>
			</ul>
		</div>
	</script>
	<?php
}


