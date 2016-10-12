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

	/**
	 * Initialize an external connection given a name, url, auth handler, and mapping handler
	 *
	 * @param string         $name
	 * @param string         $base_url
	 * @param Authentication $auth_handler
	 * @param Mapping        $mapping_handler
	 * @since  0.8
	 */
	public function __construct( $name, $base_url, $id, Authentication $auth_handler ) {
		$this->name = $name;
		$this->id = $id;
		$this->base_url = $base_url;
		$this->auth_handler = $auth_handler;
	}

	/**
	 * Log a sync
	 *
	 * @param  array $item_id_mappings
	 * @since  0.8
	 */
	public function log_sync( array $item_id_mappings ) {
		$sync_log = get_post_meta( $this->id, 'sy_sync_log', true );

		if ( empty( $sync_log ) ) {
			$sync_log = array();
		}

		foreach ( $item_id_mappings as $old_item_id => $new_item_id ) {
			if ( empty( $new_item_id ) ) {
				$sync_log[ $old_item_id ] = false;
			} else {
				$sync_log[ $old_item_id ] = (int) $new_item_id;
			}
		}

		update_post_meta( $this->id, 'sy_sync_log', $sync_log );

		do_action( 'sy_log_sync', $item_id_mappings, $sync_log, $this );
	}

	/**
	 * Check push/pull connections for the external connection
	 *
	 * @since  0.8
	 * @return array
	 */
	public abstract function check_connections();

}
