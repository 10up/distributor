<?php
/**
 * Minimal external connection for testing.
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use Distributor\ExternalConnection;

/**
 * External connection stub implementing the abstract methods as no-ops.
 */
class Test_External_Connection extends ExternalConnection {
	/**
	 * Connection slug.
	 *
	 * @var string
	 */
	public static $slug = 'test-external-connection';

	/**
	 * Auth handler class.
	 *
	 * @var string
	 */
	public static $auth_handler_class = '\Distributor\Authentications\WordPressBasicAuth';

	/**
	 * REST API namespace.
	 *
	 * @var string
	 */
	public static $namespace = 'wp/v2';

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
	 * Check the connection.
	 */
	public function check_connections() {}

	/**
	 * Get remote content.
	 *
	 * @param array $args Query arguments.
	 */
	public function remote_get( $args ) {}

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
