<?php
/**
 * Bootstrap the main plugin.
 *
 * This file is included by the main plugin file and is responsible for
 * bootstrapping the plugin. This allows the main file to be used for version
 * support and therefore support earlier versions of PHP and WP than the
 * minimum requirements.
 *
 * @package  distributor
 */

namespace Distributor;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * PSR-4 autoloading
 */
spl_autoload_register(
	function( $class ) {
			// Project-specific namespace prefix.
			$prefix = 'Distributor\\';
			// Base directory for the namespace prefix.
			$base_dir = __DIR__ . '/classes/';
			// Does the class use the namespace prefix?
			$len = strlen( $prefix );
		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}
			$relative_class = substr( $class, $len );
			$file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
			// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Tell the world this site supports Distributor. We need this for external connections.
 */
add_action(
	'send_headers',
	function() {
		if ( ! headers_sent() ) {
			header( 'X-Distributor: yes' );
		}
	}
);

/**
 * Set Distributor header in all API responses.
 */
add_filter(
	'rest_post_dispatch',
	function( $response ) {
		$response->header( 'X-Distributor', 'yes' );
		$response->header( 'X-Distributor-Version', DT_VERSION );

		return $response;
	}
);

\Distributor\Connections::factory();

// Include in case we have composer issues.
require_once DT_PLUGIN_PATH . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';

require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/global-functions.php';
require_once __DIR__ . '/hooks.php';
require_once __DIR__ . '/external-connection-cpt.php';
require_once __DIR__ . '/push-ui.php';
require_once __DIR__ . '/pull-ui.php';
require_once __DIR__ . '/rest-api.php';
require_once __DIR__ . '/subscriptions.php';
require_once __DIR__ . '/syndicated-post-ui.php';
require_once __DIR__ . '/distributed-post-ui.php';
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/template-tags.php';
require_once __DIR__ . '/debug-info.php';

// Include application passwords.
add_action(
	'plugins_loaded',
	function() {
		if ( ! wp_is_application_passwords_available() ) {
			add_action(
				'admin_notices',
				function() {
					if ( get_current_screen()->id !== 'toplevel_page_distributor' ) {
						return;
					}
					?>
					<div class="notice notice-warning">
						<p>
							<?php
							echo wp_kses_post(
								sprintf(
									/* translators: %s is the URL to the guide to enable Application Passwords for non HTTPS sites. */
									__( 'Your site is not using HTTPS or is a local environment. Follow this <a href="%s">guide</a> to enable Application Passwords.', 'distributor' ),
									'https://github.com/10up/distributor#application-passwords-and-wordpress-56'
								)
							);
							?>
						</p>
					</div>
					<?php
				}
			);
		}
	}
);

// Override some styles for application passwords until we can get these changes upstream.
add_action(
	'admin_enqueue_scripts',
	function() {
		$asset_file = DT_PLUGIN_PATH . '/dist/js/admin-css.min.asset.php';
		// Fallback asset data.
		$asset_data = array(
			'version'      => DT_VERSION,
			'dependencies' => array(),
		);
		if ( file_exists( $asset_file ) ) {
			$asset_data = require $asset_file;
		}

		// Dependencies only apply to JavaScript, not CSS files.
		wp_enqueue_style( 'dt-admin-settings', plugins_url( '/dist/css/admin.min.css', __DIR__ ), array(), $asset_data['version'] );
	}
);

if ( class_exists( '\\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
	/**
	 * Enable updates if we have a valid license
	 */
	$valid_license = false;

	if ( ! DT_IS_NETWORK ) {
		$valid_license = \Distributor\Utils\get_settings()['valid_license'];
	} else {
		$valid_license = \Distributor\Utils\get_network_settings()['valid_license'];
	}

	if ( $valid_license ) {
		// @codingStandardsIgnoreStart
		$updateChecker = PucFactory::buildUpdateChecker(
			'https://github.com/10up/distributor/',
			DT_PLUGIN_FULL_FILE,
			'distributor'
		);

		$updateChecker->getVcsApi()->enableReleaseAssets();

		$updateChecker->addResultFilter(
			function( $plugin_info, $http_response = null ) {
				$plugin_info->icons = array(
					'svg' => plugins_url( '/assets/img/icon.svg', __DIR__ ),
				);
				return $plugin_info;
			}
		);

		add_filter(
			'pre_set_site_transient_update_plugins',
			function( $transient ) use ( $updateChecker ) {
				$update = $updateChecker->getUpdate();

				if ( $update ) {
					// Update is available.
					$transient->response[ $update->filename ] = $update->toWpFormat();
				} else {
					// No update is available.
					$update = $updateChecker->getUpdateState()->getUpdate();
					// Adding the plugin info to the `no_update` property is required
					// for the enable/disable auto-updates links to correctly appear in UI.
					if ( $update ) {
						$transient->no_update[ $update->filename ] = $update;
					}
				}

				return $transient;
			}
		);
		// @codingStandardsIgnoreEnd
	}
}

/**
 * Register connections
 */
add_action(
	'init',
	function() {
		\Distributor\Connections::factory()->register( '\Distributor\ExternalConnections\WordPressExternalConnection' );
		\Distributor\Connections::factory()->register( '\Distributor\ExternalConnections\WordPressDotcomExternalConnection' );
		if (
			/**
			 * Filter whether the network connection type is enabled. Enabled by default, return false to disable.
			 *
			 * @since 1.0.0
			 * @hook dt_network_site_connection_enabled
			 *
			 * @param {bool} true Whether the network connection should be enabled.
			 *
			 * @return {bool} Whether the network connection should be enabled.
			 */
			apply_filters( 'dt_network_site_connection_enabled', true )
		) {
			\Distributor\Connections::factory()->register( '\Distributor\InternalConnections\NetworkSiteConnection' );
		}
		load_plugin_textdomain( 'distributor', false, basename( dirname( DT_PLUGIN_FULL_FILE ) ) . '/lang' );
	},
	1
);

/**
 * We use setup functions to avoid unit testing WP_Mock strict mode errors.
 */
\Distributor\ExternalConnectionCPT\setup();
\Distributor\Hooks\setup();
\Distributor\PushUI\setup();
\Distributor\PullUI\setup();
\Distributor\RestApi\setup();
\Distributor\Subscriptions\setup();
\Distributor\SyndicatedPostUI\setup();
\Distributor\DistributedPostUI\setup();
\Distributor\Settings\setup();
\Distributor\DebugInfo\setup();
