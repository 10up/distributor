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

	// Delete our data from the post and post meta tables.
	$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type IN ( 'dt_subscription', 'dt_ext_connection' );" );
	$wpdb->query( "DELETE meta FROM $wpdb->postmeta meta LEFT JOIN $wpdb->posts posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL OR meta.meta_key LIKE 'dt\_%';" );

	// Clear cache.
	if ( wp_cache_flush() ) {
		wp_cache_set_posts_last_changed();
	}
}

// phpcs:enable
