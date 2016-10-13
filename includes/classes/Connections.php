<?php

namespace Syndicate;

/**
 * This is a factory class for creating/registering connections
 */
class Connections {

	protected $internal_connections = array();
	protected $external_connections = array();

	/**
	 * This will act as a singleton
	 *
	 * @since  0.8
	 */
	public function __construct() { }

	/**
	 * Register a connection class for use
	 * 
	 * @param  string $class_name
	 * @param  string $type
	 * @since  0.8
	 */
	public function register( $class_name, $type = 'external' ) {
		if ( 'external' === $type ) {
			$this->external_connections[$class_name::$slug] = $class_name;
		} else {
			$this->internal_connections[$class_name::$slug] = $class_name;
		}
		
		$class_name::bootstrap();
	}

	/**
	 * Get registered connections. Note that these are classes not objects
	 *
	 * @param  string $type
	 * @since  0.8
	 * @return array
	 */
	public function get_registered( $type = null ) {
		if ( 'internal' === $type ) {
			$connections = $this->internal_connections;
		} elseif ( 'external' === $type ) {
			$connections = $this->external_connections;
		} else {
			$connections = array_merge( $this->internal_connections, $this->external_connections );
		}

		return $connections;
	}

	/**
	 * Singleton-ish class
	 *
	 * @since  0.8
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
