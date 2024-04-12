<?php

/**
 * Set up PHPUnit test library with WP Mock
 */

require_once __DIR__ . '/../../vendor/autoload.php';

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

define( 'DT_PLUGIN_PATH', dirname( __DIR__, 2 ) );
define( 'DT_VERSION', '2.0.4' );

require_once __DIR__ . '/includes/common.php';
require_once __DIR__ . '/includes/TestCase.php';

// Load plugin files.
require_once( __DIR__ . '/../../includes/hooks.php' );
require_once( __DIR__ . '/../../includes/utils.php' );
require_once( __DIR__ . '/../../includes/debug-info.php' );
require_once( __DIR__ . '/../../includes/subscriptions.php' );
require_once( __DIR__ . '/../../includes/global-functions.php' );
