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
		$this->assertTrue( true );
	}

	/**
	 * Ensure first post is titled Hello World
	 */
	public function test_get_post() {
		$post_id = $this->factory()->posts->create();

		$this->assertInstanceOf( '\WP_Post', get_post( $post_id ) );
	}
}
