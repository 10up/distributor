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

global $wpdb;

// Delete options.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE 'dt\_%';" );

// Remove transients.
$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '_transients\_dt\_%';" );

// Delete our data from the post and post meta tables.
$wpdb->query( "DELETE FROM $wpdb->posts WHERE post_type IN ( 'dt_subscription', 'dt_ext_connection' );" );
$wpdb->query( "DELETE FROM $wpdb->postmeta as meta LEFT JOIN $wpdb->posts as posts ON posts.ID = meta.post_id WHERE posts.ID IS NULL;" );

// Clear cache.
if ( wp_cache_flush() ) {
	wp_cache_set_posts_last_changed();
}

// phpcs:enable
