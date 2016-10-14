<?php

/**
 * Plugin Name: Syndicate
 * Description: Syndicate content to and from external websites and within multisite blogs.
 * Version:     0.8
 * Author:      Taylor Lovett, 10up
 * Author URI:  http://10up.com
 * License:     GPLv2 or later
 * Text Domain: syndicate
 * Domain Path: /lang/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'SY_VERSION', '0.8' );

/**
 * PSR-4 autoloading
 */
spl_autoload_register( function( $class ) {
	// project-specific namespace prefix
	$prefix = 'Syndicate\\';

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

\Syndicate\Connections::factory();

\Syndicate\Connections::factory()->register( '\Syndicate\ExternalConnections\WordPressExternalConnection' );
\Syndicate\Connections::factory()->register( '\Syndicate\InternalConnections\NetworkSiteConnection', 'internal' );

require_once( __DIR__ . '/includes/external-connection-cpt.php' );
require_once( __DIR__ . '/includes/push-ui.php' );
require_once( __DIR__ . '/includes/pull-ui.php' );
require_once( __DIR__ . '/includes/syndicated-post-ui.php' );
