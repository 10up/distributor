<?php

namespace Distributor;
use \Distributor\Connection as Connection;

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
		$this->name         = $name;
		$this->id           = $id;
		$this->base_url     = $base_url;
		$this->auth_handler = $auth_handler;
	}

	/**
	 * Log a sync
	 *
	 * @param  array $item_id_mappings
	 * @since  0.8
	 */
	public function log_sync( array $item_id_mappings ) {
		$sync_log = get_post_meta( $this->id, 'dt_sync_log', true );

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

		update_post_meta( $this->id, 'dt_sync_log', $sync_log );

		do_action( 'dt_log_sync', $item_id_mappings, $sync_log, $this );
	}

	/**
	 * Check push/pull connections for the external connection
	 *
	 * @since  0.8
	 * @return array
	 */
	public abstract function check_connections();

	/**
	 * This is a static factory method for initializing an external connection
	 *
	 * @param  int|WP_Post $external_connection
	 * @since  0.8
	 * @return Connection
	 */
	public static function instantiate( $external_connection ) {
		$external_connection_id = $external_connection;

		if ( is_object( $external_connection_id ) && ! empty( $external_connection_id->ID ) ) {
			$external_connection_id = $external_connection_id->ID;
		}

		$type = get_post_meta( $external_connection_id, 'dt_external_connection_type', true );
		$url  = get_post_meta( $external_connection_id, 'dt_external_connection_url', true );
		$auth = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
		$name = get_the_title( $external_connection_id );

		if ( empty( $type ) || empty( $url ) ) {
			return new \WP_Error( 'external_connection_not_found', esc_html__( 'External connection not found.', 'distributor' ) );
		}

		$connections = \Distributor\Connections::factory()->get_registered( 'external' );

		if ( empty( $connections[ $type ] ) ) {
			return new \WP_Error( 'no_external_connection_type', esc_html__( 'External connection type is not registered.', 'distributor' ) );
		}

		$connection_class = $connections[ $type ];

		$auth_handler = new $connection_class::$auth_handler_class( $auth );

		return new $connection_class( $name, $url, $external_connection_id, $auth_handler );
	}
}
