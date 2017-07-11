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

		if ( ! empty( $original_blog_id ) || ! empty( $original_source_id ) ) {
			$unlinked = (bool) get_post_meta( $post_id, 'dt_unlinked', true );

			if ( $unlinked ) {
				echo esc_html__( 'Unlinked', 'distributor' );
			} else {
				echo esc_html__( 'Linked', 'distributor' );
			}
		} else {
			echo '—';
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
	$syndicate_time = get_post_meta( $_GET['post'], 'dt_syndicate_time', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return $classes;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( $unlinked ) {
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

	if ( ! empty( $dt_original_post ) ) {
		$syndicate_time = $dt_original_post->syndicate_time;
	} else {
		$syndicate_time = get_post_meta( $post->ID, 'dt_syndicate_time', true );
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
 * Resync an already pushed post
 *
 * @param  int $post_id
 * @since  0.8
 */
function resync( $post_id ) {
	$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post_id, 'dt_original_source_id', true );

	if ( ! empty( $original_blog_id ) ) {
		$current_blog = get_current_blog_id();

		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $current_blog ) );
		$connection->pull( [
			[
				'remote_post_id' => $original_post_id,
				'post_id'        => $post_id,
			],
		] );
	} else {
		$connection = \Distributor\ExternalConnection::instantiate( $original_source_id );
		$connection->pull( [
			[
				'remote_post_id' => $original_post_id,
				'post_id'        => $post_id,
			],
		] );
	}
}

/**
 * Bring taxonomy terms over to cloned post. We only bring terms from taxonomies that exist on the
 * syndicated post site.
 *
 * @param  int $post_id
 * @since  0.8
 */
function clone_taxonomy_terms( $post_id ) {
	$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );

	$post = get_post( $post_id );

	// Get taxonomy/terms of original post
	switch_to_blog( $original_blog_id );

	$original_post = get_post( $original_post_id );

	$original_taxonomy_terms = [];
	$original_taxonomies = get_object_taxonomies( $original_post );

	foreach ( $original_taxonomies as $taxonomy ) {
		$original_taxonomy_terms[ $taxonomy ] = wp_get_object_terms( $original_post_id, $taxonomy );
	}

	restore_current_blog();

	// Now let's add the taxonomy/terms to syndicated post
	\Distributor\Utils\set_taxonomy_terms( $post_id, $original_taxonomy_terms );
}

/**
 * Bring media files over to syndicated post. We sync all the images and update the featured image
 * to use the new one. We leave image urls in the post content intact as we can't guarentee the post
 * image size in each inserted image exists.
 *
 * @param  int $post_id
 * @since  0.8
 */
function sync_media( $post_id ) {
	$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );

	// Get media of original post
	switch_to_blog( $original_blog_id );

	$raw_media = get_attached_media( get_allowed_mime_types(), $post_id );
	$media_array = array();

	$featured_image_id = get_post_thumbnail_id( $post_id );
	$found_featured = false;

	foreach ( $raw_media as $media_post ) {
		$media_item = \Distributor\Utils\format_media_post( $media_post );

		if ( $media_item['featured'] ) {
			$found_featured = true;
		}

		$media_array[] = $media_item;
	}

	if ( ! empty( $featured_image_id ) && ! $found_featured ) {
		$media_array[] = \Distributor\Utils\format_media_post( get_post( $featured_image_id ) );
	}

	restore_current_blog();

	\Distributor\Utils\set_media( $post_id, $media_array );
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

	$original_source_id = get_post_meta( $_GET['post'], 'dt_original_source_id', true );

	update_post_meta( $_GET['post'], 'dt_unlinked', true );

	/**
	 * For external connections we don't need to do this because of subscriptions
	 */
	if ( empty( $original_source_id ) ) {
		resync( $_GET['post'] );

		sync_media( $_GET['post'] );

		clone_taxonomy_terms( $_GET['post'] );
	}

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
		resync( $_GET['post'] );

		sync_media( $_GET['post'] );

		clone_taxonomy_terms( $_GET['post'] );
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

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	$post_type_object = get_post_type_object( $post->post_type );

	if ( ! empty( $original_blog_id ) ) {
		switch_to_blog( $original_blog_id );
		$post_url = get_permalink( $original_post_id );
		$original_location_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( empty( $blog_name ) ) {
			$blog_name = sprintf( esc_html__( 'Blog #%d', 'distributor' ), $original_blog_id );
		}
	} else {
		$post_url = get_post_meta( $post->ID, 'dt_original_post_url', true );
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
