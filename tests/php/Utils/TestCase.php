<?php

namespace Distributor\Tests\Utils;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as BaseTestCase;

/**
 * We will extend this test case to make WP_Mock set up easier
 */
class TestCase extends BaseTestCase {

	/**
	 * Set up with WP_Mock
	 *
	 * @since  0.8
	 */
	public function set_up() {
		// Reset registered connections
		\Distributor\Connections::factory()->connections = array();
	}
}
