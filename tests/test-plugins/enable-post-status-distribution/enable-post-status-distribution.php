<?php
/**
 * Plugin Name:       Enable post status distribution
 * Description:       Helper plugin for Distributor tests. Enables post status distribution with the `dt_distribute_post_status` filter.
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

// Enable post status distribution.
add_filter( 'dt_distribute_post_status', '__return_true' );