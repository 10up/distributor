<?php
/**
 * Sample Test
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;

/**
 * Test Plugin Readme and PHP Headers
 */
class Test_Sample extends WP_UnitTestCase {
	/**
	 * Ensure passing tests pass.
	 */
	public function test_passing_assertion() {
		global $wp_version;
		var_dump( getenv('WP_VERSION'), $wp_version );
		$this->assertTrue( true );
	}

	/**
	 * Ensure first post is titled Hello World
	 */
	public function test_get_post() {
		$post_id = $this->factory()->post->create();

		$this->assertInstanceOf( '\WP_Post', get_post( $post_id ) );
	}
}
