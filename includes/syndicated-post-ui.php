<?php
/**
 * UI functionality for distributed posts
 *
 * @package  distributor
 */

namespace Distributor\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 9, 1 );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_post_scripts' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_edit_scripts' );
			add_action( 'enqueue_block_editor_assets', __NAMESPACE__ . '\enqueue_gutenberg_edit_scripts' );
			add_action( 'admin_init', __NAMESPACE__ . '\unlink' );
			add_action( 'admin_init', __NAMESPACE__ . '\link' );
			add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\syndication_date' );
			add_filter( 'admin_body_class', __NAMESPACE__ . '\add_linked_class' );
			add_filter( 'post_row_actions', __NAMESPACE__ . '\remove_quick_edit', 10, 2 );

			$post = isset( $_GET['post'] ) ? get_post( (int) $_GET['post'] ) : false; // @codingStandardsIgnoreLine Nonce not required

			if ( $post && ! \Distributor\Utils\is_using_gutenberg( $post ) ) {
				add_action( 'do_meta_boxes', __NAMESPACE__ . '\replace_revisions_meta_box', 10, 3 );
				add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_revisions_meta_box' );
			}

			add_action( 'admin_init', __NAMESPACE__ . '\setup_columns' );
		}
	);
}

/**
 * Setup custom admin columns
 *
 * @since 1.0
 */
function setup_columns() {
	$post_types = \Distributor\Utils\distributable_post_types();

	foreach ( $post_types as $post_type ) {
		add_action( 'manage_' . $post_type . '_posts_custom_column', __NAMESPACE__ . '\output_distributor_column', 10, 2 );
		add_filter( 'manage_' . $post_type . '_posts_columns', __NAMESPACE__ . '\add_distributor_column' );
	}
}

/**
 * Add Distributor column to post table to indicate a posts link status
 *
 * @since  1.0
 * @param  array $columns Array of columns
 * @return array
 */
function add_distributor_column( $columns ) {
	unset( $columns['date'] );
	$columns['distributor'] = '<img src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_attr__( 'Posts distributed from another site.', 'distributor' ) . '" title="' . esc_attr__( 'Posts distributed from another site.', 'distributor' ) . '"> <span class="dt-column-header">Distributor</span>';

	$columns['date'] = esc_html__( 'Date', 'distributor' );

	return $columns;
}

/**
 * Output Distributor post table column. Tell users if a post is linked.
 *
 * @param  string $column_name Column name
 * @param  int    $post_id Post ID
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
			$unlinked         = (bool) get_post_meta( $post_id, 'dt_unlinked', true );
			$post_type_object = get_post_type_object( get_post_type( $post_id ) );
			$post_url         = get_post_meta( $post_id, 'dt_original_post_url', true );

			if ( $unlinked ) {
				echo '<a href="' . esc_url( $post_url ) . '"><img class="dt-unlinked" src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_attr__( 'Unlinked', 'distributor' ) . '" title="' . esc_attr__( 'Unlinked', 'distributor' ) . '"></a>';
			} else {
				echo '<a href="' . esc_url( $post_url ) . '"><img src="' . esc_url( plugins_url( 'assets/img/icon.svg', __DIR__ ) ) . '" alt="' . esc_attr__( 'Linked', 'distributor' ) . '" title="' . esc_attr__( 'Linked', 'distributor' ) . '"></a>';
			}
		}
	}
}

/**
 * Remove quick edit for linked posts
 *
 * @param  array    $actions Array of current actions
 * @param  \WP_Post $post Post object
 *
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
 * @param  string $classes CSS classes string
 * @since  0.8
 * @return string
 */
function add_linked_class( $classes ) {
	global $post, $pagenow;

	if (
		'post.php' !== $pagenow && 'post-new.php' !== $pagenow
		|| empty( $_GET['post'] ) // @codingStandardsIgnoreLine No nonce needed.
	) {
		return $classes;
	}

	$current_post_id    = intval( $_GET['post'] ); // @codingStandardsIgnoreLine No nonce needed.
	$original_blog_id   = get_post_meta( $current_post_id, 'dt_original_blog_id', true );
	$original_source_id = get_post_meta( $current_post_id, 'dt_original_source_id', true );
	$original_post_id   = get_post_meta( $current_post_id, 'dt_original_post_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return $classes;
	}

	$unlinked         = (bool) get_post_meta( $current_post_id, 'dt_unlinked', true );
	$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

	if ( $unlinked || $original_deleted ) {
		return $classes;
	}

	return $classes . ' dt-linked-post';
}

/**
 * Output syndicated on date
 *
 * @param  \WP_Post $post Post object
 * @since  0.8
 */
function syndication_date( $post ) {
	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( $unlinked ) {
		return;
	}

	$syndicate_time = get_post_meta( $post->ID, 'dt_syndicate_time', true );
	?>

	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="syndicate-time"><?php esc_html_e( 'Distributed on: ', 'distributor' ); ?><strong><?php echo esc_html( gmdate( 'M j, Y @ h:i', ( $syndicate_time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) ); ?></strong></span>
	</div>

	<?php
}

/**
 * Remove old revisions meta box
 *
 * @since  1.0
 */
function add_revisions_meta_box() {
	global $post;

	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( $unlinked ) {
		return;
	}

	add_meta_box( 'revisionsdiv2', esc_html__( 'Revisions', 'distributor' ), __NAMESPACE__ . '\new_revisions_meta_box', $post->post_type );
}

/**
 * New revisions meta box
 *
 * @param  int $post_id Post ID
 * @since  1.2
 */
function new_revisions_meta_box( $post_id ) {
	$post_type = get_post_type_object( get_post_type( $post_id ) );
	?>
	<p>
		<?php
			/* translators: %s the post type name */
			printf( esc_html__( 'Distributed %s do not support revisions unless unlinked.', 'distributor' ), esc_html( strtolower( $post_type->labels->name ) ) );
		?>
	</p>
	<?php
}

/**
 * Remove old revisions meta box
 *
 * @param  string   $post_type Post type
 * @param  string   $context Meta box context
 * @param  \WP_Post $post Post object
 *
 * @since  1.0
 */
function replace_revisions_meta_box( $post_type, $context, $post ) {
	if ( empty( $post ) ) {
		return;
	}

	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	if ( empty( $original_post_id ) || ( empty( $original_blog_id ) && empty( $original_source_id ) ) ) {
		return;
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( $unlinked ) {
		return;
	}

	remove_meta_box( 'revisionsdiv', $post_type, $context );
}

/**
 * Unlink post
 *
 * @since  0.8
 */
function unlink() {
	if ( empty( $_GET['action'] ) || 'unlink' !== $_GET['action'] || empty( $_GET['post'] ) ) { // @codingStandardsIgnoreLine Nonce isn't needed here.
		return;
	}

	$post_id = intval( $_GET['post'] );

	if ( empty( $_GET['_wpnonce'] ) ||
		! wp_verify_nonce( $_GET['_wpnonce'], 'unlink-post_' . $post_id ) ||
		/**
		 * Filters whether the post can be unlinked.
		 *
		 * @since 1.0
		 * @hook dt_allow_post_unlink
		 *
		 * @param {bool} true       Whether the post is allowed to be unlinked. Default true.
		 * @param {int}  $post_id   The ID of the post attempting to be unlinked.
		 *
		 * @return {bool} Whether the post is allowed to be unlinked.
		 */
		! apply_filters( 'dt_allow_post_unlink', true, $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, 'dt_unlinked', true );

	/**
	 * Todo: Do we delete subscriptions for external posts?
	 */
	/**
	 * Fires when a post is unlinked.
	 *
	 * @since 1.0
	 * @hook dt_unlink_post
	 *
	 * @param {int} $post_id ID of the post being unlinked.
	 */
	do_action( 'dt_unlink_post', $post_id );

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . intval( $_GET['post'] ) ) );
	exit;
}

/**
 * Restore post link
 *
 * @since  0.8
 */
function link() {
	if ( empty( $_GET['action'] ) || 'link' !== $_GET['action'] || empty( $_GET['post'] ) ) { // @codingStandardsIgnoreLine Nonce isn't needed here.
		return;
	}

	$post_id = intval( $_GET['post'] );

	if ( empty( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'link-post_' . $post_id ) ) {
		return;
	}

	update_post_meta( $post_id, 'dt_unlinked', false );

	$original_source_id = get_post_meta( $post_id, 'dt_original_source_id', true );

	/**
	 * For external connections we use a saved update since we might not have access to sync from original
	 */
	if ( empty( $original_source_id ) && is_multisite() ) {
		$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );
		$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );

		$blog_id = get_current_blog_id();

		switch_to_blog( $original_blog_id );

		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $blog_id ) );

		$connection->push( $original_post_id, array( 'remote_post_id' => $post_id ) );

		restore_current_blog();
	} else {
		$update = get_post_meta( $post_id, 'dt_subscription_update', true );

		if ( ! empty( $update ) ) {
			wp_update_post(
				[
					'ID'           => $post_id,
					'post_title'   => $update['post_title'],
					'post_content' => $update['post_content'],
					'post_excerpt' => $update['post_excerpt'],
				]
			);

			if ( null !== $update['meta'] ) {
				\Distributor\Utils\set_meta( $post_id, $update['meta'] );
			}

			if ( null !== $update['terms'] ) {
				\Distributor\Utils\set_taxonomy_terms( $post_id, $update['terms'] );
			}

			if ( null !== $update['media'] ) {
				\Distributor\Utils\set_media( $post_id, $update['media'] );
			}
		}
	}

	/**
	 * Fires when a post is linked.
	 *
	 * @since 1.0
	 * @hook dt_link_post
	 *
	 * @param {int} $post_id ID of the post being linked.
	 */
	do_action( 'dt_link_post', $post_id );

	wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $post_id ) );
	exit;
}

/**
 * Show syndicated post message
 *
 * @param  \WP_Post $post Post object.
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

	if ( ! empty( $original_blog_id ) && is_multisite() ) {
		switch_to_blog( $original_blog_id );
		$original_location_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( empty( $original_location_name ) ) {
			/* translators: %d: the blog ID */
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
				<?php
					/* translators: %1$s: post type name, %2$s: site url, %3$s: site name */
					echo wp_kses_post( sprintf( __( 'This %1$s was distributed from <a href="%2$s">%3$s</a>. However, the original has been deleted.', 'distributor' ), esc_html( strtolower( $post_type_singular ) ), esc_url( $post_url ), esc_html( $original_location_name ) ) );
				?>
			</p>
		<?php elseif ( ! $unlinked ) : ?>
			<p>
				<?php
					/* translators: %1$s: site url, %2$s: site name */
					echo wp_kses_post( sprintf( __( 'Distributed from <a href="%1$s">%2$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ) );
				?>
				<?php
					// Filter documented above.
				if ( apply_filters( 'dt_allow_post_unlink', true, $post->ID ) ) :
					?>
					<?php /* translators: %1$s: post type name, %2$s: unlink url */ ?>
					<span><?php echo wp_kses_post( sprintf( __( 'The original %1$s will update this version unless you <a href="%2$s">unlink from the original.</a>', 'distributor' ), esc_html( strtolower( $post_type_singular ) ), wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ) ) ); ?></span>
				<?php endif; ?>
			</p>
		<?php else : ?>
			<p>
				<?php /* translators: %1$s: site url, %2$s: site name */ ?>
				<?php echo wp_kses_post( sprintf( __( 'Originally distributed from <a href="%1$s">%1$s</a>.', 'distributor' ), esc_url( $post_url ), esc_html( $original_location_name ) ) ); ?>
				<?php /* translators: %1$s: post type name, %2$s: link url */ ?>
				<span><?php echo wp_kses_post( sprintf( __( "This %1\$s has been unlinked from the original. However, you can always <a href='%2\$s'>restore it.</a>", 'distributor' ), esc_html( strtolower( $post_type_singular ) ), wp_nonce_url( add_query_arg( 'action', 'link', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "link-post_{$post->ID}" ) ) ); ?></span>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Enqueue admin scripts/styles for post.php
 *
 * @param  string $hook WP hook.
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

	if ( \Distributor\Utils\is_using_gutenberg( $post ) ) {
		wp_enqueue_style( 'dt-gutenberg-syndicated-post', plugins_url( '/dist/css/gutenberg-syndicated-post.min.css', __DIR__ ), array(), DT_VERSION );
	} else {
		wp_enqueue_style( 'dt-admin-syndicated-post', plugins_url( '/dist/css/admin-syndicated-post.min.css', __DIR__ ), array(), DT_VERSION );
	}

	$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

	if ( ! $unlinked ) {
		wp_dequeue_script( 'autosave' );
	}
}

/**
 * Output gutenberg JS
 *
 * @since 1.2
 */
function enqueue_gutenberg_edit_scripts() {
	global $post;

	if ( empty( $post ) ) {
		return;
	}

	$original_blog_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post->ID, 'dt_original_post_id', true );
	$original_source_id = get_post_meta( $post->ID, 'dt_original_source_id', true );

	$original_deleted   = get_post_meta( $post->ID, 'dt_original_post_deleted', true );
	$unlinked           = get_post_meta( $post->ID, 'dt_unlinked', true );
	$post_type_object   = get_post_type_object( $post->post_type );
	$post_url           = get_post_meta( $post->ID, 'dt_original_post_url', true );
	$original_site_name = get_post_meta( $post->ID, 'dt_original_site_name', true );
	$syndication_time   = get_post_meta( $post->ID, 'dt_syndicate_time', true );
	$connection_map     = get_post_meta( $post->ID, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		$total_connections = 0;
	} else {
		$total_connections = count( $connection_map['internal'] ) + count( $connection_map['external'] );
	}

	if ( ! empty( $original_blog_id ) && is_multisite() ) {
		switch_to_blog( $original_blog_id );
		$original_location_name = get_bloginfo( 'name' );
		restore_current_blog();

		if ( empty( $original_location_name ) ) {
			/* translators: %d: the original blog id */
			$original_location_name = sprintf( esc_html__( 'Blog #%d', 'distributor' ), $original_blog_id );
		}
	} else {
		$original_location_name = $original_site_name;
	}

	$post_type_singular = $post_type_object->labels->singular_name;

	if ( function_exists( 'gutenberg_get_jed_locale_data' ) ) {
		$i18n_locale = gutenberg_get_jed_locale_data( 'distributor' );
	} else {
		$i18n_locale = [
			'' => [
				'domain' => 'distributor',
				'lang'   => get_user_locale(),
			],
		]; // this is a temp hacky substitute for gutenberg_get_jed_locale_data()
	}

	wp_enqueue_script( 'dt-gutenberg-syndicated-post', plugins_url( '/dist/js/gutenberg-syndicated-post.min.js', __DIR__ ), [ 'wp-blocks' ], DT_VERSION, true );
	wp_enqueue_script( 'dt-gutenberg-syndicated-status-plugin', plugins_url( '/dist/js/gutenberg-status-plugin.min.js', __DIR__ ), [ 'wp-blocks', 'wp-edit-post' ], DT_VERSION, true );

	wp_localize_script(
		'dt-gutenberg-syndicated-post',
		'dtGutenberg',
		[
			'i18n'                 => $i18n_locale,
			'originalBlogId'       => (int) $original_blog_id,
			'originalPostId'       => (int) $original_post_id,
			'originalSourceId'     => (int) $original_source_id,
			'originalDelete'       => (int) $original_deleted,
			'unlinked'             => (int) $unlinked,
			'postTypeSingular'     => sanitize_text_field( $post_type_singular ),
			'postUrl'              => sanitize_text_field( $post_url ),
			'originalSiteName'     => sanitize_text_field( $original_site_name ),
			'syndicationTime'      => ( ! empty( $syndication_time ) ) ? esc_html( gmdate( 'M j, Y @ h:i', ( $syndication_time + ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ) ) ) ) : 0,
			'syndicationCount'     => $total_connections,
			'originalLocationName' => sanitize_text_field( $original_location_name ),
			'unlinkNonceUrl'       => wp_nonce_url( add_query_arg( 'action', 'unlink', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "unlink-post_{$post->ID}" ),
			'linkNonceUrl'         => wp_nonce_url( add_query_arg( 'action', 'link', admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) ), "link-post_{$post->ID}" ),
		]
	);
}

/**
 * Enqueue admin scripts/styles for edit.php
 *
 * @param  string $hook WP hook.
 * @since  0.8
 */
function enqueue_edit_scripts( $hook ) {
	if ( 'edit.php' !== $hook ) {
		return;
	}

	wp_enqueue_style( 'dt-admin-syndicated-post', plugins_url( '/dist/css/admin-edit-table.min.css', __DIR__ ), array(), DT_VERSION );
}
