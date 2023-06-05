<?php
/**
 * Connection base class
 *
 * @package  distributor
 */

namespace Distributor;

/**
 * Connections let us push and pull content from other areas
 */
abstract class Connection {

	/**
	 * Push an item to a external connection
	 *
	 * @param  int   $item_id Item ID to push if an update.
	 * @param  array $args Array of args to push.
	 * @since  0.8
	 * @return array|\WP_Error
	 */
	abstract public function push( $item_id, $args = array() );

	/**
	 * Pull items
	 *
	 * @param  array $items Array of items to pull.
	 * @since  0.8
	 * @return bool|WP_Error
	 */
	abstract public function pull( $items );

	/**
	 * Get content from a connection
	 *
	 * @param  array $args Query args for getting.
	 * @since  0.8
	 * @return array|WP_Error
	 */
	abstract public function remote_get( $args );

	/**
	 * Log a sync
	 *
	 * @param array   $item_id_mappings Mapping to store; key = origin post ID, value = new post ID.
	 * @param int     $id Blog or Connection ID. Optional.
	 * @param boolean $overwrite Whether to overwrite the sync log. Optional.
	 * @since 0.8
	 */
	abstract public function log_sync( array $item_id_mappings, $id, $overwrite );

	/**
	 * Get the sync log
	 *
	 * @param int $id Blog or Connection ID. Optional.
	 */
	abstract public function get_sync_log( $id );

	/**
	 * Get available post types from a connection
	 *
	 * @since 1.3
	 * @return array|\WP_Error
	 */
	abstract public function get_post_types();

	/**
	 * This method is called on every page load. It's helpful for canonicalization
	 *
	 * @since  0.8
	 */
	public static function bootstrap() {
		// Extend me?
	}
}
