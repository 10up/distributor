<?php

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
		\WP_Mock::setUp();
		$this->setup_common();

		// Reset registered connections
		\Distributor\Connections::factory()->connections = array();
	}

	/**
	 * Tear down with WP_Mock
	 *
	 * @since  0.8
	 */
	public function tear_down() {
		\WP_Mock::tearDown();
	}

	/**
	 * Mock common functions
	 *
	 * @since 0.8
	 */
	public function setup_common() {
		\WP_Mock::userFunction(
			'__', array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'esc_html__', array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'esc_html_e', array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'_e', array(
				'return_arg' => 0,
			)
		);
	}
}
