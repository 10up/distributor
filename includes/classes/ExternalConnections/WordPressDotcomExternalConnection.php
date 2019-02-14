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

	/**
	 * Connection slug
	 *
	 * @var string
	 */
	static public $slug = 'wpdotcom';

	/**
	 * Connection pretty label
	 *
	 * This is to represent the authentication method,
	 * not the connection type. This value was previously
	 * "WordPress.com REST API".
	 *
	 * @since 1.4.0 Label as authentication method, not connection type
	 *
	 * @var string
	 */
	static public $label = 'WordPress.com Application';

	/**
	 * Connection auth class
	 *
	 * @var string
	 */
	static public $auth_handler_class = '\Distributor\Authentications\WordPressDotcomOauth2Authentication';

	/**
	 * Connection REST API namespace
	 *
	 * @var string
	 */
	static public $namespace = 'wp/v2';

}
