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
	public static $slug = 'wpdotcom';

	/**
	 * Connection pretty label
	 *
	 * @var string
	 */
	public static $label = 'WordPress.com REST API';

	/**
	 * Connection auth class
	 *
	 * @var string
	 */
	public static $auth_handler_class = '\Distributor\Authentications\WordPressDotcomOauth2Authentication';

	/**
	 * Connection REST API namespace
	 *
	 * @var string
	 */
	public static $namespace = 'wp/v2';

}
