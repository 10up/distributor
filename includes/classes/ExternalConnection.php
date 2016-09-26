<?php

namespace Syndicate;
use \Syndicate\Connection as Connection;

/**
 * External connections extend this base abstract class. External onnections are used to push and pull content. 
 * Note that static methods are used for interacting with the type whereas class instances 
 * deal with an actual connection.
 */
abstract class ExternalConnection extends Connection {

	public $name;

	public $base_url;

	public $id;

	public $auth_handler;

	public $mapping_handler;

	/**
	 * Initialize an external connection given a name, url, auth handler, and mapping handler
	 *
	 * @param string         $name
	 * @param string         $base_url
	 * @param Authentication $auth_handler
	 * @param Mapping        $mapping_handler
	 * @since  1.0
	 */
	public function __construct( string $name, string $base_url, int $id, Authentication $auth_handler, Mapping $mapping_handler ) {
		$this->name = $name;
		$this->id = $id;
		$this->base_url = $base_url;
		$this->auth_handler = $auth_handler;
		$this->mapping_handler = $mapping_handler;
	}

	/**
	 * Log statuses for sync posts
	 *
	 * @param  array|int $item_ids
	 * @param  string $status
	 * @since  1.0
	 */
	public function log_sync_statuses( $item_ids, string $status ) {
		if ( ! is_array( $item_ids ) ) {
			$item_ids = array( $item_ids );
		}

		$pull_statuses = get_post_meta( $this->id, 'sy_pull_statuses', true );

		if ( empty( $pull_statuses ) ) {
			$pull_statuses = array();
		}

		foreach ( $item_ids as $item_id ) {
			$pull_statuses[ $item_id ] = $status;
		}

		update_post_meta( $this->id, 'sy_pull_statuses', $pull_statuses );

		do_action( 'sy_log_sync_statuses', $item_ids, $status, $pull_statuses, $this );
	}

	/**
	 * Check push/pull connections for the external connection
	 *
	 * @since  1.0
	 * @return array
	 */
	public abstract function check_connections();

}