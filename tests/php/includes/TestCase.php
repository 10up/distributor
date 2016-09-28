<?php
/**
 * We will extend this test case to make WP_Mock set up easier
 */
class TestCase extends \PHPUnit_Framework_TestCase {
	
	/**
	 * Set up with WP_Mock
	 *
	 * @since  1.0
	 */
	public function setUp() {
		\WP_Mock::setUp();
	}

	/**
	 * Tear down with WP_Mock
	 *
	 * @since  1.0
	 */
	public function tearDown() {
		\WP_Mock::tearDown();
	}
}
