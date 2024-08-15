<?php
/**
 * Plugin Name:       Distributor
 * Plugin URI:        https://github.com/10up/distributor
 * Update URI:        https://distributorplugin.com
 * Description:       Makes it easy to distribute and reuse content across your websites, whether inside of a multisite or across the web.
 * Version:           2.0.5
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            10up Inc.
 * Author URI:        https://distributorplugin.com
 * License:           GPLv2 or later
 * Text Domain:       distributor
 * Domain Path:       /lang/
 *
 * @package distributor
 *
 * Developer note: This file is used to test whether the user's server supports
 * the minimum software requirements for this plugin prior to bootstrapping.
 *
 * Unlike the rest of the plugin, this file needs to be compatible with PHP 5.6
 * and WordPress 4.7.0.
 */

namespace Distributor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DT_VERSION', '2.0.5' );
define( 'DT_PLUGIN_FILE', preg_replace( '#^.*plugins/(.*)$#i', '$1', __FILE__ ) );
define( 'DT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'DT_PLUGIN_FULL_FILE', __FILE__ );

// Define a constant if we're network activated to allow plugin to respond accordingly.
$active_plugins = get_site_option( 'active_sitewide_plugins' );

if ( is_multisite() && isset( $active_plugins[ plugin_basename( __FILE__ ) ] ) ) {
	define( 'DT_IS_NETWORK', true );
} else {
	define( 'DT_IS_NETWORK', false );
}

/**
 * Get the minimum version of WordPress required by this plugin.
 *
 * @since 2.0.0
 *
 * @return string Minimum version required.
 */
function minimum_wp_requirement() {
	return '6.4';
}

/**
 * Get the minimum version of PHP required by this plugin.
 *
 * @since 2.0.0
 *
 * @return string Minimum version required.
 */
function minimum_php_requirement() {
	return '7.4';
}

/**
 * Whether WP installation meets the minimum requirements
 *
 * @since 2.0.0
 *
 * @return bool True if meets minimum requirements, false otherwise.
 */
function site_meets_wp_requirements() {
	global $wp_version;
	return version_compare( $wp_version, minimum_wp_requirement(), '>=' );
}

/**
 * Whether PHP installation meets the minimum requirements
 *
 * @since 2.0.0
 *
 * @return bool True if meets minimum requirements, false otherwise.
 */
function site_meets_php_requirements() {
	return version_compare( phpversion(), minimum_php_requirement(), '>=' );
}

/**
 * Require PHP 7.4+, WP 6.4+ - throw an error if the plugin is activated on an older version.
 */
register_activation_hook(
	__FILE__,
	function() {
		if (
			! site_meets_wp_requirements() &&
			! site_meets_php_requirements()
		) {
			wp_die(
				sprintf(
					/* translators: %1$s: Minimum required PHP version, %2$s: Minimum required WordPress version */
					esc_html__( 'Distributor requires PHP version %1$s or later and WordPress version %2$s or later. Please upgrade your software or disable the plugin.', 'distributor' ),
					esc_html( minimum_php_requirement() ),
					esc_html( minimum_wp_requirement() )
				),
				esc_html__( 'Error Activating', 'distributor' )
			);

		} elseif ( ! site_meets_wp_requirements() ) {
			wp_die(
				sprintf(
					/* translators: %s: Minimum required WordPress version */
					esc_html__( 'Distributor requires WordPress version %s or later. Please upgrade WordPress or disable the plugin.', 'distributor' ),
					esc_html( minimum_wp_requirement() )
				),
				esc_html__( 'Error Activating', 'distributor' )
			);
		} elseif ( ! site_meets_php_requirements() ) {
			wp_die(
				sprintf(
					/* translators: %s: Minimum required PHP version */
					esc_html__( 'Distributor requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'distributor' ),
					esc_html( minimum_php_requirement() )
				),
				esc_html__( 'Error Activating', 'distributor' )
			);
		}
	}
);

add_action(
	'plugins_loaded',
	function() {
		if ( site_meets_wp_requirements() && site_meets_php_requirements() ) {
			// Do nothing, everything is fine.
			return;
		}

		add_action(
			'admin_notices',
			function() {
				if (
					! site_meets_wp_requirements() &&
					! site_meets_php_requirements()
				) {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							printf(
								/* translators: %1$s: Minimum required PHP version, %2$s: Minimum required WordPress version */
								esc_html__( 'Distributor requires PHP version %1$s or later and WordPress version %2$s or later. Please upgrade your software or disable the plugin.', 'distributor' ),
								esc_html( minimum_php_requirement() ),
								esc_html( minimum_wp_requirement() )
							);
							?>
						</p>
					</div>
					<?php
					return;
				}

				if ( ! site_meets_wp_requirements() ) {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							printf(
								/* translators: %s: Minimum required WordPress version */
								esc_html__( 'Distributor requires WordPress version %s or later. Please upgrade WordPress or disable the plugin.', 'distributor' ),
								esc_html( minimum_wp_requirement() )
							);
							?>
						</p>
					</div>
					<?php
					return;
				}

				if ( ! site_meets_php_requirements() ) {
					?>
					<div class="notice notice-error">
						<p>
							<?php
							printf(
								/* translators: %s: Minimum required PHP version */
								esc_html__( 'Distributor requires PHP version %s or later. Please upgrade PHP or disable the plugin.', 'distributor' ),
								esc_html( minimum_php_requirement() )
							);
							?>
						</p>
					</div>
					<?php
					return;
				}
			}
		);
	}
);

if ( ! site_meets_wp_requirements() || ! site_meets_php_requirements() ) {
	return;
}

require_once __DIR__ . '/includes/bootstrap.php';
