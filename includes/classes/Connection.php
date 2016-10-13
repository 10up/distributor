<?php

namespace Syndicate;

/**
 * Connections let us push and pull content from other areas
 */
abstract class Connection {

	/**
	 * Push an item to a external connection
	 * 
	 * @param  int $item_id
	 * @param  array $args
	 * @since  0.8
	 * @return bool|WP_Error
	 */
	public abstract function push( $item_id, $args = array() );

	/**
	 * Pull items
	 * 
	 * @param  array $items
	 * @since  0.8
	 * @return bool|WP_Error
	 */
	public abstract function pull( $items );

	/**
	 * Get content from a connection
	 * 
	 * @param  array $args
	 * @since  0.8
	 * @return array|WP_Error
	 */
	public abstract function remote_get( $args );

	/**
	 * Log a sync
	 *
	 * @param  array $item_id_mappings
	 * @since  0.8
	 */
	public abstract function log_sync( array $item_id_mappings );

	/**
	 * This method is called on every page load. It's helpful for canonicalization
	 * 
	 * @since  0.8
	 */
	static function bootstrap() {
		// Extend me?
	}
}
