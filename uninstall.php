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

	// Function to delete data on a specific site
	function dt_delete_data() {
		global $wpdb;

		// Delete options.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'dt\_%';" );

		// Remove transients.
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transients\_dt\_%';" );

		// Delete our data from the post and post meta tables.
		$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type IN ( 'dt_subscription', 'dt_ext_connection' );" );
		$wpdb->query( "DELETE meta FROM $wpdb->postmeta meta LEFT JOIN $wpdb->posts posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL OR meta.meta_key LIKE 'dt\_%';" );

		// Clear cache.
		wp_cache_set_posts_last_changed();
		wp_cache_delete( 'alloptions', 'options' );
	}

	if ( is_multisite() ) {
		// Loop through each site in the network
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			dt_delete_data();
			restore_current_blog();
		}
	} else {
		// Single site
		dt_delete_data();
	}
}

// phpcs:enable
