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
	/**
	 * Ensure default debug info is populated.
	 */
	public function test_add_debug_info() {
		$info = DebugInfo\add_debug_info( [] );

		$version = wp_list_filter( $info['distributor']['fields'], array( 'label' => 'Version' ) );
		$version = reset( $version )['value'];

		$internal_sites = wp_list_filter( $info['distributor']['fields'], array( 'label' => 'Internal Connections' ) );
		$internal_sites = reset( $internal_sites )['value'];

		$this->assertArrayHasKey( 'distributor', $info, 'Debug info should contain distributor.' );
		$this->assertArrayHasKey( 'label', $info['distributor'], 'Distributor info should include label.' );
		$this->assertArrayHasKey( 'fields', $info['distributor'], 'Distributor info should include fields' );
		$this->assertEquals( 'Distributor', $info['distributor']['label'], 'Distributor label should be Distributor.' );
		$this->assertEquals( 6, count( $info['distributor']['fields'] ), 'Debug info should include six fields.' );
		$this->assertSame( DT_VERSION, $version, 'Reported version should match constant' );
		$this->assertSame( 'N/A', $internal_sites, 'No internal connections should exist.' );
	}

	/**
	 * Ensure sub-sites are shown in the debug info.
	 */
	public function test_debug_info_for_multiple_sites() {
		wp_set_current_user( 1 );
		$site = $this->factory()->blog->create_and_get();
		$info = DebugInfo\add_debug_info( [] );

		$internal_sites = wp_list_filter( $info['distributor']['fields'], array( 'label' => 'Internal Connections' ) );
		$internal_sites = reset( $internal_sites )['value'];

		$site_url = ( is_ssl() ? 'https' : 'http' ) . "://{$site->domain}{$site->path}";
		$site_url = untrailingslashit( $site_url );

		$expected = "\n- Blog ID: {$site->blog_id}\n- URL: {$site_url}\n- Registered: {$site->registered}\n- Last updated: {$site->last_updated}";
		$actual   = reset( $internal_sites );

		$this->assertIsArray( $internal_sites, 'Internal Sites should be an array' );
		$this->assertSame( $expected, $actual, 'Internal Site data should match registered site.' );
		$this->assertArrayHasKey( get_blog_option( $site->blog_id, 'blogname' ), $internal_sites, 'Internal sites should be keyed by name' );
	}
}
