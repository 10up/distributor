<?php

namespace Distributor\Tests;

use Distributor\Connections;
use Distributor\ExternalConnection;
use Distributor\ExternalConnections\WordPressExternalConnection;
use Distributor\Tests\Mocks\TestExternalConnection;
use Distributor\Tests\Utils\ExternalConnectionPostGenerator;
use WP_Error;

class ExternalConnectionTest extends Utils\TestCase {

	/**
	 * Text External Connection instantiation failure on no type
	 *
	 * @since  0.8
	 * @group  ExternalConnection
	 */
	public function test_instantiate_fail_type(): void {
		/* @var WP_Error $external_connection */
		$external_connection = ExternalConnection::instantiate( 1 );

		$this->assertArrayHasKey( 'external_connection_not_found', $external_connection->errors );
	}

	/**
	 * Text External Connection instantiation failure on not registered
	 *
	 * @since  0.8
	 * @group  ExternalConnection
	 */
	public function test_instantiate_fail_not_registered(): void {
		$connectionId = ExternalConnectionPostGenerator::create(
			[], // Use default post data.
			[ 'dt_external_connection_type' => 'wp_non_existing_connection_type' ] // Set connection type.
		);

		/* @var WP_Error $external_connection */
		$external_connection = ExternalConnection::instantiate( $connectionId );

		$this->assertArrayHasKey( 'no_external_connection_type', $external_connection->errors );
	}

	/**
	 * Text External Connection instantiation success
	 *
	 * @since  0.8
	 * @group  ExternalConnection
	 */
	public function test_instantiate_success(): void {
		Connections::factory()->register( WordPressExternalConnection::class );
		$connectionId = ExternalConnectionPostGenerator::create();

		// Test non-existent connection ID
		$external_connection = ExternalConnection::instantiate( $connectionId );

		$this->assertTrue( is_a( $external_connection, ExternalConnection::class ) );
	}

	/**
	 * Text External Connection instantiation success
	 *
	 * @since  0.8
	 * @group  ExternalConnection
	 */
	public function test_instantiate_success_customer_connection_type(): void {
		Connections::factory()->register( TestExternalConnection::class );

		$connectionId = ExternalConnectionPostGenerator::create(
			[], // Use default post data.
			[ 'dt_external_connection_type' => TestExternalConnection::$slug ] // Set connection type.
		);

		// Test non-existent connection ID
		$external_connection = ExternalConnection::instantiate( $connectionId );

		$this->assertTrue( is_a( $external_connection, TestExternalConnection::class ) );
	}
}
