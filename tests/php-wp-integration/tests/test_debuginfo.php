<?php
/**
 * DebugInfo Test
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use Distributor\DebugInfo;
use WP_UnitTestCase;

/**
 * Integration tests for includes/debug-info.php
 */
class Test_DebugInfo extends WP_UnitTestCase {
	public function test_add_debug_info() {
		$info = DebugInfo\add_debug_info( [] );
		$this->assertArrayHasKey( 'distributor', $info );
		$this->assertArrayHasKey( 'label', $info['distributor'] );
		$this->assertArrayHasKey( 'fields', $info['distributor'] );
		$this->assertEquals( 'Distributor', $info['distributor']['label'] );
		$this->assertEquals( 6, count( $info['distributor']['fields'] ) );
	}
}
