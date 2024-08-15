<?php

namespace Distributor\Tests;

use Distributor\Tests\Utils\TestCase;
use Distributor\Utils;

class UtilsTest extends TestCase {

	/**
	 * Test set meta with string value and array value
	 *
	 * @since  1.0
	 * @group  Utils
	 */
	public function test_set_meta_simple(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set meta with multiple values
	 *
	 * @since  1.0
	 * @group  Utils
	 */
	public function test_set_meta_multi(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set meta with serialized value
	 *
	 * @since  1.0
	 * @group  Utils
	 */
	public function test_set_meta_serialize(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and term
	 *
	 * @since 1.0
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_simple(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and non existing term
	 *
	 * @since 1.0
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_create_term(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set taxonomy terms with non existing taxonomy
	 *
	 * @since 1.0
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_no_taxonomy(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test format media with feature
	 *
	 * @since 1.0
	 * @group Utils
	 */
	public function test_format_media_featured(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test format media with no `_wp_attachment_metadata`
	 *
	 * @group Utils
	 */
	public function test_format_media_no_attachment_meta(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test set media
	 *
	 * @since 1.0
	 * @group Utils
	 */
	public function test_set_media(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Todo finish test_set_media
	 */

	/**
	 * Todo finish process_media
	 */

	/**
	 * Test post_args_allow_list
	 *
	 * @since 1.7.0
	 */
	public function test_post_args_allow_list(): void {
		$post_args = [
			'post_title'   => 'Test Title',
			'post_type'    => 'post',
			'post_content' => 'Test Content',
			'post_excerpt' => 'Test Excerpt',
			'link'         => 'https://github.com/10up/distributor/issues/879',
			'dt_source'    => 'https://github.com/10up/distributor/pull/895',
		];

		$expected = [
			'post_title'   => 'Test Title',
			'post_type'    => 'post',
			'post_content' => 'Test Content',
			'post_excerpt' => 'Test Excerpt',
		];

		$actual = Utils\post_args_allow_list( $post_args );
		$this->assertSame( $expected, $actual );
	}
}
