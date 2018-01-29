<?php

namespace Distributor\ExternalConnections;

use \Distributor\ExternalConnections\WordPressExternalConnection as WordPressExternalConnection;

class WordPressDotcomExternalConnection extends WordPressExternalConnection {

	static $slug               = 'wpdotcom';
	static $label              = 'WordPress.com REST API';
	static $auth_handler_class = '\Distributor\Authentications\WordPressDotcomOauth2Authentication';
	static $namespace          = 'wp/v2';

}
