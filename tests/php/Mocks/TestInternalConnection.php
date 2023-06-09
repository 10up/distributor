<?php

namespace Distributor\Tests\Mocks;

class TestInternalConnection extends \Distributor\Connection {
	static $slug = 'test-internal-connection';

	public function push( $item_id, $args = array() ) { }

	public function pull( $items ) { }

	public function remote_get( $args ) { }

	public function log_sync( array $item_id_mappings, $id, $overwrite ) {}

	public function get_sync_log( $id ) {}

	public function get_post_types() { }
}
