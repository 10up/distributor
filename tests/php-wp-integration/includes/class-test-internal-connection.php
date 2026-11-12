<?php
/**
 * Minimal internal connection for testing.
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use Distributor\Connection;

/**
 * Internal connection stub implementing the abstract methods as no-ops.
 */
class Test_Internal_Connection extends Connection {
	/**
	 * Connection slug.
	 *
	 * @var string
	 */
	public static $slug = 'test-internal-connection';

	/**
	 * Push an item.
	 *
	 * @param int   $item_id Item ID.
	 * @param array $args    Push arguments.
	 */
	public function push( $item_id, $args = array() ) {}

	/**
	 * Pull items.
	 *
	 * @param array $items Items to pull.
	 */
	public function pull( $items ) {}

	/**
	 * Get remote content.
	 *
	 * @param array $args Query arguments.
	 */
	public function remote_get( $args ) {}

	/**
	 * Log a sync.
	 *
	 * @param array $item_id_mappings Item ID mappings.
	 * @param int   $id               Connection ID.
	 * @param bool  $overwrite        Whether to overwrite the log.
	 */
	public function log_sync( array $item_id_mappings, $id, $overwrite ) {}

	/**
	 * Get the sync log.
	 *
	 * @param int $id Connection ID.
	 */
	public function get_sync_log( $id ) {}

	/**
	 * Get the available post types.
	 */
	public function get_post_types() {}

	/**
	 * Get the taxonomies for a post type.
	 *
	 * @param string $post_type Post type.
	 */
	public function get_post_type_taxonomies( $post_type ) {}

	/**
	 * Get the taxonomy terms.
	 */
	public function get_taxonomy_terms() {}
}
