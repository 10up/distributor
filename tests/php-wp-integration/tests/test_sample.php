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
	 * Ensure failing tests fail.
	 */
	public function test_failing_assertion() {
		$this->assertFalse( true );
	}

	/**
	 * Ensure first post is titled Hello World
	 */
	public function test_get_post() {
		$this->assertNotFalse( get_post( 1 ) );
		$this->assertSame( 'Hello World!', get_the_title( 1 ) );
	}
}
