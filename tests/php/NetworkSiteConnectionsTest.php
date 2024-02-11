<?php

namespace Distributor\Tests;

use Distributor\InternalConnections\NetworkSiteConnection;
use Distributor\Tests\Utils\TestCase;

class NetworkSiteConnectionsTest extends TestCase {
	public \WP_Site $site_obj;
	public NetworkSiteConnection $connection_obj;

	/**
	 * Push returns an post ID on success instance of WP Error on failure.
	 *
	 * @since  0.8
	 * @group  NetworkSiteConnection
	 */
	public function test_push(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Pull returns an array of Post IDs on success. This test simulates sending an
	 * array containing three IDs (integers) will receive an array containing
	 * three integers.
	 *
	 * @since  0.8
	 * @group  NetworkSiteConnection
	 */
	public function test_pull() {
		$this->markTestIncomplete();
	}

	/**
	 * Verifies that when passed no id the request can still return items
	 *
	 * @since 0.8
	 * @group NetworkSiteConnection
	 */
	public function test_remote_get_empty_id(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Verifies that the remote_get method returns an array containing the post title.
	 *
	 * @since 0.8
	 * @group NetworkSiteConnection
	 */
	public function test_remote_get(): void {
		$this->markTestIncomplete();
	}

}
