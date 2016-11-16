<?php

namespace Distributor;

class ConnectionsTest extends \TestCase {
	/**
	 * Test connection registration
	 *
	 * @since 0.8
	 * @group Connections
	 */
	public function test_register() {
		Connections::factory()->register( '\TestExternalConnection' );

		$this->assertEquals( '\TestExternalConnection', Connections::factory()->get_registered()['test-external-connection'] );

		Connections::factory()->register( '\TestInternalConnection', 'internal' );

		$this->assertEquals( '\TestInternalConnection', Connections::factory()->get_registered()['test-internal-connection'] );
	}
}
