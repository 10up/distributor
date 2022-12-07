<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;

class DistributorPostTest extends TestCase {

	/**
	 * Set up with WP_Mock
	 *
	 * Set up common mocks required for multiple tests.
	 *
	 * @since x.x.x
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_common();
	}

	public function setup_common() {
		\WP_Mock::userFunction(
			'get_post',
			array(
				'return' => (object) array(
					'ID' => 1,
					'post_title' => 'Test Post',
					'post_content' => 'Test Content',
					'post_excerpt' => 'Test Excerpt',
					'post_status' => 'publish',
					'post_type' => 'post',
					'post_author' => 1,
					'post_date' => '2020-01-01 00:00:00',
					'post_date_gmt' => '2020-01-01 00:00:00',
					'post_modified' => '2020-01-01 00:00:00',
					'post_modified_gmt' => '2020-01-01 00:00:00',
					'post_parent' => 0,
					'post_mime_type' => '',
					'comment_count' => 0,
					'comment_status' => 'open',
					'ping_status' => 'open',
					'guid' => 'http://example.org/?p=1',
					'menu_order' => 0,
					'pinged' => '',
					'to_ping' => '',
					'post_password' => '',
					'post_name' => 'test-post',
					'post_content_filtered' => '',
				),
			)
		);
	}
}
