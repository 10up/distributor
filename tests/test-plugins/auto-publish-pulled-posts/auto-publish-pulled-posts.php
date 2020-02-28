<?php
/**
 * Plugin Name:       Auto publish pulled posts.
 * Description:       Helper plugin for Distributor tests. Set pulled post status of external connections to publish.
 * Version:           1.0.0
 * Author:            10up Inc.
 * Author URI:        https://distributorplugin.com
 * License:           GPLv2 or later
 * Text Domain:       distributor-tests
 * Domain Path:       /lang/
 * GitHub Plugin URI: https://github.com/10up/distributor-tests
 *
 * @package distributor-tests
 */

add_filter( 'dt_pull_post_args', function( $post_array ) {
	$post_array[ 'post_status' ] = 'publish';

	return $post_array;
} );
