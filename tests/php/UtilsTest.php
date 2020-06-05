<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;

class UtilsTest extends TestCase {

	/**
	 * Test set meta with string value and array value
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_simple() {
		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1 ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1 ],
				'return' => [ 'key' => [ 'value' ] ],
			]
		);

		\WP_Mock::userFunction(
			'add_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', 'value' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', [ 'value' ], 'value' ],
				'return' => [],
			]
		);

		\WP_Mock::expectAction( 'dt_after_set_meta', [ 'key' => [ 'value' ] ], [], 1 );

		\WP_Mock::expectAction( 'dt_after_set_meta', [ 'key' => [ [ 'value' ] ] ], [ 'key' => [ 'value' ] ], 1 );

		Utils\set_meta(
			1, [
				'key' => [ 'value' ]
			]
		);

		Utils\set_meta(
			1, [
				'key' => [ [ 'value' ] ],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test set meta with multiple values
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_multi() {
		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1 ],
				'return' => [ 'key' => [ 'value' ], 'key2' => [ 'value2' ] ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1 ],
				'return' => [ 'key' => [ 'value', 'value2' ], 'key2' => [ 'value3' ] ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', 'value', 'value' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key2', 'value2', 'value2' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', 'value', 'value' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', 'value2', 'value2' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key2', 'value3', 'value3' ],
				'return' => [],
			]
		);

		Utils\set_meta(
			1, [
				'key'  => [ 'value' ],
				'key2' => [ 'value2' ],
			]
		);

		Utils\set_meta(
			1, [
				'key'  => [
					'value',
					'value2'
				],
				'key2' => [ 'value3' ],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test set meta with serialized value
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_serialize() {
		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1 ],
				'return' => [ 'key' => [ 'value' ], 'key2' => [ [ 0 => 'test' ] ] ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key', 'value', 'value' ],
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'key2', [ 0 => 'test' ], [ 0 => 'test' ] ],
				'return' => [],
			]
		);

		Utils\set_meta(
			1, [
				'key'  => [ 'value' ],
				'key2' => [ 'a:1:{i:0;s:4:"test";}' ],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and term
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_simple() {
		$post_id  = 1;
		$term_id  = 1;
		$taxonomy = 'taxonomy';
		$slug     = 'slug';
		$name     = 'name';

		\WP_Mock::userFunction(
			'taxonomy_exists', [
				'times'  => 1,
				'args'   => [ $taxonomy ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_term_by', [
				'times'  => 1,
				'args'   => [ 'slug', $slug, $taxonomy ],
				'return' => function() use ( $term_id ) {
					$term          = new \stdClass();
					$term->term_id = $term_id;

					return $term;
				},
			]
		);

		/**
		 * Don't need to create any terms
		 */
		\WP_Mock::userFunction(
			'wp_insert_term', [
				'times' => 0,
			]
		);

		\WP_Mock::userFunction(
			'wp_update_term', [
				'times'  => 1,
				'args'   => [
					$term_id,
					$taxonomy,
					[
						'parent' => 0,
					]
				],
				'return' => [ 'term_id' => $term_id ],
			]
		);

		\WP_Mock::onFilter( 'dt_update_term_hierarchy' )
			->with( true )
			->reply( true );

		\WP_Mock::userFunction(
			'wp_set_object_terms', [
				'times' => 1,
				'args'  => [ $post_id, [ $term_id ], $taxonomy ],
			]
		);

		Utils\set_taxonomy_terms(
			$post_id, [
				$taxonomy => [
					[
						'slug'    => $slug,
						'name'    => $name,
						'term_id' => $term_id,
						'parent'  => 0,
					],
				],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and non existing term
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_create_term() {
		$post_id     = 1;
		$term_id     = 1;
		$taxonomy    = 'taxonomy';
		$slug        = 'slug';
		$name        = 'name';
		$description = 'description';

		\WP_Mock::userFunction(
			'taxonomy_exists', [
				'times'  => 1,
				'args'   => [ $taxonomy ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_term_by', [
				'times'  => 1,
				'args'   => [ 'slug', $slug, $taxonomy ],
				'return' => false,
			]
		);

		/**
		 * Don't need to create any terms
		 */
		\WP_Mock::userFunction(
			'wp_insert_term', [
				'times'  => 1,
				'args'   => [
					$name,
					$taxonomy,
					[
						'slug' => $slug,
						'description' => $description
					]
				],
				'return' => [ 'term_id' => $term_id ],
			]
		);

		\WP_Mock::userFunction(
			'wp_update_term', [
				'times'  => 1,
				'args'   => [
					$term_id,
					$taxonomy,
					[
						'parent' => 0,
					]
				],
				'return' => [ 'term_id' => $term_id ],
			]
		);

		\WP_Mock::onFilter( 'dt_update_term_hierarchy' )
			->with( true )
			->reply( true );

		\WP_Mock::userFunction(
			'wp_set_object_terms', [
				'times' => 1,
				'args'  => [ $post_id, [ $term_id ], $taxonomy ],
			]
		);

		Utils\set_taxonomy_terms(
			$post_id, [
				$taxonomy => [
					[
						'slug'        => $slug,
						'name'        => $name,
						'term_id'     => $term_id,
						'parent'      => 0,
						'description' => $description,
					],
				],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Test set taxonomy terms with non existing taxonomy
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_no_taxonomy() {
		$post_id  = 1;
		$term_id  = 1;
		$taxonomy = 'taxonomy';
		$slug     = 'slug';
		$name     = 'name';

		\WP_Mock::userFunction(
			'taxonomy_exists', [
				'times'  => 1,
				'args'   => [ $taxonomy ],
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_set_object_terms', [
				'times' => 0,
			]
		);

		Utils\set_taxonomy_terms(
			$post_id, [
				$taxonomy => [
					[
						'slug'    => $slug,
						'name'    => $name,
						'term_id' => $term_id,
						'parent'  => 0,
					],
				],
			]
		);

		$this->assertConditionsMet();
	}

	/**
	 * Todo: Test set_taxonomy_terms hierarchical functionality
	 */

	/**
	 * Test format media with no feature
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_format_media_not_featured() {
		$media_post                 = new \stdClass();
		$media_post->ID             = 1;
		$media_post->post_parent    = 10;
		$media_post->post_title     = 'title';
		$media_post->post_content   = 'content';
		$media_post->post_excerpt   = 'excerpt';
		$media_post->post_mime_type = 'image/png';

		\WP_Mock::userFunction(
			'get_post_thumbnail_id', [
				'times'  => 1,
				'args'   => [ $media_post->post_parent ],
				'return' => 0,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $media_post->ID, '_wp_attachment_image_alt', true ],
				'return' => 'alt',
			]
		);

		\WP_Mock::userFunction(
			'wp_attachment_is_image', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_metadata', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => [ 'test' => 1 ],
			]
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_url', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => 'http://mediaitem.com',
			]
		);

		\WP_Mock::userFunction(
			'get_attached_file', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => '/var/www/html/wp-content/uploads/mediaitem.jpg',
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => [
					'meta1' => [ true ],
					'meta2' => [ false ],
				],
			]
		);

		\WP_Mock::userFunction(
			'remove_filter', [
				'times' => 1,
			]
		);

		$formatted_media = Utils\format_media_post( $media_post );

		$this->assertFalse( $formatted_media['featured'] );

		return $formatted_media;
	}

	/**
	 * Test format media with feature
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_format_media_featured() {
		$media_post                 = new \stdClass();
		$media_post->ID             = 1;
		$media_post->post_parent    = 10;
		$media_post->post_title     = 'title';
		$media_post->post_content   = 'content';
		$media_post->post_excerpt   = 'excerpt';
		$media_post->post_mime_type = 'image/png';

		\WP_Mock::userFunction(
			'get_post_thumbnail_id', [
				'times'  => 1,
				'args'   => [ $media_post->post_parent ],
				'return' => $media_post->ID,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $media_post->ID, '_wp_attachment_image_alt', true ],
				'return' => 'alt',
			]
		);

		\WP_Mock::userFunction(
			'wp_attachment_is_image', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_metadata', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => [ 'test' => 1 ],
			]
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_url', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => 'http://mediaitem.com',
			]
		);

		\WP_Mock::userFunction(
			'get_attached_file', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => '/var/www/html/wp-content/uploads/mediaitem.jpg',
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $media_post->ID ],
				'return' => [
					'meta1' => [ true ],
					'meta2' => [ false ],
				],
			]
		);

		\WP_Mock::userFunction(
			'remove_filter', [
				'times' => 1,
			]
		);

		$formatted_media = Utils\format_media_post( $media_post );

		$this->assertTrue( $formatted_media['featured'] );

		return $formatted_media;
	}

	/**
	 * Test set media
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_media() {
		$post_id    = 1;
		$media_item = $this->test_format_media_featured();

		$new_image_id = 5;

		$attached_media_post                 = new \stdClass();
		$attached_media_post->ID             = 3;
		$attached_media_post->post_parent    = 10;
		$attached_media_post->post_title     = 'title';
		$attached_media_post->post_content   = 'content';
		$attached_media_post->post_excerpt   = 'excerpt';
		$attached_media_post->post_mime_type = 'image/png';

		\WP_Mock::userFunction(
			'Distributor\Utils\get_settings', [
				'times'  => 1,
				'return' => [
					'override_author_byline' => true,
					'media_handling'         => 'featured',
					'email'                  => '',
					'license_key'            => '',
					'valid_license'          => null,
				],
			]
		);

		\WP_Mock::userFunction(
			'get_attached_media', [
				'times'  => 1,
				'args'   => [ get_allowed_mime_types(), $post_id ],
				'return' => [ $attached_media_post ],
			]
		);

		\WP_Mock::userFunction(
			'wp_parse_args', [
				'times'  => 1,
				'args'   => [
					[ 'use_filesystem' => false ],
					[ 'use_filesystem' => false ],
				],
				'return' => [
					'use_filesystem' => false,
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_delete_attachment', [
				'times' => 1,
				'args'  => [ $attached_media_post->ID, true ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $attached_media_post->ID, 'dt_original_media_url', true ],
				'return' => 'http://mediaitem.com',
			]
		);

		\WP_Mock::userFunction(
			'wp_list_pluck', [
				'times'  => 1,
				'args'   => [ [ $media_item ], 'featured' ],
				'return' => [ 0 => true ],
			]
		);

		\WP_Mock::userFunction(
			'Distributor\Utils\process_media', [
				'times'  => 1,
				'args'   => [ $media_item['source_url'], $post_id,
					[
						'source_file'    => $media_item['source_file'],
						'use_filesystem' => false,
					]
				],
				'return' => $new_image_id,
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $new_image_id, 'dt_original_media_url', $media_item['source_url'] ],
			]
		);

		\WP_Mock::userFunction(
			'set_post_thumbnail', [
				'times' => 1,
				'args'  => [ $post_id, $new_image_id ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $new_image_id, 'dt_original_media_id', $media_item['id'] ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $new_image_id ],
				'return' => [ 'meta1' => [ true ], 'meta2' => [ false ] ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $new_image_id, 'meta1', true, true ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $new_image_id, 'meta2', false, false ],
			]
		);

		\WP_Mock::userFunction(
			'wp_update_post', [
				'times' => 1,
				'args'  => [
					[
						'ID'           => $new_image_id,
						'post_title'   => $attached_media_post->post_title,
						'post_content' => $attached_media_post->post_content,
						'post_excerpt' => $attached_media_post->post_excerpt,
					],
				],
			]
		);

		Utils\set_media( $post_id, [ $media_item ], [ 'use_filesystem' => false ] );
	}

	/**
	 * Todo finish test_set_media
	 */

	/**
	 * Todo finish process_media
	 */

}
