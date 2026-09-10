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

		$this->assertArrayHasKey( 'distributor', $info, 'Debug info should contain distributor.' );
		$this->assertArrayHasKey( 'label', $info['distributor'], 'Distributor info should include label.' );
		$this->assertArrayHasKey( 'fields', $info['distributor'], 'Distributor info should include fields' );
		$this->assertEquals( 'Distributor', $info['distributor']['label'], 'Distributor label should be Distributor.' );
		$this->assertEquals( 6, count( $info['distributor']['fields'] ), 'Debug info should include six fields.' );
		$this->assertSame( DT_VERSION, wp_list_filter( $info['distributor']['fields'], array( 'label' => 'Version' ) )[0]['value'], 'Reported version should match constant' );
	}
}
