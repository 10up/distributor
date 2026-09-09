<?php
/**
 * Utils Test
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use WP_UnitTest_Factory;

use function Distributor\Utils\format_media_post;
use function Distributor\Utils\post_args_allow_list;
use function Distributor\Utils\set_media;
use function Distributor\Utils\set_meta;
use function Distributor\Utils\set_taxonomy_terms;

/**
 * Integration tests for includes/utils.php
 */
class Test_Utils extends WP_UnitTestCase {

	/**
	 * Shared user ID for the tests.
	 *
	 * @var int
	 */
	public static $post_id = 0;

	/**
	 * Set up shared fixtures.
	 *
	 * @param WP_UnitTest_Factory $factory
	 */
	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$post_id = $factory->post->create();

		// Clear default post meta.
		delete_post_meta( self::$post_id, '_pingme' );
		delete_post_meta( self::$post_id, '_encloseme' );
	}

	/**
	 * Test set meta with string value and array value
	 *
	 * @group Utils
	 */
	public function test_set_meta_simple() {
		$post_id = self::$post_id;

		$mock_action = new \MockAction();
		add_action( 'dt_after_set_meta', array( $mock_action, 'action' ), 10, 3 );

		set_meta( $post_id, array( 'key' => array( 'value' ) ) );

		$this->assertSame( array( 'value' ), get_post_meta( $post_id, 'key', false ) );

		set_meta( $post_id, array( 'key' => array( array( 'value' ) ) ) );

		$this->assertSame( array( array( 'value' ) ), get_post_meta( $post_id, 'key', false ) );

		$this->assertSame( 2, $mock_action->get_call_count() );

		$call_args = $mock_action->get_args();

		// First call: no pre-existing meta.
		$this->assertSame( array( 'key' => array( 'value' ) ), $call_args[0][0] );
		$this->assertSame( array(), $call_args[0][1] );
		$this->assertSame( $post_id, $call_args[0][2] );

		// Second call: meta from the first call is now "existing".
		$this->assertSame( array( 'key' => array( array( 'value' ) ) ), $call_args[1][0] );
		$this->assertSame( array( 'key' => array( 'value' ) ), $call_args[1][1] );
		$this->assertSame( $post_id, $call_args[1][2] );
	}

	/**
	 * Test set meta with multiple values
	 *
	 * @group Utils
	 */
	public function test_set_meta_multi() {
		$post_id = self::$post_id;

		$mock_action = new \MockAction();
		add_action( 'dt_after_set_meta', array( $mock_action, 'action' ) );

		set_meta(
			$post_id,
			array(
				'key'  => array( 'value' ),
				'key2' => array( 'value2' ),
			)
		);

		$this->assertSame( array( 'value' ), get_post_meta( $post_id, 'key', false ) );
		$this->assertSame( array( 'value2' ), get_post_meta( $post_id, 'key2', false ) );

		set_meta(
			$post_id,
			array(
				'key'  => array( 'value', 'value2' ),
				'key2' => array( 'value3' ),
			)
		);

		$this->assertSame( array( 'value', 'value2' ), get_post_meta( $post_id, 'key', false ) );
		$this->assertSame( array( 'value3' ), get_post_meta( $post_id, 'key2', false ) );

		$this->assertSame( 2, $mock_action->get_call_count() );
	}

	/**
	 * Test set meta with a serialized value
	 *
	 * @group Utils
	 */
	public function test_set_meta_serialize() {
		$post_id = self::$post_id;

		set_meta(
			$post_id,
			array(
				'key'  => array( 'value' ),
				'key2' => array( array( 0 => 'test' ) ),
			)
		);

		$this->assertSame( array( 'value' ), get_post_meta( $post_id, 'key', false ) );
		$this->assertSame( array( array( 0 => 'test' ) ), get_post_meta( $post_id, 'key2', false ) );

		// Meta can arrive as an already-serialized string, e.g. from a remote site over REST.
		set_meta( $post_id, array( 'key3' => array( 'a:1:{i:0;s:4:"test";}' ) ) );

		$this->assertSame( array( array( 0 => 'test' ) ), get_post_meta( $post_id, 'key3', false ) );
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and term
	 *
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_simple() {
		$post_id = self::$post_id;
		$term    = $this->factory()->term->create_and_get(
			array(
				'taxonomy' => 'category',
				'name'     => 'Term Name',
				'slug'     => 'term-slug',
			)
		);

		set_taxonomy_terms(
			$post_id,
			array(
				'category' => array(
					array(
						'slug'    => $term->slug,
						'name'    => $term->name,
						'term_id' => $term->term_id,
						'parent'  => 0,
					),
				),
			)
		);

		$this->assertSame( array( $term->term_id ), wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) ) );

		// No duplicate term should have been created for the existing slug.
		$terms_with_slug = get_terms(
			array(
				'taxonomy'   => 'category',
				'slug'       => $term->slug,
				'hide_empty' => false,
			)
		);
		$this->assertCount( 1, $terms_with_slug );

		$this->assertSame( 0, get_term( $term->term_id, 'category' )->parent );
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and non existing term
	 *
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_create_term() {
		$post_id     = self::$post_id;
		$taxonomy    = 'category';
		$slug        = 'brand-new-term';
		$name        = 'Brand New Term';
		$description = 'A description.';

		$this->assertFalse( get_term_by( 'slug', $slug, $taxonomy ) );

		set_taxonomy_terms(
			$post_id,
			array(
				$taxonomy => array(
					array(
						'slug'        => $slug,
						'name'        => $name,
						// The remote site's term ID. It only needs to be unique within this request.
						'term_id'     => 999,
						'parent'      => 0,
						'description' => $description,
					),
				),
			)
		);

		$term = get_term_by( 'slug', $slug, $taxonomy );

		$this->assertInstanceOf( 'WP_Term', $term );
		$this->assertSame( $name, $term->name );
		$this->assertSame( $description, $term->description );
		$this->assertSame( 0, $term->parent );

		$this->assertSame( array( $term->term_id ), wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) ) );
	}

	/**
	 * Test set taxonomy terms with non existing taxonomy
	 *
	 * @group Utils
	 */
	public function test_set_taxonomy_terms_no_taxonomy() {
		$post_id  = self::$post_id;
		$taxonomy = 'a_taxonomy_that_does_not_exist';

		$this->assertFalse( taxonomy_exists( $taxonomy ) );

		set_taxonomy_terms(
			$post_id,
			array(
				$taxonomy => array(
					array(
						'slug'    => 'slug',
						'name'    => 'name',
						'term_id' => 1,
						'parent'  => 0,
					),
				),
			)
		);

		// Nothing should have been created for the unregistered taxonomy.
		$this->assertFalse( get_term_by( 'slug', 'slug', 'category' ) );
	}

	/**
	 * Test format media with no feature
	 *
	 * @group Utils
	 */
	public function test_format_media_not_featured() {
		$parent_id = self::$post_id;
		$media_id  = $this->factory()->attachment->create_object(
			'test-image.jpg',
			$parent_id,
			array(
				'post_title'     => 'title',
				'post_content'   => 'content',
				'post_excerpt'   => 'excerpt',
				'post_mime_type' => 'image/png',
			)
		);

		update_post_meta( $media_id, '_wp_attachment_image_alt', 'alt' );

		$formatted_media = format_media_post( get_post( $media_id ), $parent_id );

		$this->assertFalse( $formatted_media['featured'] );
		$this->assertSame( $media_id, $formatted_media['id'] );
		$this->assertSame( 'title', $formatted_media['title'] );
		$this->assertSame( 'content', $formatted_media['description']['raw'] );
		$this->assertSame( 'excerpt', $formatted_media['caption']['raw'] );
		$this->assertSame( 'alt', $formatted_media['alt_text'] );
		$this->assertSame( 'image', $formatted_media['media_type'] );
		$this->assertSame( 'image/png', $formatted_media['mime_type'] );
	}

	/**
	 * Test format media with feature
	 *
	 * @group Utils
	 */
	public function test_format_media_featured() {
		$parent_id = self::$post_id;
		$media_id  = $this->factory()->attachment->create_object(
			'test-image.jpg',
			$parent_id,
			array(
				'post_title'     => 'title',
				'post_mime_type' => 'image/png',
			)
		);

		set_post_thumbnail( $parent_id, $media_id );

		$formatted_media = format_media_post( get_post( $media_id ), $parent_id );

		$this->assertTrue( $formatted_media['featured'] );
	}

	/**
	 * Test format media excludes excluded meta, e.g. `_wp_attachment_metadata`
	 *
	 * @group Utils
	 */
	public function test_format_media_no_attachment_meta() {
		$parent_id = self::$post_id;
		$media_id  = $this->factory()->attachment->create_object(
			'test-image.jpg',
			$parent_id,
			array( 'post_mime_type' => 'image/png' )
		);

		update_post_meta( $media_id, '_wp_attachment_metadata', array( 'width' => 100 ) );
		update_post_meta( $media_id, 'custom_meta', 'value' );

		$formatted_media = format_media_post( get_post( $media_id ), $parent_id );

		$this->assertArrayNotHasKey( '_wp_attachment_metadata', $formatted_media['meta'] );
		$this->assertArrayHasKey( 'custom_meta', $formatted_media['meta'] );
	}

	/**
	 * Test set media reuses media already attached to the post when the
	 * source URL matches, rather than downloading it again.
	 *
	 * @group Utils
	 */
	public function test_set_media_reuses_already_attached_media() {
		$post_id = self::$post_id;

		$existing_media_id = $this->factory()->attachment->create_object(
			'test-image.jpg',
			$post_id,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		update_post_meta( $existing_media_id, 'dt_original_media_url', 'http://example.com/mediaitem.jpg' );

		$media_item = array(
			'id'          => 999,
			'source_url'  => 'http://example.com/mediaitem.jpg',
			'source_file' => '',
			'featured'    => true,
			'title'       => 'New Title',
			'description' => array( 'raw' => 'New content' ),
			'caption'     => array( 'raw' => 'New caption' ),
		);

		set_media( $post_id, array( $media_item ), array( 'use_filesystem' => false ) );

		$this->assertSame( $existing_media_id, (int) get_post_thumbnail_id( $post_id ) );

		$updated_media = get_post( $existing_media_id );
		$this->assertSame( 'New Title', $updated_media->post_title );
		$this->assertSame( 'New content', $updated_media->post_content );
		$this->assertSame( 'New caption', $updated_media->post_excerpt );
		$this->assertSame( '999', get_post_meta( $existing_media_id, 'dt_original_media_id', true ) );
	}

	/**
	 * Test set media reuses media that already exists on the site (matched by
	 * the original remote ID/URL) even if it isn't yet attached to this post.
	 *
	 * @group Utils
	 */
	public function test_set_media_reuses_media_by_original_data() {
		$post_id = self::$post_id;

		$existing_media_id = $this->factory()->attachment->create_object(
			'test-image.jpg',
			0,
			array( 'post_mime_type' => 'image/jpeg' )
		);
		update_post_meta( $existing_media_id, 'dt_original_media_id', 55 );
		update_post_meta( $existing_media_id, 'dt_original_media_url', 'http://example.com/mediaitem.jpg' );

		$media_item = array(
			'id'          => 55,
			'source_url'  => 'http://example.com/mediaitem.jpg',
			'source_file' => '',
			'featured'    => false,
			'title'       => 'Existing media',
			'description' => array( 'raw' => '' ),
			'caption'     => array( 'raw' => '' ),
		);

		set_media( $post_id, array( $media_item ), array( 'use_filesystem' => false ) );

		// The pre-existing attachment should be reused (and updated), not attached to this
		// post - `set_media()` never touches `post_parent` for media matched this way -
		// and no new attachment should have been created via `process_media()`.
		$this->assertSame(
			array( $existing_media_id ),
			get_posts(
				array(
					'post_type'   => 'attachment',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			)
		);
		$this->assertSame( 'Existing media', get_post( $existing_media_id )->post_title );
		$this->assertSame( 0, (int) get_post_thumbnail_id( $post_id ) );
	}

	/**
	 * Test set media downloads and sideloads media that doesn't already
	 * exist on the site.
	 *
	 * The network request is intercepted via `pre_http_request` and answered
	 * with a real local test image, so `media_handle_sideload()` has valid
	 * image data to work with without needing an external connection.
	 *
	 * @group Utils
	 */
	public function test_set_media_downloads_new_media() {
		$post_id    = self::$post_id;
		$source_url = 'http://example.com/new-mediaitem.jpg';
		$test_image = DIR_TESTDATA . '/images/test-image.jpg';

		$media_item = array(
			'id'          => 123,
			'source_url'  => $source_url,
			'source_file' => '',
			'featured'    => true,
			'title'       => 'Downloaded media',
			'description' => array( 'raw' => 'Downloaded content' ),
			'caption'     => array( 'raw' => 'Downloaded caption' ),
			'meta'        => array(),
		);

		$intercept_download = function ( $preempt, $parsed_args, $url ) use ( $source_url, $test_image ) {
			if ( $url !== $source_url ) {
				return $preempt;
			}

			if ( ! empty( $parsed_args['filename'] ) ) {
				copy( $test_image, $parsed_args['filename'] );
			}

			return array(
				'headers'  => array(),
				'body'     => '',
				'response' => array(
					'code'    => 200,
					'message' => 'OK',
				),
				'cookies'  => array(),
				'filename' => $parsed_args['filename'] ?? null,
			);
		};

		add_filter( 'pre_http_request', $intercept_download, 10, 3 );
		set_media( $post_id, array( $media_item ), array( 'use_filesystem' => false ) );
		remove_filter( 'pre_http_request', $intercept_download, 10 );

		$attached = get_attached_media( 'image', $post_id );
		$this->assertCount( 1, $attached );

		$new_media = current( $attached );
		$this->assertSame( 'Downloaded media', $new_media->post_title );
		$this->assertSame( $new_media->ID, (int) get_post_thumbnail_id( $post_id ) );
		$this->assertSame( $source_url, get_post_meta( $new_media->ID, 'dt_original_media_url', true ) );
		$this->assertSame( '123', get_post_meta( $new_media->ID, 'dt_original_media_id', true ) );
	}

	/**
	 * Test post_args_allow_list
	 *
	 * @group Utils
	 */
	public function test_post_args_allow_list() {
		$post_args = array(
			'post_title'   => 'Test Title',
			'post_type'    => 'post',
			'post_content' => 'Test Content',
			'post_excerpt' => 'Test Excerpt',
			'link'         => 'https://github.com/10up/distributor/issues/879',
			'dt_source'    => 'https://github.com/10up/distributor/pull/895',
		);

		$expected = array(
			'post_title'   => 'Test Title',
			'post_type'    => 'post',
			'post_content' => 'Test Content',
			'post_excerpt' => 'Test Excerpt',
		);

		$this->assertSame( $expected, post_args_allow_list( $post_args ) );
	}
}
