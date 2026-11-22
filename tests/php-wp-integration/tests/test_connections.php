<?php
/**
 * Connections registry tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use Distributor\Connections;

/**
 * Integration tests for the Connections registry.
 *
 * @group Connections
 */
class Test_Connections extends WP_UnitTestCase {
	/**
	 * Tear down.
	 *
	 * The registry is a singleton, so remove the test connections to avoid
	 * leaking them into other tests.
	 */
	public function tear_down() {
		unset(
			Connections::factory()->connections[ Test_External_Connection::$slug ],
			Connections::factory()->connections[ Test_Internal_Connection::$slug ]
		);
		parent::tear_down();
	}

	/**
	 * Test the plugin registers its own connection types.
	 */
	public function test_default_connections_registered() {
		$registered = Connections::factory()->get_registered();

		$this->assertSame( '\Distributor\ExternalConnections\WordPressExternalConnection', $registered['wp'] ?? null, 'WordPress external connection should be registered.' );
		$this->assertSame( '\Distributor\ExternalConnections\WordPressDotcomExternalConnection', $registered['wpdotcom'] ?? null, 'WordPress.com external connection should be registered.' );
		$this->assertSame( '\Distributor\InternalConnections\NetworkSiteConnection', $registered['networkblog'] ?? null, 'Network site connection should be registered on multisite.' );
	}

	/**
	 * Test connection registration.
	 */
	public function test_register() {
		$external_class = '\\' . Test_External_Connection::class;
		$internal_class = '\\' . Test_Internal_Connection::class;

		Connections::factory()->register( $external_class );
		$this->assertSame( $external_class, Connections::factory()->get_registered()['test-external-connection'] ?? null, 'External connection should be registered by slug.' );

		Connections::factory()->register( $internal_class );
		$this->assertSame( $internal_class, Connections::factory()->get_registered()['test-internal-connection'] ?? null, 'Internal connection should be registered by slug.' );
	}
}
