<?php
/**
 * External connection base
 *
 * @package  distributor
 */

namespace Distributor;

use \Distributor\Connection as Connection;

/**
 * External connections extend this base abstract class. External connections are used to push and pull content.
 * Note that static methods are used for interacting with the type whereas class instances
 * deal with an actual connection.
 */
abstract class ExternalConnection extends Connection {

	/**
	 * Title of external connection
	 *
	 * @var string
	 */
	public $name;

	/**
	 * API url for external connection
	 *
	 * @var string
	 */
	public $base_url;

	/**
	 * External connection ID
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Auth handler class
	 *
	 * @var Authentication
	 */
	public $auth_handler;

	/**
	 * Initialize an external connection given a name, url, auth handler, and mapping handler
	 *
	 * @param string         $name Pretty name of connection.
	 * @param string         $base_url URL to connection API.
	 * @param int            $id ID of external connection.
	 * @param Authentication $auth_handler Auth handler class.
	 * @since  0.8
	 */
	public function __construct( $name, $base_url, $id, Authentication $auth_handler ) {
		$this->name         = $name;
		$this->id           = $id;
		$this->base_url     = $base_url;
		$this->auth_handler = $auth_handler;
	}

	/**
	 * Log a sync.
	 *
	 * {
	 *  old_post_id: new_post_id (false means skipped)
	 * }
	 *
	 * This let's us grab all the IDs of posts we've PULLED from a given connection
	 *
	 * @param array   $item_id_mappings Mapping array to store; key = origin post ID, value = new post ID.
	 * @param int     $connection_id Connection ID.
	 * @param boolean $overwrite Whether to overwrite the sync log for this connection. Default false.
	 * @since 0.8
	 */
	public function log_sync( array $item_id_mappings, $connection_id = 0, $overwrite = false ) {
		$connection_id = 0 === $connection_id ? $this->id : $connection_id;

		$sync_log = $this->get_sync_log( $connection_id );

		if ( true === $overwrite ) {
			$sync_log = array();
		}

		foreach ( $item_id_mappings as $old_item_id => $new_item_id ) {
			if ( empty( $new_item_id ) ) {
				$sync_log[ $old_item_id ] = false;
			} else {
				$sync_log[ $old_item_id ] = (int) $new_item_id;
			}
		}

		update_post_meta( $connection_id, 'dt_sync_log', $sync_log );

		/**
		 * Action fired when a sync is being logged.
		 *
		 * @since 1.0
		 * @hook dt_log_sync
		 *
		 * @param {array} $item_id_mappings Item ID mappings.
		 * @param {array} $sync_log The sync log
		 * @param {object} $this The current connection class.
		 */
		do_action( 'dt_log_sync', $item_id_mappings, $sync_log, $this );
	}

	/**
	 * Return the sync log for a specific connection
	 *
	 * @param int $connection_id Connection ID.
	 * @return array
	 */
	public function get_sync_log( $connection_id = 0 ) {
		$connection_id = 0 === $connection_id ? $this->id : $connection_id;

		$sync_log = get_post_meta( $connection_id, 'dt_sync_log', true );

		if ( empty( $sync_log ) ) {
			$sync_log = [];
		}

		return $sync_log;
	}

	/**
	 * Check push/pull connections for the external connection
	 *
	 * @since  0.8
	 * @return array
	 */
	abstract public function check_connections();

	/**
	 * This is a static factory method for initializing an external connection
	 *
	 * @param  int|WP_Post $external_connection External connection reference.
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
