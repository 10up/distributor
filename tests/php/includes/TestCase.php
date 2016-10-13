<?php
/**
 * We will extend this test case to make WP_Mock set up easier
 */
class TestCase extends \PHPUnit_Framework_TestCase {
	
	/**
	 * Set up with WP_Mock
	 *
	 * @since  0.8
	 */
	public function setUp() {
		\WP_Mock::setUp();
		$this->setup_common();

		// Reset registered connections
		\Syndicate\Connections::factory()->connections = array();
	}

	/**
	 * Tear down with WP_Mock
	 *
	 * @since  0.8
	 */
	public function tearDown() {
		\WP_Mock::tearDown();
	}

	/**
	 * Mock common functions
	 * 
	 * @since 0.8
	 */
	public function setup_common() {
		\WP_Mock::userFunction( '__', array(
			'return_arg' => 0
		) );

		\WP_Mock::userFunction( 'esc_html__', array(
			'return_arg' => 0
		) );

		\WP_Mock::userFunction( 'esc_html_e', array(
			'return_arg' => 0
		) );

		\WP_Mock::userFunction( '_e', array(
			'return_arg' => 0
		) );
	}
}
