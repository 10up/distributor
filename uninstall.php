<?php
/**
 * Distributor uninstall script.
 *
 * @since x.x.x
 * @package distributor
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

if ( is_multisite() && wp_is_large_network() ) {
	delete_site_option( 'dt_settings' );
	delete_site_option( 'dt_sync_log' );
} else {
	delete_option( 'dt_settings' );
	delete_option( 'dt_sync_log' );
}

// Delete options.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'dt\_%';" );

// Remove transients.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transients\_dt\_%';" );

// Delete our data from the post and post meta tables.
$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE post_type IN ( 'dt_subscription', 'dt_ext_connection' );" );
$wpdb->query( "DELETE meta FROM {$wpdb->postmeta} meta LEFT JOIN {$wpdb->posts} posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );
