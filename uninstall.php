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
