<?php

namespace Syndicate;

/**
 * This is a factory class for creating/registering external connections
 */
class ExternalConnections {
	protected $external_connections = array();

	/**
	 * This will act as a singleton
	 *
	 * @since  1.0
	 */
	public function __construct() { }

	/**
	 * Register a external connection class for use
	 * 
	 * @param  string $class_name
	 * @since  1.0
	 */
	public function register( $class_name ) {
		$this->external_connections[$class_name::$slug] = $class_name;
	}

	/**
	 * Get registered external connections. Note that this are classes not objects
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get_registered() {
		return $this->external_connections;
	}

	/**
	 * This is a factory method for initializing an external connection
	 * 
	 * @param  int|WP_Post $external_connection
	 * @since  1.0
	 * @return Connection
	 */
	public function instantiate( $external_connection ) {
		$external_connection_id = $external_connection;

		if ( is_object( $external_connection_id ) && ! empty( $external_connection_id->ID ) ) {
			$external_connection_id = $connection_id->ID;
		}

		$type = get_post_meta( $external_connection_id, 'sy_external_connection_type', true );
		$url = get_post_meta( $external_connection_id, 'sy_external_connection_url', true );
		$auth = get_post_meta( $external_connection_id, 'sy_external_connection_auth', true );
		$name = get_the_title( $external_connection_id );

		if ( empty( $type ) || empty( $url ) ) {
			return new \WP_Error( 'external_connection_not_found', esc_html__( 'External connection not found.', 'syndicate' ) );
		}

		if ( empty( $this->external_connections[$type] ) ) {
			return new \WP_Error( 'no_external_connection_type', esc_html__( 'External connection type is not registered.', 'syndicate' ) );
		}

		$auth_handler = new $this->external_connections[$type]::$auth_handler_class( $auth );
		$mapping_handler = new $this->external_connections[$type]::$mapping_handler_class();

		return new $this->external_connections[$type]( $name, $url, $external_connection_id, $auth_handler, $mapping_handler );
	}

	/**
	 * Singleton-ish class
	 *
	 * @since  1.0
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}