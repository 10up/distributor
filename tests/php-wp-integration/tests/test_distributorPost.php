<?php
/**
 * DistributorPost tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;
use Distributor\DistributorPost;
use Distributor\Utils;

/**
 * Integration tests for the DistributorPost class.
 *
 * @covers \Distributor\DistributorPost
 * @group Post
 */
class Test_DistributorPost extends WP_UnitTestCase {
	/**
	 * Origin sub-site ID for internal connections.
	 *
	 * @var int
	 */
	public static $origin_blog_id = 0;

	/**
	 * Post ID on the origin sub-site that was distributed internally.
	 *
	 * @var int
	 */
	public static $origin_post_id = 0;

	/**
	 * External connection ID posts are pulled from.
	 *
	 * @var int
	 */
	public static $external_connection_id = 0;

	/**
	 * Author of the posts on this site.
	 *
	 * @var \WP_User
	 */
	public static $author;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory Test suite factory.
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$origin_blog_id = $factory->blog->create( array( 'title' => 'Test Internal Origin' ) );

		switch_to_blog( self::$origin_blog_id );
		self::$origin_post_id = $factory->post->create( array( 'post_title' => 'Internal origin post' ) );
		restore_current_blog();

		self::$external_connection_id = $factory->post->create(
			array(
				'post_type'  => 'dt_ext_connection',
				'post_title' => 'Test External, Pulled Origin',
			)
		);

		self::$author = $factory->user->create_and_get(
			array(
				'role'         => 'author',
				'display_name' => 'Distributed site author name',
			)
		);
	}

	/**
	 * Tear down shared fixtures.
	 *
	 * Sites are not removed by the test suite's data cleanup, so delete the
	 * sub-site to avoid its tables leaking into later test runs.
	 */
	public static function wpTearDownAfterClass() {
		wp_delete_site( self::$origin_blog_id );
	}

	/**
	 * Tear down.
	 */
	public function tear_down() {
		$this->remove_added_uploads();
		parent::tear_down();
	}

	/**
	 * Get the permalink of the origin post.
	 *
	 * @return string Permalink.
	 */
	protected function get_origin_permalink() {
		switch_to_blog( self::$origin_blog_id );
		$permalink = get_permalink( self::$origin_post_id );
		restore_current_blog();

		return $permalink;
	}

	/**
	 * Create a post of the given kind.
	 *
	 * @param string $kind      One of source, internal, external_pushed or external_pulled.
	 * @param array  $post_args Optional. Post arguments.
	 * @param array  $meta      Optional. Additional post meta.
	 * @return int Post ID.
	 */
	protected function create_post( $kind, $post_args = array(), $meta = array() ) {
		$kind_meta = array(
			'source'          => array(),
			'internal'        => array(
				'dt_original_post_id'  => self::$origin_post_id,
				'dt_original_blog_id'  => self::$origin_blog_id,
				'dt_syndicate_time'    => 1670383190,
				'dt_original_post_url' => $this->get_origin_permalink(),
			),
			'external_pushed' => array(
				'dt_original_post_id'       => 10,
				'dt_original_site_name'     => 'Test External, Pushed Origin',
				'dt_original_site_url'      => 'http://origin.example.org/',
				'dt_original_post_url'      => 'http://origin.example.org/?p=10',
				'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
				'dt_syndicate_time'         => 1670384223,
				'dt_full_connection'        => 1,
				'dt_original_source_id'     => 2,
			),
			'external_pulled' => array(
				'dt_original_post_id'       => 10,
				'dt_original_site_name'     => 'Test External, Pulled Origin',
				'dt_original_site_url'      => 'http://origin.example.org/',
				'dt_original_post_url'      => 'http://origin.example.org/?p=10',
				'dt_subscription_signature' => 'abcdefghijklmnopqrstuvwxyz',
				'dt_syndicate_time'         => 1670384223,
				'dt_full_connection'        => '',
				'dt_original_source_id'     => self::$external_connection_id,
			),
		);

		return self::factory()->post->create(
			array_merge(
				array(
					'post_title'   => 'Test Post',
					'post_content' => 'Test Content',
					'post_excerpt' => 'Test Excerpt',
					'post_name'    => 'test-post',
					'post_author'  => self::$author->ID,
					'meta_input'   => array_merge( $kind_meta[ $kind ], $meta ),
				),
				$post_args
			)
		);
	}

	/**
	 * Create an image attachment with a real file.
	 *
	 * @param int $parent_id Optional. Parent post ID.
	 * @return int Attachment ID.
	 */
	protected function create_image( $parent_id = 0 ) {
		return self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg', $parent_id );
	}

	/**
	 * Create an attachment of the given mime type without a file.
	 *
	 * @param string $file      File name.
	 * @param string $mime_type Mime type.
	 * @param int    $parent_id Optional. Parent post ID.
	 * @return int Attachment ID.
	 */
	protected function create_attachment( $file, $mime_type, $parent_id = 0 ) {
		return self::factory()->attachment->create_object(
			$file,
			$parent_id,
			array(
				'post_mime_type' => $mime_type,
				'post_title'     => pathinfo( $file, PATHINFO_FILENAME ),
			)
		);
	}

	/**
	 * Get the expected media data for attachments.
	 *
	 * @param int   $post_id        Post the media is distributed with.
	 * @param int[] $attachment_ids Attachment IDs in the expected order.
	 * @param int   $featured_id    Optional. ID of the featured image.
	 * @return array[] Expected media data.
	 */
	protected function expected_media( $post_id, $attachment_ids, $featured_id = 0 ) {
		$media = array();
		foreach ( $attachment_ids as $attachment_id ) {
			$media_item             = Utils\format_media_post( get_post( $attachment_id ), $post_id );
			$media_item['featured'] = $attachment_id === $featured_id;
			$media[]                = $media_item;
		}

		return $media;
	}

	/**
	 * Test the DistributorPost object for public methods.
	 *
	 * Only magic methods are expected to be public as the class uses the
	 * __call() method to handle all other methods.
	 */
	public function test_public_methods() {
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
	 * The original post URL is read from the origin site, so a stale URL in
	 * post meta is replaced with the live permalink.
	 */
	public function test_internal_connection() {
		$post_id = $this->create_post( 'internal', array(), array( 'dt_original_post_url' => 'http://origin.example.org/stale-url/' ) );

		switch_to_blog( self::$origin_blog_id );
		$origin_home_url = home_url();
		restore_current_blog();

		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( self::$origin_post_id, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( $this->get_origin_permalink(), $dt_post->original_post_url, 'Origin post URL does not match the live permalink.' );
		$this->assertSame( self::$origin_blog_id, $dt_post->connection_id, 'Origin site ID does not match expected value.' );
		$this->assertSame( 'bidirectional', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'internal', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( $origin_home_url, $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test Internal Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 */
	public function test_external_connection_with_pushed_post() {
		$dt_post = new DistributorPost( $this->create_post( 'external_pushed' ) );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->connection_id, 'Origin connection ID does not match source URL.' );
		$this->assertSame( 'pushed', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pushed Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pulled posts.
	 */
	public function test_external_connection_with_pulled_post() {
		$dt_post = new DistributorPost( $this->create_post( 'external_pulled' ) );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( self::$external_connection_id, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( 'pulled', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pulled Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertFalse( $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for a source post.
	 */
	public function test_source_post() {
		$post_id = $this->create_post( 'source' );
		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( $post_id, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertTrue( $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( get_permalink( $post_id ), $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 0, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( home_url(), $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( get_bloginfo( 'name' ), $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertTrue( $dt_post->is_source, 'Post is incorrectly marked as distributed.' );
	}

	/**
	 * Test the get_the_id() method.
	 */
	public function test_get_the_id() {
		$post_id = $this->create_post( 'source' );
		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( $post_id, $dt_post->get_the_id() );
	}

	/**
	 * Test the get_permalink() method.
	 */
	public function test_get_permalink() {
		$post_id = $this->create_post( 'source' );
		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( get_permalink( $post_id ), $dt_post->get_permalink() );
	}

	/**
	 * Test the get_post_type() method.
	 */
	public function test_get_post_type() {
		$dt_post = new DistributorPost( $this->create_post( 'source', array( 'post_type' => 'page' ) ) );

		$this->assertSame( 'page', $dt_post->get_post_type() );
	}

	/**
	 * Test the get_post_thumbnail_id() method.
	 */
	public function test_get_post_thumbnail_id() {
		$post_id      = $this->create_post( 'source' );
		$thumbnail_id = $this->create_image( $post_id );
		set_post_thumbnail( $post_id, $thumbnail_id );

		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( $thumbnail_id, $dt_post->get_post_thumbnail_id() );
	}

	/**
	 * Test the get_post_thumbnail_url() method.
	 */
	public function test_get_post_thumbnail_url() {
		$post_id      = $this->create_post( 'source' );
		$thumbnail_id = $this->create_image( $post_id );
		set_post_thumbnail( $post_id, $thumbnail_id );

		$dt_post = new DistributorPost( $post_id );
		$actual  = $dt_post->get_post_thumbnail_url();

		$this->assertNotEmpty( $actual, 'Thumbnail URL should not be empty.' );
		$this->assertSame( wp_get_attachment_image_url( $thumbnail_id, 'post-thumbnail' ), $actual );
	}

	/**
	 * Test the get_the_post_thumbnail() method.
	 */
	public function test_get_the_post_thumbnail() {
		$post_id      = $this->create_post( 'source' );
		$thumbnail_id = $this->create_image( $post_id );
		set_post_thumbnail( $post_id, $thumbnail_id );

		$dt_post = new DistributorPost( $post_id );
		$actual  = $dt_post->get_the_post_thumbnail();

		$this->assertStringContainsString( 'wp-post-image', $actual, 'Thumbnail markup should be returned.' );
		$this->assertSame( get_the_post_thumbnail( $post_id ), $actual );
	}

	/**
	 * Test the get_meta() method.
	 */
	public function test_get_meta() {
		$post_id = $this->create_post( 'external_pulled', array(), array( 'distributable_meta_data' => 'This will be distributed.' ) );
		$dt_post = new DistributorPost( $post_id );

		$distributable_meta = $dt_post->get_meta();

		$this->assertSame( array( 'This will be distributed.' ), $distributable_meta['distributable_meta_data'] ?? null, 'Distributable meta should be included.' );
		foreach ( Utils\excluded_meta() as $meta_key ) {
			$this->assertArrayNotHasKey( $meta_key, $distributable_meta, "Excluded meta '{$meta_key}' should not be included." );
		}
	}

	/**
	 * Test the get_terms() method.
	 */
	public function test_get_terms() {
		$post_id  = $this->create_post( 'external_pulled' );
		$category = self::factory()->category->create_and_get( array( 'name' => 'Test Category' ) );
		$tag      = self::factory()->tag->create_and_get( array( 'name' => 'Test Tag' ) );
		wp_set_post_categories( $post_id, array( $category->term_id ) );
		wp_set_post_tags( $post_id, array( $tag->term_id ) );

		$dt_post             = new DistributorPost( $post_id );
		$distributable_terms = $dt_post->get_terms();

		$this->assertEquals( array( get_term( $category->term_id ) ), $distributable_terms['category'] ?? null, 'Categories should be included.' );
		$this->assertEquals( array( get_term( $tag->term_id ) ), $distributable_terms['post_tag'] ?? null, 'Tags should be included.' );
	}

	/**
	 * Test the get_media() method with blocks.
	 */
	public function test_get_media_with_blocks() {
		$post_id  = $this->create_post( 'source' );
		$image_id = $this->create_image();
		$audio_id = $this->create_attachment( 'mp3-5mg.mp3', 'audio/mpeg' );
		$video_id = $this->create_attachment( 'sample-mp4.mp4', 'video/mp4' );

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf(
					'<!-- wp:image {"id":%1$d,"sizeSlug":"large","linkDestination":"none"} --><figure class="wp-block-image size-large"><img src="%2$s" alt="" class="wp-image-%1$d"/></figure><!-- /wp:image -->' .
					'<!-- wp:group --><div class="wp-block-group"><!-- wp:audio {"id":%3$d} --><figure class="wp-block-audio"><audio controls src="%4$s"></audio></figure><!-- /wp:audio --></div><!-- /wp:group -->' .
					'<!-- wp:video {"id":%5$d} --><figure class="wp-block-video"><video controls src="%6$s"></video></figure><!-- /wp:video -->',
					$image_id,
					wp_get_attachment_url( $image_id ),
					$audio_id,
					wp_get_attachment_url( $audio_id ),
					$video_id,
					wp_get_attachment_url( $video_id )
				),
			)
		);

		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( $this->expected_media( $post_id, array( $image_id, $audio_id, $video_id ) ), $dt_post->get_media(), 'Media in blocks, including inner blocks, should be returned.' );
	}

	/**
	 * Test the get_media() method with attachments.
	 *
	 * For posts without blocks, attached media and images in the content are
	 * returned.
	 */
	public function test_get_media_with_attachments() {
		$post_id            = $this->create_post( 'source', array( 'post_content' => 'No content, just child posts.' ) );
		$image_id           = $this->create_image( $post_id );
		$audio_id           = $this->create_attachment( 'mp3-5mg.mp3', 'audio/mpeg', $post_id );
		$video_id           = $this->create_attachment( 'sample-mp4.mp4', 'video/mp4', $post_id );
		$content_image_id   = $this->create_image();
		$unrelated_image_id = $this->create_image();

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf( '<p>Content image <img src="%s" class="alignnone wp-image-%d" /></p>', wp_get_attachment_url( $content_image_id ), $content_image_id ),
			)
		);
		set_post_thumbnail( $post_id, $image_id );

		$dt_post = new DistributorPost( $post_id );
		$actual  = $dt_post->get_media();

		$this->assertEqualSets( array( $image_id, $audio_id, $video_id, $content_image_id ), wp_list_pluck( $actual, 'id' ), 'Attached media and content images should be returned.' );
		$this->assertNotContains( $unrelated_image_id, wp_list_pluck( $actual, 'id' ), 'Unrelated media should not be returned.' );

		$actual_by_id = array_column( $actual, null, 'id' );
		$expected     = $this->expected_media( $post_id, array( $image_id, $audio_id, $video_id, $content_image_id ), $image_id );
		$this->assertSame( array_column( $expected, null, 'id' ), $actual_by_id, 'Media should be formatted for distribution.' );
	}

	/**
	 * Test the get_media() method includes a featured image not used in the post.
	 */
	public function test_get_media_includes_unattached_featured_image() {
		$post_id     = $this->create_post( 'source' );
		$featured_id = $this->create_image();
		set_post_thumbnail( $post_id, $featured_id );

		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( $this->expected_media( $post_id, array( $featured_id ), $featured_id ), $dt_post->get_media() );
	}

	/**
	 * Test the get_media() method caches the media IDs parsed from blocks.
	 */
	public function test_get_media_sets_cache() {
		$post_id    = $this->create_post( 'source' );
		$image_id_1 = $this->create_image();
		$image_id_2 = $this->create_image();

		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => sprintf(
					'<!-- wp:image {"id":%1$d} --><figure class="wp-block-image"><img src="%2$s" alt="" class="wp-image-%1$d"/></figure><!-- /wp:image -->' .
					'<!-- wp:image {"id":%3$d} --><figure class="wp-block-image"><img src="%4$s" alt="" class="wp-image-%3$d"/></figure><!-- /wp:image -->',
					$image_id_1,
					wp_get_attachment_url( $image_id_1 ),
					$image_id_2,
					wp_get_attachment_url( $image_id_2 )
				),
			)
		);
		wp_cache_delete( "dt_media::{$post_id}", 'dt::post' );

		$dt_post = new DistributorPost( $post_id );
		$dt_post->get_media();

		$this->assertSame( array( $image_id_1, $image_id_2 ), wp_cache_get( "dt_media::{$post_id}", 'dt::post' ), 'Media IDs should be cached.' );
	}

	/**
	 * Assert the post data, insert and REST formats for an internally distributed post.
	 *
	 * @param int    $post_id          Post ID.
	 * @param string $expected_content Expected processed content.
	 * @param array  $insert_overrides Expected differences in the insert format.
	 * @param array  $rest_overrides   Expected differences in the REST format.
	 */
	protected function assert_post_data_formats( $post_id, $expected_content, $insert_overrides = array(), $rest_overrides = array() ) {
		$post    = get_post( $post_id );
		$dt_post = new DistributorPost( $post_id );

		// Terms and meta are tested in their own tests.
		$terms = $dt_post->get_terms();
		$meta  = $dt_post->get_meta();

		switch_to_blog( self::$origin_blog_id );
		$origin_home_url = home_url();
		restore_current_blog();

		$post_data_expected = array(
			'title'                          => 'Test Post',
			'slug'                           => 'test-post',
			'type'                           => 'post',
			'content'                        => $expected_content,
			'excerpt'                        => 'Test Excerpt',
			'parent'                         => 0,
			'status'                         => $post->post_status,
			'date'                           => $post->post_date,
			'date_gmt'                       => $post->post_date_gmt,
			'distributor_media'              => array(),
			'distributor_terms'              => $terms,
			'distributor_meta'               => $meta,
			'distributor_original_site_name' => 'Test Internal Origin',
			'distributor_original_site_url'  => $origin_home_url,
			'distributor_original_post_url'  => get_permalink( $post_id ),
			'distributor_original_post_id'   => $post_id,
			'distributor_extra_data'         => array(),
		);
		$this->assert_same_with_terms( $post_data_expected, $dt_post->post_data(), 'distributor_terms', 'Post data is not in an expected form' );

		$to_insert_expected = array_merge(
			array(
				'post_title'   => 'Test Post',
				'post_name'    => 'test-post',
				'post_type'    => 'post',
				'post_content' => $expected_content,
				'post_excerpt' => 'Test Excerpt',
				'post_status'  => $post->post_status,
				'terms'        => $terms,
				'meta'         => $meta,
				'media'        => array(),
				'post_author'  => (string) self::$author->ID,
				'meta_input'   => array(
					'dt_original_post_id'  => $post_id,
					'dt_original_post_url' => get_permalink( $post_id ),
				),
			),
			$insert_overrides
		);

		// Post meta for wp_insert_post() is always added last.
		$meta_input = $to_insert_expected['meta_input'];
		unset( $to_insert_expected['meta_input'] );
		$to_insert_expected['meta_input'] = $meta_input;

		$this->assert_same_with_terms( $to_insert_expected, $dt_post->to_insert(), 'terms', 'Insert post data is not in an expected form' );

		$to_rest_expected = array_merge(
			array(
				'title'                          => 'Test Post',
				'slug'                           => 'test-post',
				'type'                           => 'post',
				'content'                        => $expected_content,
				'excerpt'                        => 'Test Excerpt',
				'status'                         => $post->post_status,
				'distributor_media'              => array(),
				'distributor_terms'              => $terms,
				'distributor_meta'               => $meta,
				'distributor_original_site_name' => 'Test Internal Origin',
				'distributor_original_site_url'  => $origin_home_url,
				'distributor_original_post_url'  => get_permalink( $post_id ),
				'distributor_extra_data'         => array(),
				'distributor_remote_post_id'     => $post_id,
			),
			$rest_overrides
		);
		$this->assert_same_with_terms( $to_rest_expected, $dt_post->to_rest(), 'distributor_terms', 'REST API data is not in an expected form' );
	}

	/**
	 * Assert two arrays are identical, comparing term objects by value.
	 *
	 * @param array  $expected  Expected array.
	 * @param array  $actual    Actual array.
	 * @param string $terms_key Key holding the terms.
	 * @param string $message   Failure message.
	 */
	protected function assert_same_with_terms( $expected, $actual, $terms_key, $message ) {
		$this->assertEquals( $expected[ $terms_key ], $actual[ $terms_key ] ?? null, "{$message}: unexpected terms." );

		$expected[ $terms_key ] = 'terms';
		if ( array_key_exists( $terms_key, $actual ) ) {
			$actual[ $terms_key ] = 'terms';
		}

		$this->assertSame( $expected, $actual, $message );
	}

	/**
	 * Test methods for formatting the post data of a scheduled post without blocks.
	 */
	public function test_scheduled_post_data_without_blocks() {
		$post_id = $this->create_post(
			'internal',
			array(
				'post_status'   => 'future',
				'post_date'     => '2120-01-01 00:00:00',
				'post_date_gmt' => '2120-01-01 00:00:00',
			)
		);

		$this->assert_post_data_formats(
			$post_id,
			"<p>Test Content</p>\n",
			array(
				'post_date'     => '2120-01-01 00:00:00',
				'post_date_gmt' => '2120-01-01 00:00:00',
			)
		);
	}

	/**
	 * Test methods for formatting the post data without blocks.
	 */
	public function test_post_data_without_blocks() {
		$post_id = $this->create_post( 'internal' );

		$this->assert_post_data_formats( $post_id, "<p>Test Content</p>\n" );
	}

	/**
	 * Test methods for formatting the post data with blocks.
	 *
	 * The raw block content is used for inserts and sent to the REST API.
	 */
	public function test_post_data_with_blocks() {
		$block_content = '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->';
		$post_id       = $this->create_post( 'internal', array( 'post_content' => $block_content ) );

		// The markup of rendered blocks varies between WordPress versions.
		$this->assert_post_data_formats(
			$post_id,
			apply_filters( 'the_content', $block_content ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
			array( 'post_content' => $block_content ),
			array( 'distributor_raw_content' => $block_content )
		);
	}

	/**
	 * Test get_canonical_url() method for a distributed post.
	 */
	public function test_get_canonical_url() {
		$dt_post = new DistributorPost( $this->create_post( 'internal' ) );

		$this->assertSame( $this->get_origin_permalink(), $dt_post->get_canonical_url() );
	}

	/**
	 * Test get_canonical_url() method for an unlinked post.
	 */
	public function test_get_canonical_url_unlinked() {
		$post_id = $this->create_post( 'internal', array(), array( 'dt_unlinked' => 1 ) );
		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( get_permalink( $post_id ), $dt_post->get_canonical_url() );
	}

	/**
	 * Test get_canonical_url() method for a source post.
	 */
	public function test_get_canonical_url_source() {
		$post_id = $this->create_post( 'source' );
		$dt_post = new DistributorPost( $post_id );

		$this->assertSame( get_permalink( $post_id ), $dt_post->get_canonical_url() );
	}

	/**
	 * Test get_author_name() method for a distributed post.
	 */
	public function test_get_author_name() {
		$dt_post = new DistributorPost( $this->create_post( 'external_pushed' ) );

		$this->assertSame( 'Test External, Pushed Origin', $dt_post->get_author_name() );
	}

	/**
	 * Test get_author_name() method when the author byline is not overridden.
	 */
	public function test_get_author_name_without_override() {
		update_option( 'dt_settings', array( 'override_author_byline' => false ) );
		$dt_post = new DistributorPost( $this->create_post( 'external_pushed' ) );

		$this->assertSame( 'Distributed site author name', $dt_post->get_author_name(), 'Author name expected to point to distributed site.' );
		$this->assertSame( 'Filtered author name', $dt_post->get_author_name( 'Filtered author name' ), 'Author name expected to use filtered value.' );
	}

	/**
	 * Test get_author_name() method for an unlinked post.
	 */
	public function test_get_author_name_unlinked() {
		$dt_post = new DistributorPost( $this->create_post( 'internal', array(), array( 'dt_unlinked' => 1 ) ) );

		$this->assertSame( 'Distributed site author name', $dt_post->get_author_name() );
	}

	/**
	 * Test get_author_name() method for a source post.
	 */
	public function test_get_author_name_source() {
		$dt_post = new DistributorPost( $this->create_post( 'source' ) );

		$this->assertSame( 'Distributed site author name', $dt_post->get_author_name() );
	}

	/**
	 * Test get_author_link() method for a distributed post.
	 */
	public function test_get_author_link() {
		$dt_post = new DistributorPost( $this->create_post( 'external_pushed' ) );

		$this->assertSame( 'http://origin.example.org/', $dt_post->get_author_link() );
	}

	/**
	 * Test get_author_link() method when the author byline is not overridden.
	 */
	public function test_get_author_link_without_override() {
		update_option( 'dt_settings', array( 'override_author_byline' => false ) );
		$dt_post = new DistributorPost( $this->create_post( 'external_pushed' ) );

		$this->assertSame( get_author_posts_url( self::$author->ID ), $dt_post->get_author_link(), 'Author link should be the local author link' );
		$this->assertSame( 'http://example.org/filtered-link/', $dt_post->get_author_link( 'http://example.org/filtered-link/' ), 'Author link should use the prefiltered value.' );
	}

	/**
	 * Test get_author_link() method for an unlinked post.
	 */
	public function test_get_author_link_unlinked() {
		$dt_post = new DistributorPost( $this->create_post( 'internal', array(), array( 'dt_unlinked' => 1 ) ) );

		$this->assertSame( get_author_posts_url( self::$author->ID ), $dt_post->get_author_link() );
	}

	/**
	 * Test get_author_link() method for a source post.
	 */
	public function test_get_author_link_source() {
		$dt_post = new DistributorPost( $this->create_post( 'source' ) );

		$this->assertSame( get_author_posts_url( self::$author->ID ), $dt_post->get_author_link() );
	}
}
