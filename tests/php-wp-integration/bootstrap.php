<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package Post_Content_Warnings
 */

$_distributor_wp_tests_directory = getenv( 'WP_TESTS_DIR' );

if ( ! $_distributor_wp_tests_directory ) {
	$_distributor_wp_tests_directory = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_distributor_phpunit_polyfills_path = __DIR__ . '/../vendor/yoast/phpunit-polyfills/phpunitpolyfills-autoload.php';
if ( false !== $_distributor_phpunit_polyfills_path ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- test suite constant.
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_distributor_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_distributor_wp_tests_directory}/includes/functions.php" ) ) {
	echo "Could not find {$_distributor_wp_tests_directory}/includes/functions.php, have you run tests/bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_distributor_wp_tests_directory}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _distributor_tests_load_plugin() {
	require dirname( __DIR__, 2 ) . '/distributor.php';
}

tests_add_filter( 'muplugins_loaded', '_distributor_tests_load_plugin' );
tests_add_filter( 'pre_option_uploads_use_yearmonth_folders', '__return_zero' ); // false does not preflight option.

// Start up the WP testing environment.
require "{$_distributor_wp_tests_directory}/includes/bootstrap.php";
