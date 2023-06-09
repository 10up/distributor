<?php

namespace Distributor\Tests;

use Distributor\Tests\Utils\TestCase;

/**
 * @covers \Distributor\DebugInfo
 */
class DebugInfoTest extends TestCase {
	/**
	 * @covers \Distributor\DebugInfo\add_debug_info
	 */
	public function test_add_debug_info() {
		$info = \Distributor\DebugInfo\add_debug_info( [] );
		$this->assertArrayHasKey( 'distributor', $info );
		$this->assertArrayHasKey( 'label', $info['distributor'] );
		$this->assertArrayHasKey( 'fields', $info['distributor'] );
		$this->assertEquals( 'Distributor', $info['distributor']['label'] );
		$this->assertEquals( 6, count( $info['distributor']['fields'] ) );
	}
}
