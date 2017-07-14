<?php

namespace Distributor;

class UtilsTest extends \TestCase {

	/**
	 * Test set meta with string value and array value
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_simple() {
		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 2,
			'args'   => [ 1, 'key', 'value' ],
			'return' => [],
		] );

		Utils\set_meta( 1, [
			'key' => 'value',
		] );

		Utils\set_meta( 1, [
			'key' => [ 'value' ],
		] );
	}

	/**
	 * Test set meta with multiple values
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_multi() {
		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ 1, 'key', 'value' ],
			'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ 1, 'key2', 'value2' ],
			'return' => [],
		] );

		Utils\set_meta( 1, [
			'key'  => 'value',
			'key2' => 'value2',
		] );
	}

	/**
	 * Test set meta with serialized value
	 *
	 * @since  1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_meta_serialize() {
		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ 1, 'key', 'value' ],
			'return' => [],
		] );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times'  => 1,
			'args'   => [ 1, 'key2', [ 0 => 'test' ] ],
			'return' => [],
		] );

		Utils\set_meta( 1, [
			'key'  => 'value',
			'key2' => 'a:1:{i:0;s:4:"test";}',
		] );
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and term
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_simple() {
		$post_id = 1;
		$term_id = 1;
		$taxonomy = 'taxonomy';
		$slug = 'slug';
		$name = 'name';

		\WP_Mock::userFunction( 'taxonomy_exists', [
			'times'  => 1,
			'args'   => [ $taxonomy ],
			'return' => true,
		] );

		\WP_Mock::userFunction( 'get_term_by', [
			'times'  => 1,
			'args'   => [ 'slug', $slug, $taxonomy ],
			'return' => function() use ( $term_id ) {
				$term = new \stdClass();
				$term->term_id = $term_id;

				return $term;
			},
		] );

		/**
		 * Don't need to create any terms
		 */
		\WP_Mock::userFunction( 'wp_insert_term', [
			'times'  => 0,
		] );

		\WP_Mock::onFilter( 'dt_update_term_hierarchy' )
			->with( true )
			->reply( true );

		\WP_Mock::userFunction( 'wp_set_object_terms', [
			'times'  => 1,
			'args'   => [ $post_id, [ $term_id ], $taxonomy ],
		] );

		Utils\set_taxonomy_terms( $post_id, [
			$taxonomy  => [
				[
					'slug'    => $slug,
					'name'    => $name,
					'term_id' => $term_id,
					'parent'  => 0,
				],
			],
		] );
	}

	/**
	 * Test set taxonomy terms with an existing taxonomy and non existing term
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_create_term() {
		$post_id = 1;
		$term_id = 1;
		$taxonomy = 'taxonomy';
		$slug = 'slug';
		$name = 'name';

		\WP_Mock::userFunction( 'taxonomy_exists', [
			'times'  => 1,
			'args'   => [ $taxonomy ],
			'return' => true,
		] );

		\WP_Mock::userFunction( 'get_term_by', [
			'times'  => 1,
			'args'   => [ 'slug', $slug, $taxonomy ],
			'return' => false,
		] );

		/**
		 * Don't need to create any terms
		 */
		\WP_Mock::userFunction( 'wp_insert_term', [
			'times' => 1,
			'args'  => [ $name, $taxonomy ],
			'return' => [ 'term_id' => $term_id ],
		] );

		\WP_Mock::onFilter( 'dt_update_term_hierarchy' )
			->with( true )
			->reply( true );

		\WP_Mock::userFunction( 'wp_set_object_terms', [
			'times'  => 1,
			'args'   => [ $post_id, [ $term_id ], $taxonomy ],
		] );

		Utils\set_taxonomy_terms( $post_id, [
			$taxonomy  => [
				[
					'slug'    => $slug,
					'name'    => $name,
					'term_id' => $term_id,
					'parent'  => 0,
				],
			],
		] );
	}

	/**
	 * Test set taxonomy terms with non existing taxonomy
	 *
	 * @since 1.0
	 * @group Utils
	 * @runInSeparateProcess
	 */
	public function test_set_taxonomy_terms_no_taxonomy() {
		$post_id = 1;
		$term_id = 1;
		$taxonomy = 'taxonomy';
		$slug = 'slug';
		$name = 'name';

		\WP_Mock::userFunction( 'taxonomy_exists', [
			'times'  => 1,
			'args'   => [ $taxonomy ],
			'return' => false,
		] );

		\WP_Mock::userFunction( 'wp_set_object_terms', [
			'times'  => 0,
		] );

		Utils\set_taxonomy_terms( $post_id, [
			$taxonomy  => [
				[
					'slug'    => $slug,
					'name'    => $name,
					'term_id' => $term_id,
					'parent'  => 0,
				],
			],
		] );
	}

	/**
	 * Todo: Test set_taxonomy_terms hierarchical functionality
	 */

}
