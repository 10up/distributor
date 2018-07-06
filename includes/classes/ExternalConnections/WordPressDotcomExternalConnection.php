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

	static public $slug               = 'wpdotcom';
	static public $label              = 'WordPress.com REST API';
	static public $auth_handler_class = '\Distributor\Authentications\WordPressDotcomOauth2Authentication';
	static public $namespace          = 'wp/v2';

}
