<?php

namespace Distributor\Tests;

use Distributor\DistributorPost;
use Distributor\Tests\Utils\TestCase;

/**
 * Tests for the DistributorPost class.
 *
 * @covers \Distributor\DistributorPost
 * @group  Post
 */
class DistributorPostTest extends TestCase {
	/**
	 * Test the DistributorPost object for public methods.
	 *
	 * Only magic methods are expected to be public as the class uses the
	 * __call() method to handle all other methods.
	 *
	 * @covers ::__construct
	 */
	public function test_public_methods(): void {
		$actual_methods   = get_class_methods( DistributorPost::class );
		$expected_methods = array(
			'__construct',
			'__call',
			'__get',
			'__isset',
		);

		sort( $actual_methods );
		sort( $expected_methods );
		$this->assertSame( $expected_methods, $actual_methods, 'Only magic methods are expected to be public.' );
	}

	/**
	 * Test the DistributorPost object for internal connections.
	 *
	 * @covers ::__construct
	 */
	public function test_internal_connection(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers ::__construct
	 */
	public function test_external_connection_with_pushed_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers ::__construct
	 */
	public function test_external_connection_with_pulled_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the DistributorPost object a source post.
	 *
	 * @covers ::__construct
	 */
	public function test_source_post(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_the_id() method.
	 *
	 * @covers ::get_the_id
	 */
	public function test_get_the_id(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_permalink() method.
	 *
	 * @covers ::get_permalink
	 */
	public function test_get_permalink(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_post_type() method.
	 *
	 * @covers ::get_post_type
	 */
	public function test_get_post_type(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_post_thumbnail_id() method.
	 *
	 * @covers ::get_post_thumbnail_id
	 */
	public function test_get_post_thumbnail_id(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_post_thumbnail_url() method.
	 *
	 * @covers ::get_post_thumbnail_url
	 */
	public function test_get_post_thumbnail_url(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_the_post_thumbnail() method.
	 *
	 * @covers ::get_the_post_thumbnail
	 */
	public function test_get_the_post_thumbnail(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_meta() method.
	 *
	 * @covers ::get_meta
	 */
	public function test_get_meta(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_terms() method.
	 *
	 * @covers ::get_terms
	 */
	public function test_get_terms(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_media() method with blocks.
	 *
	 * @covers ::get_media
	 * @covers ::parse_media_blocks
	 * @covers ::parse_blocks_for_attachment_id
	 */
	public function test_get_media_with_blocks(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test the get_media() method with attachments.
	 *
	 * This tests the legacy fork of the method which is still used by posts
	 * authored in the classic editor.
	 *
	 * @covers ::get_media()
	 */
	public function test_get_media_with_attachments(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test methods for formatting the post data without blocks.
	 *
	 * @covers ::post_data()
	 * @covers ::to_insert()
	 * @covers ::to_json()
	 */
	public function test_post_data_without_blocks(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test methods for formatting the post data with blocks.
	 *
	 * @covers ::post_data()
	 * @covers ::to_insert()
	 * @covers ::to_json()
	 */
	public function test_post_data_with_blocks(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url_unlinked(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url_source(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name_without_override(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name_unlinked(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name_source(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link_without_override(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link_unlinked(): void {
		$this->markTestIncomplete();
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link_source(): void {
		$this->markTestIncomplete();
	}
}
