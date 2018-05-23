<?php
/**
 * Plugin Name:       Distributor
 * Description:       Syndicate content to and from external websites and within multisite blogs.
 * Version:           1.1.0
 * Author:            Taylor Lovett, 10up
 * Author URI:        http://10up.com
 * License:           GPLv2 or later
 * Text Domain:       distributor
 * Domain Path:       /lang/
 * GitHub Plugin URI: https://github.com/10up/distributor
 *
 * @package distributor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DT_VERSION', '1.1.0-1' );

/**
 * PSR-4 autoloading
 */
spl_autoload_register(
	function( $class ) {
			// Project-specific namespace prefix.
			$prefix = 'Distributor\\';

			// Base directory for the namespace prefix.
			$base_dir = __DIR__ . '/includes/classes/';

			// Does the class use the namespace prefix?
			$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

			$relative_class = substr( $class, $len );

			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			// If the file exists, require it.
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);

/**
 * Require PHP version 5.6 - throw an error if the plugin is activated on an older version.
 */
register_activation_hook(
	__FILE__, function() {
		if ( version_compare( PHP_VERSION, '5.6.0', '<' ) ) {
			wp_die(
				esc_html__( 'Distributor requires PHP version 5.6.', 'distributor' ),
				esc_html__( 'Error Activating', 'distributor' )
			);
		}
	}
);

/**
 * Tell the world this site supports Distributor. We need this for external connections.
 */
add_action(
	'send_headers', function() {
		if ( ! headers_sent() ) {
			header( 'X-Distributor: yes' );
		}
	}
);

/**
 * Set Distributor header in all API responses.
 */
add_filter(
	'rest_post_dispatch', function( $response ) {
		$response->header( 'X-Distributor', 'yes' );

		return $response;
	}
);

\Distributor\Connections::factory();

require_once __DIR__ . '/includes/utils.php';
require_once __DIR__ . '/includes/external-connection-cpt.php';
require_once __DIR__ . '/includes/push-ui.php';
require_once __DIR__ . '/includes/pull-ui.php';
require_once __DIR__ . '/includes/rest-api.php';
require_once __DIR__ . '/includes/subscriptions.php';
require_once __DIR__ . '/includes/syndicated-post-ui.php';
require_once __DIR__ . '/includes/distributed-post-ui.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/template-tags.php';

if ( \Distributor\Utils\is_vip_com() ) {
	add_filter( 'dt_network_site_connection_enabled', '__return_false', 9 );
}

/**
 * Register connections
 */
add_action(
	'init', function() {
		\Distributor\Connections::factory()->register( '\Distributor\ExternalConnections\WordPressExternalConnection' );
		\Distributor\Connections::factory()->register( '\Distributor\ExternalConnections\WordPressDotcomExternalConnection' );
		if (
			/**
			 * Filter whether the network connection type is enabled. Enabled by default, return false to disable.
			 *
			 * @since 1.0.0
			 *
			 * @param bool true Whether the network connection should be enabled.
			 */
			apply_filters( 'dt_network_site_connection_enabled', true )
		) {
			\Distributor\Connections::factory()->register( '\Distributor\InternalConnections\NetworkSiteConnection' );
		}
	}, 1
);

/**
 * We use setup functions to avoid unit testing WP_Mock strict mode errors.
 */
\Distributor\ExternalConnectionCPT\setup();
\Distributor\PushUI\setup();
\Distributor\PullUI\setup();
\Distributor\RestApi\setup();
\Distributor\Subscriptions\setup();
\Distributor\SyndicatedPostUI\setup();
\Distributor\DistributedPostUI\setup();
\Distributor\Settings\setup();

