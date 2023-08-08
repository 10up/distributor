<?php
/**
 * Distributor uninstall script.
 *
 * @package distributor
 * @since   x.x.x
 */

// phpcs:disable

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/*
 * Only remove ALL product and page data if DT_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'DT_REMOVE_ALL_DATA' ) && true === DT_REMOVE_ALL_DATA ) {
	global $wpdb;

	// Delete options.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'dt\_%';" );

	// Remove transients.
	$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transients\_dt\_%';" );

	// Delete our data from the comment and comment meta tables.
	$wpdb->query(
		"
		DELETE FROM $wpdb->comments as comments
		LEFT JOIN $wpdb->posts as posts ON posts.ID = comments.comment_post_ID
		WHERE posts.post_type IN ( 'dt_subscription', 'dt_ext_connection' );
		"
	);
	$wpdb->query(
		"
		DELETE meta FROM {$wpdb->commentmeta} meta
		LEFT JOIN {$wpdb->comments} comments ON comments.comment_ID = meta.comment_id
		WHERE comments.comment_ID IS NULL;
		"
	);

	// Delete our data from the post and post meta tables.
	$wpdb->query(
		"
		DELETE FROM $wpdb->posts
		WHERE post_type IN ( 'dt_subscription', 'dt_ext_connection' );
		"
	);
	$wpdb->query(
		"
		DELETE FROM $wpdb->postmeta as meta
		LEFT JOIN $wpdb->posts as posts ON posts.ID = meta.post_id
		WHERE posts.ID IS NULL;
		"
	);

	// Delete orphan relationships.
	$wpdb->query(
		"
		DELETE tr FROM {$wpdb->term_relationships} tr
		LEFT JOIN {$wpdb->posts} posts ON posts.ID = tr.object_id
		WHERE posts.ID IS NULL;"
	);

	// Delete orphan terms.
	$wpdb->query(
		"
		DELETE t FROM {$wpdb->terms} t
		LEFT JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
		WHERE tt.term_id IS NULL;
		"
	);

	// Delete orphan term meta.
	$wpdb->query(
		"
		DELETE tm FROM {$wpdb->termmeta} tm
		LEFT JOIN {$wpdb->term_taxonomy} tt ON tm.term_id = tt.term_id
		WHERE tt.term_id IS NULL;
		"
	);

	// Clear cache.
	if ( wp_cache_flush() ) {
		wp_cache_set_posts_last_changed();
	}
}

// phpcs:enable
