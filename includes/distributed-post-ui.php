<?php

namespace Distributor\DistributedPostUI;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_post_scripts' );
			add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\distributed_to' );
			add_action( 'load-post.php', __NAMESPACE__ . '\add_help_tab' );
			add_action( 'load-post-new.php', __NAMESPACE__ . '\add_help_tab');
		}
	);
}

/**
 * Register distributor help tab
 */
function add_help_tab() {
	if ( empty( $_GET['post'] ) ) {
		return;
	}

	$connection_map = get_post_meta( $_GET['post'], 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	$screen = get_current_screen();

	// Add my_help_tab if current screen is My Admin Page
	$screen->add_help_tab( array(
		'id'	  => 'distributer',
		'title'	  => esc_html__( 'Distributor', 'distributor' ),
		'content' => '<p>' . esc_html__( 'The number of connections this post has been distributed to is shown in the publish meta box. If this post is deleted, it could have ramifications across all those posts.', 'distributor' ) . '</p>',
	) );
}

/**
 * Output distributed to number
 *
 * @param  WP_Post $post
 * @since  0.8
 */
function distributed_to( $post ) {
	$connection_map = get_post_meta( $post->ID, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	$total_connections = count( $connection_map['internal'] ) + count( $connection_map['external'] );

	?>

	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="distributed-to"><?php printf( wp_kses_post( _n( 'Distributed to <strong>%d</strong> connection', 'Distributed to <strong>%d</strong> connections', (int) $total_connections, 'distributor' ) ), (int) $total_connections ); ?></span>
	</div>

	<?php
}


/**
 * Enqueue admin scripts/styles for post.php
 *
 * @param  string $hook
 * @since  0.8
 */
function enqueue_post_scripts( $hook ) {
	if ( 'post-new.php' !== $hook && 'post.php' !== $hook ) {
		return;
	}

	global $post;

	$connection_map = get_post_meta( $post->ID, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$css_path = '/assets/css/admin-distributed-post.css';
	} else {
		$css_path = '/assets/css/admin-distributed-post.min.css';
	}

	wp_enqueue_style( 'dt-admin-distributed-post', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
}
