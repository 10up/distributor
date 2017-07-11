<?php

/**
 * Plugin Name: Distributor
 * Description: Syndicate content to and from external websites and within multisite blogs.
 * Version:     1.0
 * Author:      Taylor Lovett, 10up
 * Author URI:  http://10up.com
 * License:     GPLv2 or later
 * Text Domain: distributor
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'DT_VERSION', '1.0' );

/**
 * PSR-4 autoloading
 */
spl_autoload_register( function( $class ) {
	// project-specific namespace prefix
	$prefix = 'Distributor\\';

	// base directory for the namespace prefix
	$base_dir = __DIR__ . '/includes/classes/';

	// does the class use the namespace prefix?
	$len = strlen( $prefix );

	if ( strncmp( $prefix, $class, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, $len );

	$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

	// if the file exists, require it
	if ( file_exists( $file ) ) {
		require $file;
	}
} );

/**
 * Tell the world this site supports Distributor. We need this for external connections.
 */
add_action( 'send_headers', function() {
	@header( 'X-Distributor: yes' );
} );

/**
 * Set Distributor header in all API responses
 */
add_filter( 'rest_post_dispatch', function( $response ) {
	$response->header( 'X-Distributor', 'yes' );

	return $response;
} );

\Distributor\Connections::factory();

\Distributor\Connections::factory()->register( '\Distributor\ExternalConnections\WordPressExternalConnection' );
\Distributor\Connections::factory()->register( '\Distributor\InternalConnections\NetworkSiteConnection', 'internal' );


require_once( __DIR__ . '/includes/utils.php' );
require_once( __DIR__ . '/includes/external-connection-cpt.php' );
require_once( __DIR__ . '/includes/push-ui.php' );
require_once( __DIR__ . '/includes/pull-ui.php' );
require_once( __DIR__ . '/includes/rest-api.php' );
require_once( __DIR__ . '/includes/subscriptions.php' );
require_once( __DIR__ . '/includes/syndicated-post-ui.php' );

/**
 * We use setup functions to avoid unit testing WP_Mock strict mode errors.
 */
\Distributor\ExternalConnectionCPT\setup();
\Distributor\PushUI\setup();
\Distributor\PullUI\setup();
\Distributor\RestApi\setup();
\Distributor\Subscriptions\setup();
\Distributor\SyndicatedPostUI\setup();

