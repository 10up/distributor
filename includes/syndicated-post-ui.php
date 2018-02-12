<?php

namespace Distributor\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 9, 1 );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_post_scripts' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_edit_scripts' );
			add_action( 'admin_init', __NAMESPACE__ . '\unlink' );
			add_action( 'admin_init', __NAMESPACE__ . '\link' );
			add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\syndication_date' );
			add_filter( 'admin_body_class', __NAMESPACE__ . '\add_linked_class' );
			add_filter( 'post_row_actions', __NAMESPACE__ . '\remove_quick_edit', 10, 2 );

			$post_types = \Distributor\Utils\distributable_post_types();

			foreach ( $post_types as $post_type ) {
				add_action( 'manage_' . $post_type . '_posts_custom_column', __NAMESPACE__ . '\output_distributor_column', 10, 2 );
				add_filter( 'manage_' . $post_type . '_posts_columns', __NAMESPACE__ . '\add_distributor_column' );
			}
		}
	);
}

/**
 * Add Distributor column to post table to indicate a posts link status
 *
 * @since  1.0
 * @param  array $columns
 * @return array
 */
function add_distributor_column( $columns ) {
	unset( $columns['date'] );
	$columns['distributor'] = '<img src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '" title="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '">';

	$columns['date'] = esc_html__( 'Date', 'distributor' );

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
	if ( 'distributor' === $column_name ) {
		$original_blog_id   = get_post_meta( $post_id, 'dt_original_blog_id', true );
		$original_source_id = get_post_meta( $post_id, 'dt_original_source_id', true );
		$original_deleted   = (bool) get_post_meta( $post_id, 'dt_original_post_deleted', true );

		if ( ( empty( $original_blog_id ) && empty( $original_source_id ) ) || $original_deleted ) {
			echo '—';
		} else {
			$unlinked = (bool) get_post_meta( $post_id, 'dt_unlinked', true );

			if ( $unlinked ) {
				echo '<img class="dt-unlinked" src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '" title="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '">';
			} else {
				echo '<img src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '" title="' . esc_html__( 'See which posts have been distributed', 'distributor' ) . '">';
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
	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
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
	global $post, $pagenow;

	if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
		return;
	}

	if ( empty( $_GET['post'] ) ) {
		return $classes;
	}

	$original_blog_id   = get_post_meta( $_GET['post'], 'dt_original_blog_id', true );
	$original_source_id = get_post_meta( $_GET['post'], 'dt_original_source_id', true );
	$original_post_id   = get_post_meta( $_GET['post'], 'dt_original_post_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return $classes;
	}

	$unlinked         = (bool) get_post_meta( $_GET['post'], 'dt_unlinked', true );
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
	$syndicate_time = get_post_meta( $post->ID, 'dt_syndicate_time', true );

	if ( empty( $syndicate_time ) ) {
		return;
	}

	?>

	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="syndicate-time"><?php esc_html_e( 'Distributed on: ', 'distributor' ); ?><strong><?php echo esc_html( date( 'M j, Y @ h:i', $syndicate_time ) ); ?></strong></span>
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

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $_GET['post'] ) );
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
		$original_post_id = get_post_meta( $_GET['post'], 'dt_original_post_id', true );
		$original_blog_id = get_post_meta( $_GET['post'], 'dt_original_blog_id', true );

		$blog_id = get_current_blog_id();

		switch_to_blog( $original_blog_id );

		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $blog_id ) );

		$connection->push( $original_post_id, array( 'remote_post_id' => $_GET['post'] ) );

		restore_current_blog();
	} else {
		$update = get_post_meta( $_GET['post'], 'dt_subscription_update', true );

		if ( ! empty( $update ) ) {
			wp_update_post(
				[
					'ID'           => $_GET['post'],
					'post_title'   => $update['post_title'],
					'post_content' => $update['post_content'],
					'post_excerpt' => $update['post_excerpt'],
				]
			);

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

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $_GET['post'] ) );
	exit;
}

/**
 * Show syndicated post message
 *
 * @param  WP_Post $post
 * @since  0.8
 */
function syndicated_message( $post ) {

	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	$post_type_object = get_post_type_object( $post->post_type );

	$post_url           = get_post_meta( $post->ID, 'dt_original_post_url', true );
	$original_site_name = get_post_meta( $post->ID, 'dt_original_site_name', true );

	if ( ! empty( $original_blog_id ) ) {
		switch_to_blog( $original_blog_id );
		$original_location_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( empty( $original_location_name ) ) {
			$original_location_name = sprintf( esc_html__( 'Blog #%d', 'distributor' ), $original_blog_id );
		}
	} else {
		$original_location_name = $original_site_name;
	}

	$post_type_singular = $post_type_object->labels->singular_name;

	?>
	<div class="updated syndicate-status">
		<?php if ( $original_deleted ) : ?>
			<p>
				<?php echo wp_kses_post( sprintf( __( 'This %s was distributed from <a href="%2$s">%3$s</a>. However, the original has been deleted.', 'distributor' ), esc_html( strtolower( $post_type_singular ) ), esc_url( $post_url ), esc_html( $original_location_name ) ) ); ?>
			</p>
		<?php elseif ( ! $unlinked ) : ?>
			<p>
				<?php echo wp_kses_post( sprintf( __( 'Distributed from <a href="%1$s">%2$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ) ); ?>
				<span><?php echo wp_kses_post( sprintf( __( 'The original %1$s will update this version unless you <a href="%2$s">unlink from the original.</a>', 'distributor' ), esc_html( strtolower( $post_type_singular ) ), wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ) ) ); ?></span>
			</p>
		<?php else : ?>
			<p>
				<?php echo wp_kses_post( sprintf( __( 'Originally distributed from <a href="%1$s">%1$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ) ); ?>
				<span><?php echo wp_kses_post( sprintf( __( "This %1\$s has been forked from it's original. However, you can always <a href='%2\$s'>restore it.</a>", 'distributor' ), esc_html( strtolower( $post_type_singular ) ), wp_nonce_url( add_query_arg( 'action', 'link', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "link-post_{$post->ID}" ) ) ); ?></span>
			</p>
		<?php endif; ?>
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

	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	wp_enqueue_style( 'dt-admin-syndicated-post', plugins_url( '/dist/css/admin-syndicated-post.min.css', __DIR__ ), array(), DT_VERSION );

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( ! $unlinked ) {
		wp_dequeue_script( 'autosave' );
	}
}

/**
 * Enqueue admin scripts/styles for edit.php
 *
 * @param  string $hook
 * @since  0.8
 */
function enqueue_edit_scripts( $hook ) {
	if ( 'edit.php' !== $hook ) {
		return;
	}

	if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
		$css_path = '/assets/css/admin-edit-table.css';
	} else {
		$css_path = '/assets/css/admin-edit-table.min.css';
	}

	wp_enqueue_style( 'dt-admin-syndicated-post', plugins_url( $css_path, __DIR__ ), array(), DT_VERSION );
}
