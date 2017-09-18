<?php

namespace Distributor\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action( 'plugins_loaded', function() {
		add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 9, 1 );
		add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
		add_action( 'admin_init', __NAMESPACE__ . '\unlink' );
		add_action( 'admin_init', __NAMESPACE__ . '\link' );
		add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\syndication_date' );
		add_filter( 'admin_body_class', __NAMESPACE__ . '\add_linked_class' );
		add_filter( 'post_row_actions', __NAMESPACE__ . '\remove_quick_edit', 10, 2 );
		add_action( 'manage_posts_custom_column' , __NAMESPACE__ . '\output_distributor_column', 10, 2 );
		add_filter( 'manage_posts_columns', __NAMESPACE__ . '\add_distributor_column' );
	} );
}

/**
 * Add Distributor column to post table to indicate a posts link status
 *
 * @since  1.0
 * @param  array $columns
 * @return array
 */
function add_distributor_column( $columns ) {
	$post_type = get_post_type();

	if ( ! in_array( $post_type, \Distributor\Utils\distributable_post_types() ) ) {
		return $columns;
	}

	unset( $columns['date'] );
	$columns['distributor'] = esc_html__( 'Distributor', 'distributor' );

	$columns['date'] = __( 'Date' );

	return $columns;
}

/**
 * Output Distributor post table column. Tell users if a post is linked.
 *
 * @param  string $column_name
 * @param  int    $post_id
 * @since  1.0
 */
function output_distributor_column( $column_name, $post_id ) {
	$post_type = get_post_type( $post_id );

	if ( ! in_array( $post_type, \Distributor\Utils\distributable_post_types() ) ) {
		return;
	}

	if ( 'distributor' === $column_name ) {
		$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
		$original_source_id = get_post_meta( $post_id, 'dt_original_source_id', true );
		$original_deleted = (bool) get_post_meta( $post_id, 'dt_original_post_deleted', true );

		if ( ( empty( $original_blog_id ) && empty( $original_source_id ) ) || $original_deleted ) {
			echo '—';
		} else {
			$unlinked = (bool) get_post_meta( $post_id, 'dt_unlinked', true );

			if ( $unlinked ) {
				echo '<span class="dashicons dashicons-editor-unlink"></span>';
			} else {
				echo '<span class="dashicons dashicons-admin-links"></span>';
			}
		}
	}
}

/**
 * Remove quick edit for linked posts
 *
 * @param  array   $actions
 * @param  WP_Post $post
 * @since  0.8
 * @return array
 */
function remove_quick_edit( $actions, $post ) {
	$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return $actions;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( $unlinked ) {
		return $actions;
	}

	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	if ( $original_deleted ) {
		return $actions;
	}

	unset( $actions['inline hide-if-no-js'] );

	return $actions;
}

/**
 * Add linked class to body
 *
 * @param  string $classes
 * @since  0.8
 * @return string
 */
function add_linked_class( $classes ) {
	global $post, $pagenow, $dt_original_post;

	if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
		return;
	}

	if ( empty( $_GET['post'] ) ) {
		return $classes;
	}

	$original_blog_id = get_post_meta( $_GET['post'], 'dt_original_blog_id', true );
	$original_source_id = get_post_meta( $_GET['post'], 'dt_original_source_id', true );
	$original_post_id = get_post_meta( $_GET['post'], 'dt_original_post_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return $classes;
	}

	$unlinked = (bool) get_post_meta( $_GET['post'], 'dt_unlinked', true );
	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	if ( $unlinked || $original_deleted ) {
		return $classes;
	}

	return $classes . ' dt-linked-post';
}

/**
 * Output syndicated on date
 *
 * @param  WP_Post $post
 * @since  0.8
 */
function syndication_date( $post ) {
	global $dt_original_post;

	$syndicate_time = get_post_meta( $post->ID, 'dt_syndicate_time', true );

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

	update_post_meta( $_GET['post'], 'dt_unlinked', true );

	/**
	 * Todo: Do we delete subscriptions for external posts?
	 */

	do_action( 'dt_unlink_post' );

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

	update_post_meta( $_GET['post'], 'dt_unlinked', false );

	$original_source_id = get_post_meta( $_GET['post'], 'dt_original_source_id', true );

	/**
	 * For external connections we use a saved update since we might not have access to sync from original
	 */
	if ( empty( $original_source_id ) ) {

	} else {
		$update = get_post_meta( $_GET['post'], 'dt_subscription_update', true );

		if ( ! empty( $update ) ) {
			wp_update_post( [
				'ID'           => $_GET['post'],
				'post_title'   => $update['post_title'],
				'post_content' => $update['post_content'],
				'post_excerpt' => $update['post_excerpt'],
			] );

			if ( null !== $update['meta'] ) {
				\Distributor\Utils\set_meta( $_GET['post'], $update['meta'] );
			}

			if ( null !== $update['terms'] ) {
				\Distributor\Utils\set_taxonomy_terms( $_GET['post'], $update['terms'] );
			}

			if ( null !== $update['media'] ) {
				\Distributor\Utils\set_media( $_GET['post'], $update['media'] );
			}
		}
	}

	do_action( 'dt_link_post' );

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

	$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	if ( $original_deleted ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	$post_type_object = get_post_type_object( $post->post_type );

	$post_url = get_post_meta( $post->ID, 'dt_original_post_url', true );

	if ( ! empty( $original_blog_id ) ) {
		switch_to_blog( $original_blog_id );
		$original_location_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( empty( $original_location_name  ) ) {
			$original_location_name  = sprintf( esc_html__( 'Blog #%d', 'distributor' ), $original_blog_id );
		}
	} else {
		$original_location_name = get_the_title( $original_source_id );
	}

	?>
	<div class="updated syndicate-status">
		<?php if ( ! $unlinked ) : ?>
			<p>
				<?php echo sprintf( __( 'Syndicated from <a href="%1$s">%1$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ); ?>
				<span><?php echo sprintf( __( 'The original post will update this version unless you <a href="%s">unlink from the original.</a>', 'distributor' ), wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ) ); ?></span>
			</p>
		<?php else : ?>
			<p>
				<?php echo sprintf( __( 'Originally syndicated from <a href="%1$s">%1$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ); ?>
				<span><?php echo sprintf( __( "This post has been forked from it's original. However, you can always <a href='%s'>restore it.</a>", 'distributor' ), wp_nonce_url( add_query_arg( 'action', 'link', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "link-post_{$post->ID}" ) ); ?></span>
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

	$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	if ( $original_deleted ) {
		return;
	}

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$css_path = '/assets/css/admin-syndicated-post.css';
	} else {
		$css_path = '/assets/css/admin-syndicated-post.min.css';
	}

	wp_enqueue_style( 'dt-admin-syndicated-post', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( ! $unlinked ) {
		wp_dequeue_script( 'autosave' );
	}
}
