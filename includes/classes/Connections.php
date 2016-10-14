<?php

namespace Syndicate;

/**
 * This is a factory class for creating/registering connections
 */
class Connections {

	public $connections = array();

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
	 * @since  0.8
	 */
	public function register( $class_name ) {
		$this->connections[ $class_name::$slug ] = $class_name;

		$class_name::bootstrap();
	}

	/**
	 * Get registered connections. Note that these are classes not objects
	 *
	 * @since  0.8
	 * @return array
	 */
	public function get_registered() {
		return $this->connections;
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
