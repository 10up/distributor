<?php

/**
 * Simple common WP classes/functions
 */

/**
 * Hollowed out WP_Error class for mocking
 *
 * @since  0.8
 */
class WP_Error {
	public function __construct( $code = '', $message = '' ) {
		$this->code = $code;
		$this->message = $message;
	}
}

/**
 * Maybe unserialize variable. Pulled from WP core
 *
 * @param  mixed $original
 * @since  1.0
 * @return array
 */
function maybe_unserialize( $original ) {
	if ( is_serialized( $original ) ) { // don't attempt to unserialize data that wasn't serialized going in
		return @unserialize( $original );
	}
	return $original;
}

/**
 * Check if variable is serialized. Pulled from WP core
 *
 * @param  mixed $data
 * @param  bool  $strict
 * @since  1.0
 * @return bool
 */
function is_serialized( $data, $strict = true ) {
	// if it isn't a string, it isn't serialized.
	if ( ! is_string( $data ) ) {
		return false;
	}
	$data = trim( $data );
	if ( 'N;' == $data ) {
		return true;
	}
	if ( strlen( $data ) < 4 ) {
		return false;
	}
	if ( ':' !== $data[1] ) {
		return false;
	}
	if ( $strict ) {
		$lastc = substr( $data, -1 );
		if ( ';' !== $lastc && '}' !== $lastc ) {
			return false;
		}
	} else {
		$semicolon = strpos( $data, ';' );
		$brace     = strpos( $data, '}' );
		// Either ; or } must exist.
		if ( false === $semicolon && false === $brace ) {
			return false;
		}
		// But neither must be in the first X characters.
		if ( false !== $semicolon && $semicolon < 3 ) {
			return false;
		}
		if ( false !== $brace && $brace < 4 ) {
			return false;
		}
	}
	$token = $data[0];
	switch ( $token ) {
		case 's' :
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
			} elseif ( false === strpos( $data, '"' ) ) {
				return false;
			}
			// or else fall through
		case 'a' :
		case 'O' :
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b' :
		case 'i' :
		case 'd' :
			$end = $strict ? '$' : '';
			return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	}
	return false;
}

/**
 * Hollowed out WP_Query class for mocking
 *
 * @since  0.8
 */
class WP_Query {
	public function __construct( $args = array() ) {

		$items = [
			[
				'ID' => 2,
				'title' => 'my title a',
				'content' => 'my content a',
			],
			[
				'ID' => 188,
				'title' => 'my title b',
				'content' => 'my content b',
			],
			[
				'ID' => 198,
				'title' => 'my title c',
				'content' => 'my content c',
			],
		];

		foreach ( $items as $item ) {
			foreach ( $item as $key => $value ) {
				$tmp[ $key ] = $value;
			}
			$posts[] = (object) $tmp;
		}

		$this->found_posts = count( $items );
		$this->posts = $posts;

	}

}

/**
 * Check if object is WP_Error
 *
 * @param  Object $thing
 * @since  0.8
 * @return boolean
 */
function is_wp_error( $thing ) {
	return ( $thing instanceof WP_Error );
}

/**
 * Sets up a valid remote get request.
 *
 * @since  0.8
 * @return void
 */
function remote_get_setup() {

	\WP_Mock::userFunction( 'get_option' );

	$post_type = 'post';
	$links = [
		'_links' => [
			'wp:items' => [
				[ 'href' => 'http://url.com' ],
			],
		],
	];

	\WP_Mock::userFunction( 'wp_remote_get', [
		'return' => json_encode( [
			$post_type => $links,
		] ),
	] );

	\WP_Mock::userFunction( 'wp_remote_retrieve_body', [
		'return' => json_encode( [
			'id'               => 123,
			'title'            => [ 'rendered' => 'My post title' ],
			'content'          => [ 'rendered' => '' ],
			'excerpt'          => [ 'rendered' => '' ],
			'date'             => '',
			'date_gmt'         => '',
			'guid'             => [ 'rendered' => '' ],
			'modified'         => '',
			'modified_gmt'     => '',
			'type'             => '',
			'link'             => '',
			'distributor_meta' => [],
			'distributor_terms' => [],
			'distributor_media' => [],
			$post_type         => $links,
		] ),
	] );
}

/**
 * Mock WP_Post
 *
 * @since  1.0
 */
class WP_Post {
	public function __construct( $post ) {
		if ( ! empty( $post ) ) {
			$post_props = get_object_vars( $post );

			foreach ( $post_props as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

/**
 * Return testing friendly url
 *
 * @since  1.0
 * @return string
 */
function home_url() {
	return 'http://test.com';
}

/**
 * Return mime types for testing
 *
 * @since  1.0
 * @return string
 */
function get_allowed_mime_types() {
	return [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'gif'          => 'image/gif',
		'png'          => 'image/png',
		'bmp'          => 'image/bmp',
		'tif|tiff'     => 'image/tiff',
		'ico'          => 'image/x-icon',
	];
}

/**
 * Classes for testing connections
 */
class TestExternalConnection extends \Distributor\ExternalConnection {
	static $slug = 'test-external-connection';
	static $auth_handler_class = '\Distributor\Authentications\WordPressBasicAuth';
	static $namespace = 'wp/v2';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function check_connections() { }

	public function remote_get( $args ) { }
}

class TestInternalConnection extends \Distributor\Connection {
	static $slug = 'test-internal-connection';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function remote_get( $args ) { }

	public function log_sync( array $item_id_mappings ) { }
}
