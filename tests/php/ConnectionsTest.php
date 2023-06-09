<?php

namespace Distributor\Tests;

use Distributor\Connections;
use Distributor\Tests\Mocks\TestExternalConnection;
use Distributor\Tests\Mocks\TestInternalConnection;
use Distributor\Tests\Utils\TestCase;

class ConnectionsTest extends TestCase {
	/**
	 * Test connection registration
	 *
	 * @since 0.8
	 * @group Connections
	 */
	public function test_register() {
		Connections::factory()->register( TestExternalConnection::class );

		$this->assertEquals( TestExternalConnection::class, Connections::factory()->get_registered()['test-external-connection'] );

		Connections::factory()->register( TestInternalConnection::class, 'internal' );

		$this->assertEquals( TestInternalConnection::class, Connections::factory()->get_registered()['test-internal-connection'] );
	}
}
