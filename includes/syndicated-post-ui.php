<?php

namespace Syndicate\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
add_action( 'plugins_loaded', function() {
	add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 9, 1 );
	add_action( 'admin_enqueue_scripts', __NAMESPACE__  . '\admin_enqueue_scripts' );
	add_action( 'admin_init', __NAMESPACE__  . '\unlink' );
	add_action( 'admin_init', __NAMESPACE__  . '\link' );
	add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\syndication_date' );
	add_filter( 'admin_body_class', __NAMESPACE__ . '\add_linked_class' );
} );

/**
 * Add linked class to body
 * 
 * @param  string $classes
 * @since  0.8
 * @return string
 */
function add_linked_class( $classes ) {
	global $post, $pagenow, $sy_original_post;

	if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
    	return;
    }

    if ( empty( $_GET['post'] ) ) {
    	return $classes;
    }

    $original_blog_id = get_post_meta( $_GET['post'], 'sy_original_blog_id', true );
	$original_post_id = get_post_meta( $_GET['post'], 'sy_original_post_id', true );
	$syndicate_time = get_post_meta( $_GET['post'], 'sy_syndicate_time', true );

	if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
		return $classes;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'sy_unlinked', true );

	if ( $unlinked ) {
		return $classes;
	}

	return $classes . ' sy-linked-post';
}

/**
 * Output syndicated on date
 * 
 * @param  WP_Post $post
 * @since  0.8
 */
function syndication_date( $post ) {
	global $sy_original_post;

	if ( ! empty( $sy_original_post ) ) {
		$syndicate_time = $sy_original_post->syndicate_time;
	} else {
		$syndicate_time = get_post_meta( $post->ID, 'sy_syndicate_time', true );
	}

	if ( empty( $syndicate_time ) ) {
		return;
	}

	?>

	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="syndicate-time"><?php echo sprintf( __( 'Syndicated on: <strong>%s</strong>' ,'syndicate' ), date( 'M j, Y @ h:i', $syndicate_time ) ); ?></span>
	</div>

	<?php
}

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
 * Restore post link
 *
 * @since  0.8
 */
function link() {
	if ( empty( $_GET['action'] ) || 'link' !== $_GET['action'] || empty( $_GET['post'] ) ) {
		return;
	}

	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'link-post_' . $_GET['post'] ) ) {
		return;
	}

	update_post_meta( $_GET['post'], 'sy_unlinked', false );

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
		<?php if ( ! $unlinked ) : ?>
			<p>
				<?php echo sprintf( __( 'Syndicated from <a href="%s">%s</a>.', 'syndicate' ), esc_url( $post_url ), esc_html( $blog_name ) ); ?> 
				<span><?php echo sprintf( __( 'The original post will update this version unless you <a href="%s">unlink from the original.</a>', 'syndicate' ), wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ) ); ?></span>
			</p>
		<?php else : ?>
			<p>
				<?php echo sprintf( __( 'Originally syndicated from <a href="%s">%s</a>.', 'syndicate' ), esc_url( $post_url ), esc_html( $blog_name ) ); ?> 
				<span><?php echo sprintf( __( "This post has been forked from it's original. However, you can always <a href='%s'>restore it.</a>", 'syndicate' ), wp_nonce_url( add_query_arg( 'action', 'link', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "link-post_{$post->ID}" ) ); ?></span>
			</p>
		<?php endif; ?>
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

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$css_path = '/assets/css/admin-syndicated-post.css';
	} else {
		$css_path = '/assets/css/admin-syndicated-post.min.css';
	}

	wp_enqueue_style( 'sy-admin-syndicated-post', plugins_url( $css_path, __DIR__ ), array(), SY_VERSION );

	$unlinked = (bool) get_post_meta( $post->ID, 'sy_unlinked', true );

	if ( ! $unlinked ) {
		wp_dequeue_script( 'autosave' );
	}
}
