<?php

namespace Syndicate;

require_once( ABSPATH . '/wp-admin/includes/class-wp-posts-list-table.php' );

/**
 * External connection table shows all external connections
 */
class ExternalConnectionListTable extends \WP_Posts_List_Table {
	/**
	 * Create table
	 *
	 * @since  1.0
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
			'screen' => \WP_Screen::get( 'sy_ext_connection' ),
		) );
	}

	/**
	 * Only allow delete bulk action
	 *
	 * @since  1.0
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete'
		];

		return $actions;
	}
}