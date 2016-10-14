<?php

namespace Syndicate\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
add_action( 'plugins_loaded', function() {
	add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 10, 1 );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\admin_enqueue_scripts' );
	add_action( 'admin_init', __NAMESPACE__  . '\unlink' );
} );

/**
 * Unlink post
 *
 * @since  0.8
 */
function unlink() {
	if ( empty( $_GET['action'] ) || 'unlink' !== $_GET['action'] || empty( $_GET['post'] ) ) {
		return;
	}

	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'unlink-post_' . $_GET['post'] ) ) {
		return;
	}

	update_post_meta( $_GET['post'], 'sy_unlinked', true );

	wp_redirect( admin_url( 'post.php?action=edit&post=' . $_GET['post'] ) );
	exit;
}

/**
 * Show syndicated post message
 *
 * @param  WP_Post $post
 * @since  0.8
 */
function syndicated_message( $post ) {

	$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

	if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'sy_unlinked', true );

	if ( $unlinked ) {
		return;
	}

	switch_to_blog( $original_blog_id );
	$post_url = get_permalink( $original_post_id );
	$blog_name = get_bloginfo( 'name' );
	restore_current_blog();

	if ( empty( $blog_name ) ) {
		$blog_name = sprintf( esc_html__( 'Blog #%d', 'syndicate' ), $original_blog_id );
	}

	$post_type_object = get_post_type_object( $post->post_type );

	?>
	<div class="updated syndicate-status">
		<p>
			<?php echo sprintf( __( 'Reposted from <a href="%s">%s</a>.', 'syndicate' ), esc_url( $post_url ), esc_html( $blog_name ) ); ?> 
			<span><?php echo sprintf( __( 'The original post will update this version unless you <a href="%s">unlink from the original.</a>', 'syndicate' ), wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ) ); ?></span>
		</p>
	</div>
	<?php
}

/**
 * Enqueue admin scripts for external connection editor
 *
 * @param  string $hook
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( 'post-new.php' !== $hook && 'post.php' !== $hook ) {
		return;
	}

	global $post;

	$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

	if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'sy_unlinked', true );

	if ( ! $unlinked ) {
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			$css_path = '/assets/css/admin-syndicated-post.css';
		} else {
			$css_path = '/assets/css/admin-syndicated-post.min.css';
		}

		wp_enqueue_style( 'sy-admin-syndicated-post', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );

		wp_dequeue_script( 'autosave' );
	}
}
