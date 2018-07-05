<?php
/**
 * WordPress.com external connection functionality
 *
 * @package  distributor
 */

namespace Distributor\ExternalConnections;

use \Distributor\ExternalConnections\WordPressExternalConnection as WordPressExternalConnection;

/**
 * WP.COM external connection class
 */
class WordPressDotcomExternalConnection extends WordPressExternalConnection {

	static $slug               = 'wpdotcom';
	static $label              = 'WordPress.com REST API';
	static $auth_handler_class = '\Distributor\Authentications\WordPressDotcomOauth2Authentication';
	static $namespace          = 'wp/v2';

}
