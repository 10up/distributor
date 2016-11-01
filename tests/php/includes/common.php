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
 * Hollowed out WP_List_Table class for mocking
 *
 * @since  0.8
 */
class WP_List_Table {
	public function __construct(){}
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
function remote_get_setup(){

	\WP_Mock::userFunction( 'get_option' );

	$post_type = 'post';
	$links = [
		'_links' => [
			'wp:items' => [
				['href' => 'http://url.com'],
			],
		]
	];

	\WP_Mock::userFunction( 'wp_remote_get', [
		'return' => json_encode( [
			$post_type => $links,
		] )
    ] );

    \WP_Mock::userFunction( 'wp_remote_retrieve_body', [
    	'return' => json_encode( [
			'id'           => 123,
			'title'        => ['rendered' => 'My post title'],
			'content'      => ['rendered' => '',],
			'date'         => '',
			'date_gmt'     => '',
			'guid'         => ['rendered' => '',],
			'modified'     => '',
			'modified_gmt' => '',
			'type'         => '',
			'link'         => '',
			$post_type     => $links,
		] )
    ] );
}

/**
 * Classes for testing connections
 */
class TestExternalConnection extends \Distributor\ExternalConnection {
	static $slug = 'test-external-connection';
	static $auth_handler_class = '\Distributor\Authentications\WordPressBasicAuth';

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
