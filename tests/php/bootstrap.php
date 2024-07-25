<?php

/**
 * Load Composer autoloader.
 */
require dirname( __FILE__, 3 ) . '/vendor/autoload.php';

$_tests_dir = getenv( 'WP_TESTS_DIR' );

define( 'FS_METHOD', 'direct' );
define( 'TEST_DIR', __DIR__ );
define( 'PHPUNIT_RUNNER', true );
define( 'DT_PLUGIN_PATH', dirname( __DIR__, 2 ) );
define( 'DT_VERSION', '2.0.4' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL;
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __FILE__, 3 ) . '/distributor.php';
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
