<?php

/**
 * Simple common WP classes/functions
 */

namespace Distributor\Tests\Utils;

/**
 * Hollowed out WP_Error class for mocking
 *
 * @since  0.8
 */
class WP_Error {
	public function __construct( $code = '', $message = '' ) {
		$this->code    = $code;
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
		case 's':
			if ( $strict ) {
				if ( '"' !== substr( $data, -2, 1 ) ) {
					return false;
				}
			} elseif ( false === strpos( $data, '"' ) ) {
				return false;
			}
			// or else fall through
		case 'a':
		case 'O':
			return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		case 'b':
		case 'i':
		case 'd':
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
				'ID'      => 2,
				'title'   => 'my title a',
				'content' => 'my content a',
			],
			[
				'ID'      => 188,
				'title'   => 'my title b',
				'content' => 'my content b',
			],
			[
				'ID'      => 198,
				'title'   => 'my title c',
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
		$this->posts       = $posts;

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

	$rest_response = [
		[
			'post_title'                     => 'My post title',
			'post_name'                      => 'my-post-title',
			'post_type'                      => 'post',
			'post_content'                   => '',
			'post_excerpt'                   => '',
			'post_status'                    => 'publish',
			'terms'                          => [],
			'meta'                           => [],
			'media'                          => [],
			'post_author'                    => 1,
			'meta_input'                     => [
				'dt_original_post_id'  => 123,
				'dt_original_post_url' => 'http://example.com/2023/04/11/my-post-title/',
			],
			'ID'                             => 123,
			'post_date'                      => '2023-04-11 05:40:43',
			'post_date_gmt'                  => '2023-04-11 05:40:43',
			'post_modified'                  => '2023-04-11 05:40:43',
			'post_modified_gmt'              => '2023-04-11 05:40:43',
			'post_password'                  => '',
			'guid'                           => 'http://example.com/?p=123',
			'comment_status'                 => 'open',
			'ping_status'                    => 'open',
			'link'                           => 'http://example.com/2023/04/11/my-post-title/',
			'distributor_original_site_name' => 'My site name',
			'distributor_original_site_url'  => 'http://example.com/',
		],
	];

	$post_response = $rest_response[0];
	$post_response['original_site_name'] = $post_response['distributor_original_site_name'];
	$post_response['original_site_url']  = $post_response['distributor_original_site_url'];
	unset( $post_response['distributor_original_site_name'] );
	unset( $post_response['distributor_original_site_url'] );

	\WP_Mock::userFunction(
		'wp_remote_post', [
			'return' => new stdClass(),
		]
	);

	\WP_Mock::userFunction(
		'wp_remote_request', [
			'return' => new stdClass(),
		]
	);

	\WP_Mock::userFunction(
		'wp_remote_retrieve_body', [
			'return' => json_encode( $rest_response ),
		]
	);

	\WP_Mock::userFunction(
		'wp_remote_retrieve_response_code', [
			'return' => 200,
		]
	);

	\WP_Mock::userFunction(
		'wp_list_filter', [
			'return' => [ new WP_Post( (object) $post_response ) ],
		]
	);
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
 * Mock wp_json_encode() function.
 *
 * @since x.x.x
 *
 * @param mixed $data Data to encode.
 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
 * @param int   $depth Optional. Maximum depth to walk through $data.
 * @return string|false The JSON encoded string, or false if it cannot be encoded.
 */
function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

/**
 * Mock wp_parse_args() function.
 *
 * @since x.x.x
 *
 * @param array $settings Array of arguments.
 * @param array $defaults Array of default arguments.
 * @return array Array of parsed arguments.
 */
function wp_parse_args( $settings, $defaults ) {
	return array_merge( $defaults, $settings );
}

/**
 * Mock absint() function.
 *
 * Copied from WordPress core.
 *
 * @since 2.0.0
 *
 * @param mixed $maybeint Data you wish to have converted to a non-negative integer.
 * @return int A non-negative integer.
 */
function absint( $maybeint ) {
	return abs( (int) $maybeint );
}

/**
 * Stub for remove_filter to avoid failure in test_remote_get()
 *
 * @return void
 */
function remove_filter() { }
