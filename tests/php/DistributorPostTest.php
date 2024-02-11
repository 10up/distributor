<?php

namespace Distributor\Tests;

use Distributor\DistributorPost;
use Distributor\Tests\Utils\PostGenerator;

use function Distributor\Utils\excluded_meta;

/**
 * Tests for the DistributorPost class.
 *
 * @covers \Distributor\DistributorPost
 * @group  Post
 */
class DistributorPostTest extends \WP_UnitTestCase {
	/**
	 * Test the DistributorPost object for public methods.
	 *
	 * Only magic methods are expected to be public as the class uses the
	 * __call() method to handle all other methods.
	 *
	 * @covers \Distributor\DistributorPost::__construct
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
	 * @covers       \Distributor\DistributorPost::__construct
	 * @dataProvider internal_connection_data_provider
	 */
	public function test_internal_connection( $data ): void {
		$dt_post = new DistributorPost(
			( new PostGenerator() )
				->create()
				->withMeta( array(
					'dt_original_post_id'  => $data->post->ID,
					'dt_original_blog_id'  => $data->blog->blog_id,
					'dt_syndicate_time'    => time(),
					'dt_original_post_url' => $data->post_url,
				) )
				->getPost()
		);

		$this->assertSame(
			$data->post->ID,
			$dt_post->original_post_id,
			'Origin post ID does not match expected value.'
		);
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame(
			$data->post_url,
			$dt_post->original_post_url,
			'Origin post URL does not match expected value.'
		);
		$this->assertEquals(
			$data->blog->blog_id,
			$dt_post->connection_id,
			'Origin site ID does not match expected value.'
		);
		$this->assertSame(
			'bidirectional',
			$dt_post->connection_direction,
			'Connection direction does not match expected value.'
		);
		$this->assertSame( 'internal', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame(
			$data->blog->home,
			$dt_post->source_site['home_url'],
			'Original home_url does not match expected value.'
		);
		$this->assertSame(
			$data->blog->blogname,
			$dt_post->source_site['name'],
			'Original site name does not match expected value.'
		);
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers       \Distributor\DistributorPost::__construct
	 * @dataProvider internal_connection_data_provider
	 */
	public function test_external_connection_with_pushed_post( $data ): void {
		$dt_post = new DistributorPost(
			( new PostGenerator() )
				->create()
				->withMeta( array(
					'dt_original_post_id'       => $data->post->ID,
					'dt_original_site_name'     => $data->blog->blogname,
					'dt_original_site_url'      => $data->blog->home,
					'dt_original_post_url'      => $data->post_url,
					'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
					'dt_syndicate_time'         => time(),
					'dt_full_connection'        => '1',
					'dt_original_source_id'     => $data->blog->id,
				) )
				->getPost()
		);

		$this->assertSame( $data->post->ID,
			$dt_post->original_post_id,
			'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( $data->post_url,
			$dt_post->original_post_url,
			'Origin post URL does not match expected value.' );
		$this->assertSame( $data->blog->home,
			$dt_post->connection_id,
			'Origin connection ID does not match source URL.' );
		$this->assertSame( 'pushed',
			$dt_post->connection_direction,
			'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( $data->blog->home,
			$dt_post->source_site['home_url'],
			'Original home_url does not match expected value.' );
		$this->assertSame( $data->blog->blogname,
			$dt_post->source_site['name'],
			'Original site name does not match expected value.' );
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers       \Distributor\DistributorPost::__construct
	 * @dataProvider internal_connection_data_provider
	 */
	public function test_external_connection_with_pulled_post( $data ): void {
		$dt_post = new DistributorPost(
			( new PostGenerator() )
				->create()
				->withMeta( array(
					'dt_original_post_id'       => $data->post->ID,
					'dt_original_site_name'     => $data->blog->blogname,
					'dt_original_site_url'      => $data->blog->home,
					'dt_original_post_url'      => $data->post_url,
					'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
					'dt_syndicate_time'         => time(),
					'dt_full_connection'        => '',
					'dt_original_source_id'     => $data->blog->id,
				) )
				->getPost()
		);

		$this->assertSame( $data->post->ID,
			$dt_post->original_post_id,
			'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( $data->post_url,
			$dt_post->original_post_url,
			'Origin post URL does not match expected value.' );
		$this->assertSame( $data->blog->id,
			$dt_post->connection_id,
			'Origin connection ID does not match source URL.' );
		$this->assertSame( 'pulled',
			$dt_post->connection_direction,
			'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( $data->blog->home,
			$dt_post->source_site['home_url'],
			'Original home_url does not match expected value.' );
		$this->assertSame( $data->blog->blogname,
			$dt_post->source_site['name'],
			'Original site name does not match expected value.' );
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object a source post.
	 *
	 * @covers \Distributor\DistributorPost::__construct
	 */
	public function test_source_post(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( $post->ID, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 0, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame(
			home_url(),
			$dt_post->source_site['home_url'],
			'Original home_url does not match expected value.'
		);
		$this->assertSame(
			get_bloginfo( 'name' ),
			$dt_post->source_site['name'],
			'Original site name does not match expected value.'
		);
		$this->assertTrue( $dt_post->is_source, 'Post is incorrectly marked as distributed.' );
	}

	/**
	 * Test the get_the_id() method.
	 *
	 * @covers \Distributor\DistributorPost::get_the_id
	 */
	public function test_get_the_id(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( $post->ID, $dt_post->get_the_id() );
	}

	/**
	 * Test the get_permalink() method.
	 *
	 * @covers \Distributor\DistributorPost::get_permalink
	 */
	public function test_get_permalink(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( get_permalink( $post ), $dt_post->get_permalink() );
	}

	/**
	 * Test the get_post_type() method.
	 *
	 * @covers \Distributor\DistributorPost::get_post_type
	 */
	public function test_get_post_type(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( get_post_type( $post ), $dt_post->get_post_type() );
	}

	/**
	 * Test the get_post_thumbnail_id() method.
	 *
	 * @covers \Distributor\DistributorPost::get_post_thumbnail_id
	 */
	public function test_get_post_thumbnail_id(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( get_post_thumbnail_id( $post ), $dt_post->get_post_thumbnail_id() );
	}

	/**
	 * Test the get_post_thumbnail_url() method.
	 *
	 * @covers \Distributor\DistributorPost::get_post_thumbnail_url
	 */
	public function test_get_post_thumbnail_url(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( get_the_post_thumbnail_url( $post ), $dt_post->get_post_thumbnail_url() );
	}

	/**
	 * Test the get_the_post_thumbnail() method.
	 *
	 * @covers \Distributor\DistributorPost::get_the_post_thumbnail
	 */
	public function test_get_the_post_thumbnail(): void {
		$post    = ( new PostGenerator() )->create()->getPost();
		$dt_post = new DistributorPost( $post );

		$this->assertSame( get_the_post_thumbnail( $post ), $dt_post->get_the_post_thumbnail() );
	}

	/**
	 * Test the get_meta() method.
	 *
	 * @covers \Distributor\DistributorPost::get_meta
	 */
	public function test_get_meta(): void {
		$post = ( new PostGenerator() )->create()->withMeta(
			array(
				'dt_original_post_id'       => '10',
				'dt_original_site_name'     => 'Test External, Pulled Origin',
				'dt_original_site_url'      => 'http://origin.example.org/',
				'dt_original_post_url'      => 'http://origin.example.org/?p=10',
				'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
				'dt_syndicate_time'         => time(),
				'dt_full_connection'        => '',
				'dt_original_source_id'     => '3',
				'distributable_meta_data'   => 'This will be distributed.',
			)
		)->getPost();

		$dt_post = new DistributorPost( $post->ID );

		$excluded_meta      = excluded_meta();
		$distributable_meta = $dt_post->get_meta();

		$this->assertArrayHasKey(
			'distributable_meta_data',
			$distributable_meta,
			'Distributable meta should be included.'
		);

		foreach ( $excluded_meta as $meta_key ) {
			$this->assertArrayNotHasKey(
				$meta_key,
				$distributable_meta,
				"Excluded meta '{$meta_key}' should not be included."
			);
		}
	}

	/**
	 * Test the get_terms() method.
	 *
	 * @covers \Distributor\DistributorPost::get_terms
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
	 * Test that the cache gets set when parse_media_blocks is called.
	 *
	 * @covers ::get_media
	 * @covers ::parse_media_blocks
	 * @covers ::parse_blocks_for_attachment_id
	 * @runInSeparateProcess
	 * @doesNotPerformAssertions
	 */
	public function test_get_media_sets_cache() {
		$this->markTestIncomplete();
	}

	/**
	 * Test methods for formatting the post data without blocks.
	 *
	 * @covers ::post_data()
	 * @covers ::to_insert()
	 * @covers ::to_json()
	 * @runInSeparateProcess
	 */
	public function test_scheduled_post_data_without_blocks() {
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

	/**
	 * This method provides data for the internal connection tests.
	 */
	public function internal_connection_data_provider(): array {
		$data = new \stdClass();

		$main_blog_id = get_site()->id;

		// Create a new blog.
		$data->blog = self::factory()->blog->create_and_get( array(
			'blog_id' => 2,
			'domain'  => 'origin.example.org',
		) );

		// Create a new post on the new blog.
		switch_to_blog( $data->blog->blog_id );

		$post_generator = new PostGenerator();
		$data->post     = $post_generator->create()->getPost();
		$data->post_url = get_permalink( $data->post->ID );

		// Reset to main blog.
		switch_to_blog( $main_blog_id );

		return array( array( $data ) );
	}
}
