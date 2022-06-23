<?php
/**
 * Plugin Name: Disable HTTP SSL check
 * Version:     1.0.0
 * Author:      10up Inc.
 * License:     GPLv2 or later
 */

// To make connection between local sites in Distributor.
add_filter( 'http_request_args', function ( $params, $url ) {
	$params['sslverify'] = false;

	return $params;
}, 10, 2 );
