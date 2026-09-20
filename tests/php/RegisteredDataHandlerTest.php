<?php
/**
 * Tests for the RegisteredDataHandler class.
 *
 * @package distributor
 */

namespace Distributor;

use WP_Mock\Tools\TestCase;
use WP_Mock\Functions;
use stdClass;

/**
 * Class RegisteredDataHandlerTest
 *
 * @group RegisteredDataHandler
 */
class RegisteredDataHandlerTest extends TestCase {

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$GLOBALS['distributor_registered_data'] = array();
	}

	/**
	 * Test constructor initializes connection data correctly.
	 */
	public function test_construct(): void {
		$handler_default = new RegisteredDataHandler();
		$this->assertSame( array(), $handler_default->connection_data );

		$connection_data = array( 'site_url' => 'https://example.com' );
		$handler_custom  = new RegisteredDataHandler( $connection_data );
		$this->assertSame( $connection_data, $handler_custom->connection_data );
	}

	/**
	 * Test search_replace_block_inner_content returns block unchanged when replacement strings are empty.
	 */
	public function test_search_replace_block_inner_content_empty(): void {
		$handler = new RegisteredDataHandler();
		$block   = array(
			'innerHTML'    => '<p>Hello World</p>',
			'innerContent' => array( '<p>Hello World</p>' ),
		);

		$result = $handler->search_replace_block_inner_content( $block, array() );
		$this->assertSame( $block, $result );
	}

	/**
	 * Test search_replace_block_inner_content replaces content in both innerHTML and innerContent.
	 */
	public function test_search_replace_block_inner_content_replaces(): void {
		$handler = new RegisteredDataHandler();
		$block   = array(
			'innerHTML'    => '<p>Search me and find me</p>',
			'innerContent' => array( '<p>Search me and find me</p>' ),
		);

		$replacements = array(
			array(
				'search'  => 'Search',
				'replace' => 'Replace',
			),
			array(
				'search'  => 'find',
				'replace' => 'get',
			),
		);

		$result = $handler->search_replace_block_inner_content( $block, $replacements );
		$this->assertSame( '<p>Replace me and get me</p>', $result['innerHTML'] );
		$this->assertSame( '<p>Replace me and get me</p>', $result['innerContent'][0] );
	}

	/**
	 * Test prepare_registered_data_term returns 0 when get_term fails or returns WP_Error.
	 */
	public function test_prepare_registered_data_term_invalid(): void {
		$handler = new RegisteredDataHandler();

		\WP_Mock::userFunction(
			'get_term',
			array(
				'args'   => array( 999 ),
				'return' => null,
			)
		);

		$this->assertSame( 0, $handler->prepare_registered_data_term( 999 ) );

		\WP_Mock::userFunction(
			'get_term',
			array(
				'args'   => array( 998 ),
				'return' => new \WP_Error( 'invalid_term', 'Invalid term' ),
			)
		);

		$this->assertSame( 0, $handler->prepare_registered_data_term( 998 ) );
	}

	/**
	 * Test prepare_registered_data_term prepares term data array without parent.
	 */
	public function test_prepare_registered_data_term_flat(): void {
		$handler = new RegisteredDataHandler();

		$term              = new stdClass();
		$term->term_id     = 42;
		$term->name        = 'Tech News';
		$term->slug        = 'tech-news';
		$term->description = 'All tech articles';
		$term->taxonomy    = 'post_tag';
		$term->parent      = 0;

		\WP_Mock::userFunction(
			'get_term',
			array(
				'args'   => array( 42 ),
				'return' => $term,
			)
		);

		$result = $handler->prepare_registered_data_term( 42, false );

		$expected = array(
			'term_id'     => 42,
			'name'        => 'Tech News',
			'slug'        => 'tech-news',
			'description' => 'All tech articles',
			'taxonomy'    => 'post_tag',
		);
		$this->assertSame( $expected, $result );
	}

	/**
	 * Test prepare_registered_data_term prepares hierarchical term data with recursive parent.
	 */
	public function test_prepare_registered_data_term_hierarchical_with_parent(): void {
		$handler = new RegisteredDataHandler();

		$child_term              = new stdClass();
		$child_term->term_id     = 10;
		$child_term->name        = 'WordPress Core';
		$child_term->slug        = 'wordpress-core';
		$child_term->description = 'Core news';
		$child_term->taxonomy    = 'category';
		$child_term->parent      = 5;

		$parent_term              = new stdClass();
		$parent_term->term_id     = 5;
		$parent_term->name        = 'Development';
		$parent_term->slug        = 'development';
		$parent_term->description = 'Dev category';
		$parent_term->taxonomy    = 'category';
		$parent_term->parent      = 0;

		\WP_Mock::userFunction(
			'get_term',
			array(
				'args'   => array( 10 ),
				'return' => $child_term,
			)
		);

		\WP_Mock::userFunction(
			'get_term',
			array(
				'args'   => array( 5 ),
				'return' => $parent_term,
			)
		);

		\WP_Mock::userFunction(
			'is_taxonomy_hierarchical',
			array(
				'args'   => array( 'category' ),
				'return' => true,
			)
		);

		$result = $handler->prepare_registered_data_term( 10, true );

		$this->assertSame( 10, $result['term_id'] );
		$this->assertIsArray( $result['parent'] );
		$this->assertSame( 5, $result['parent']['term_id'] );
		$this->assertSame( 'development', $result['parent']['slug'] );
	}

	/**
	 * Test process_registered_data_term returns 0 when empty data provided.
	 */
	public function test_process_registered_data_term_empty(): void {
		$handler = new RegisteredDataHandler();
		$this->assertSame( 0, $handler->process_registered_data_term( array() ) );
	}

	/**
	 * Test process_registered_data_term returns existing term ID when found.
	 */
	public function test_process_registered_data_term_existing_term(): void {
		$handler = new RegisteredDataHandler();

		$term          = new stdClass();
		$term->term_id = 77;
		$term->parent  = 0;

		\WP_Mock::userFunction(
			'is_taxonomy_hierarchical',
			array(
				'args'   => array( 'category' ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_term_by',
			array(
				'args'   => array( 'slug', 'my-term', 'category' ),
				'return' => $term,
			)
		);

		$term_data = array(
			'slug'        => 'my-term',
			'taxonomy'    => 'category',
			'name'        => 'My Term',
			'description' => 'Test',
		);

		$result = $handler->process_registered_data_term( $term_data );
		$this->assertSame( 77, $result );
	}

	/**
	 * Test process_registered_data_term updates parent hierarchy when requested.
	 */
	public function test_process_registered_data_term_existing_with_hierarchy_update(): void {
		$handler = new RegisteredDataHandler();

		$parent_term          = new stdClass();
		$parent_term->term_id = 15;
		$parent_term->parent  = 0;

		$child_term          = new stdClass();
		$child_term->term_id = 77;
		$child_term->parent  = 0;

		\WP_Mock::userFunction(
			'is_taxonomy_hierarchical',
			array(
				'args'   => array( 'category' ),
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_term_by',
			array(
				'args'   => array( 'slug', 'parent-category', 'category' ),
				'return' => $parent_term,
			)
		);

		\WP_Mock::userFunction(
			'get_term_by',
			array(
				'args'   => array( 'slug', 'child-category', 'category' ),
				'return' => $child_term,
			)
		);

		\WP_Mock::userFunction(
			'wp_update_term',
			array(
				'times'  => 1,
				'args'   => array( 77, 'category', array( 'parent' => 15 ) ),
				'return' => array( 'term_id' => 77 ),
			)
		);

		$term_data = array(
			'slug'        => 'child-category',
			'taxonomy'    => 'category',
			'name'        => 'Child Category',
			'description' => 'Test',
			'parent'      => array(
				'term_id'     => 15,
				'slug'        => 'parent-category',
				'taxonomy'    => 'category',
				'name'        => 'Parent Category',
				'description' => 'Parent',
			),
		);

		$result = $handler->process_registered_data_term( $term_data, true, true );
		$this->assertSame( 77, $result );
	}

	/**
	 * Test process_registered_data_term inserts new term when not found.
	 */
	public function test_process_registered_data_term_inserts_new_term(): void {
		$handler = new RegisteredDataHandler();

		\WP_Mock::userFunction(
			'is_taxonomy_hierarchical',
			array(
				'args'   => array( 'post_tag' ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_term_by',
			array(
				'args'   => array( 'slug', 'new-tag', 'post_tag' ),
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_insert_term',
			array(
				'times'  => 1,
				'args'   => array(
					'New Tag',
					'post_tag',
					array(
						'slug'        => 'new-tag',
						'description' => 'Tag desc',
					),
				),
				'return' => array( 'term_id' => 101 ),
			)
		);

		$term_data = array(
			'slug'        => 'new-tag',
			'taxonomy'    => 'post_tag',
			'name'        => 'New Tag',
			'description' => 'Tag desc',
		);

		$result = $handler->process_registered_data_term( $term_data );
		$this->assertSame( 101, $result );
	}

	/**
	 * Test process_registered_data_term returns 0 on insert failure.
	 */
	public function test_process_registered_data_term_insert_failure(): void {
		$handler = new RegisteredDataHandler();

		\WP_Mock::userFunction(
			'is_taxonomy_hierarchical',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_term_by',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_insert_term',
			array(
				'return' => new \WP_Error( 'db_insert_error', 'Cannot insert term' ),
			)
		);

		$term_data = array(
			'slug'        => 'bad-tag',
			'taxonomy'    => 'post_tag',
			'name'        => 'Bad Tag',
			'description' => 'Failed tag',
		);

		$result = $handler->process_registered_data_term( $term_data );
		$this->assertSame( 0, $result );
	}

	/**
	 * Test process_registered_post_meta_data returns post meta unchanged when callback or key missing.
	 */
	public function test_process_registered_post_meta_data_no_callback_or_meta_key(): void {
		$handler   = new RegisteredDataHandler();
		$post_meta = array( 'some_key' => 'some_val' );

		// Missing callback.
		$reg_data_no_cb = array(
			'attributes' => array( 'meta_key' => 'some_key' ),
		);
		$this->assertSame( $post_meta, $handler->process_registered_post_meta_data( $post_meta, $reg_data_no_cb, array(), array() ) );

		// Missing meta key.
		$reg_data_no_key = array(
			'post_distribute_cb' => function() {
				return 'new_val';
			},
			'attributes'         => array(),
		);
		$this->assertSame( $post_meta, $handler->process_registered_post_meta_data( $post_meta, $reg_data_no_key, array(), array() ) );
	}

	/**
	 * Test process_registered_post_meta_data updates single string meta key.
	 */
	public function test_process_registered_post_meta_data_single_string_key(): void {
		$handler   = new RegisteredDataHandler( array( 'origin' => 'source_site' ) );
		$post_meta = array( 'target_meta' => 'old_val' );
		$extra     = array( 'extra_info' => 123 );
		$post_data = array( 'ID' => 50 );

		$reg_data = array(
			'attributes'         => array( 'meta_key' => 'target_meta' ),
			'post_distribute_cb' => function( $extra_data, $orig_data, $current_post_data, $conn_data ) {
				return $orig_data . '_updated_' . $conn_data['origin'];
			},
		);

		$result = $handler->process_registered_post_meta_data( $post_meta, $reg_data, $extra, $post_data );

		$this->assertSame( 'old_val_updated_source_site', $result['target_meta'] );
	}

	/**
	 * Test process_registered_post_meta_data unwraps and rewraps single-element meta array.
	 */
	public function test_process_registered_post_meta_data_array_wrapped_single_key(): void {
		$handler   = new RegisteredDataHandler();
		$post_meta = array( 'wrapped_meta' => array( 'inner_value' ) );

		$reg_data = array(
			'attributes'         => array( 'meta_key' => 'wrapped_meta' ),
			'post_distribute_cb' => function( $extra_data, $orig_data ) {
				return strtoupper( $orig_data );
			},
		);

		$result = $handler->process_registered_post_meta_data( $post_meta, $reg_data, array(), array() );

		$this->assertSame( array( 'INNER_VALUE' ), $result['wrapped_meta'] );
	}

	/**
	 * Test process_registered_post_meta_data processes array of meta keys.
	 */
	public function test_process_registered_post_meta_data_multiple_keys(): void {
		$handler   = new RegisteredDataHandler();
		$post_meta = array(
			'key_one' => 'alpha',
			'key_two' => array( 'beta' ),
		);

		$reg_data = array(
			'attributes'         => array( 'meta_key' => array( 'key_one', 'key_two' ) ),
			'post_distribute_cb' => function( $extra_data, $orig_data ) {
				return array(
					'key_one' => $orig_data['key_one'] . '-1',
					'key_two' => $orig_data['key_two'] . '-2',
				);
			},
		);

		$result = $handler->process_registered_post_meta_data( $post_meta, $reg_data, array(), array() );

		$this->assertSame( 'alpha-1', $result['key_one'] );
		$this->assertSame( array( 'beta-2' ), $result['key_two'] );
	}

	/**
	 * Test process_blocks_data_recursive returns unchanged when callback is not callable.
	 */
	public function test_process_blocks_data_recursive_not_callable(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName' => 'core/paragraph',
				'attrs'     => array(),
			),
		);

		$reg_data = array(
			'attributes' => array( 'block_name' => 'core/paragraph' ),
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, array(), array(), 0 );

		$this->assertSame( $blocks, $result['blocks'] );
		$this->assertFalse( $result['modified'] );
		$this->assertSame( 0, $result['index'] );
	}

	/**
	 * Test process_blocks_data_recursive updates matching block attribute and sets modified true.
	 */
	public function test_process_blocks_data_recursive_matching_block_single_attribute(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName' => 'core/custom',
				'attrs'     => array( 'item_id' => 10 ),
			),
		);

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/custom',
				'block_attribute' => 'item_id',
			),
			'post_distribute_cb' => function( $extra, $source, $post, $conn ) {
				return 20;
			},
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, array(), array(), 0 );

		$this->assertTrue( $result['modified'] );
		$this->assertSame( 20, $result['blocks'][0]['attrs']['item_id'] );
		$this->assertSame( 1, $result['index'] );
	}

	/**
	 * Test process_blocks_data_recursive handles array of block attributes and inner content replacements.
	 */
	public function test_process_blocks_data_recursive_matching_block_array_attributes(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName'    => 'core/multi-attr',
				'attrs'        => array(
					'first'  => 'old_first',
					'second' => 'old_second',
				),
				'innerHTML'    => '<p>old_first</p>',
				'innerContent' => array( '<p>old_first</p>' ),
			),
		);

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/multi-attr',
				'block_attribute' => array( 'first', 'second' ),
			),
			'post_distribute_cb' => function( $extra, $source ) {
				return array(
					'first'                     => 'new_first',
					'second'                    => 'new_second',
					'inner_content_replacements' => array(
						array(
							'search'  => 'old_first',
							'replace' => 'new_first',
						),
					),
				);
			},
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, array(), array(), 0 );

		$this->assertTrue( $result['modified'] );
		$this->assertSame( 'new_first', $result['blocks'][0]['attrs']['first'] );
		$this->assertSame( 'new_second', $result['blocks'][0]['attrs']['second'] );
		$this->assertSame( '<p>new_first</p>', $result['blocks'][0]['innerHTML'] );
	}

	/**
	 * Test process_blocks_data_recursive handles media block replacement.
	 */
	public function test_process_blocks_data_recursive_media_type(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName'    => 'core/image',
				'attrs'        => array( 'id' => 100 ),
				'innerHTML'    => '<img src="https://source.com/img.jpg" class="wp-image-100" />',
				'innerContent' => array( '<img src="https://source.com/img.jpg" class="wp-image-100" />' ),
			),
		);

		$extra_data = array(
			0 => array(
				'url'  => 'https://source.com/img.jpg',
				'guid' => 'https://source.com/img.jpg',
			),
		);

		$reg_data = array(
			'type'               => 'media',
			'attributes'         => array(
				'block_name'      => 'core/image',
				'block_attribute' => 'id',
			),
			'post_distribute_cb' => function() {
				return 200;
			},
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_url',
			array(
				'args'   => array( 200 ),
				'return' => 'https://target.com/img.jpg',
			)
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, $extra_data, array(), 0 );

		$this->assertTrue( $result['modified'] );
		$this->assertSame( 200, $result['blocks'][0]['attrs']['id'] );
		$this->assertStringContainsString( 'https://target.com/img.jpg', $result['blocks'][0]['innerHTML'] );
		$this->assertStringContainsString( 'wp-image-200', $result['blocks'][0]['innerHTML'] );
	}

	/**
	 * Test process_blocks_data_recursive handles recursive traversal of innerBlocks.
	 */
	public function test_process_blocks_data_recursive_nested_inner_blocks(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/inner-target',
						'attrs'     => array( 'id' => 5 ),
					),
				),
			),
		);

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/inner-target',
				'block_attribute' => 'id',
			),
			'post_distribute_cb' => function() {
				return 50;
			},
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, array(), array(), 0 );

		$this->assertTrue( $result['modified'] );
		$this->assertSame( 50, $result['blocks'][0]['innerBlocks'][0]['attrs']['id'] );
	}

	/**
	 * Test process_registered_block_data returns original content when has_block is false.
	 */
	public function test_process_registered_block_data_no_block(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = '<p>Simple paragraph with no matching block</p>';

		$reg_data = array(
			'attributes' => array(
				'block_name'      => 'core/missing-block',
				'block_attribute' => 'id',
			),
		);

		\WP_Mock::userFunction(
			'has_block',
			array(
				'args'   => array( 'core/missing-block', $post_content ),
				'return' => false,
			)
		);

		$result = $handler->process_registered_block_data( $post_content, $reg_data, array(), array() );
		$this->assertSame( $post_content, $result );
	}

	/**
	 * Test process_registered_block_data parses, processes, and serializes modified blocks.
	 */
	public function test_process_registered_block_data_success(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = '<!-- wp:core/widget {"id":1} /-->';

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/widget',
				'block_attribute' => 'id',
			),
			'post_distribute_cb' => function() {
				return 2;
			},
		);

		\WP_Mock::userFunction(
			'has_block',
			array(
				'args'   => array( 'core/widget', $post_content ),
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'parse_blocks',
			array(
				'args'   => array( $post_content ),
				'return' => array(
					array(
						'blockName' => 'core/widget',
						'attrs'     => array( 'id' => 1 ),
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'serialize_blocks',
			array(
				'args'   => array(
					array(
						array(
							'blockName' => 'core/widget',
							'attrs'     => array( 'id' => 2 ),
						),
					),
				),
				'return' => '<!-- wp:core/widget {"id":2} /-->',
			)
		);

		$result = $handler->process_registered_block_data( $post_content, $reg_data, array(), array() );
		$this->assertSame( '<!-- wp:core/widget {"id":2} /-->', $result );
	}

	/**
	 * Test process_registered_shortcode_data returns content when has_shortcode is false.
	 */
	public function test_process_registered_shortcode_data_no_shortcode(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = 'Plain text without shortcode';

		$reg_data = array(
			'attributes' => array(
				'shortcode'           => 'my_sc',
				'shortcode_attribute' => 'item_id',
			),
		);

		\WP_Mock::userFunction(
			'has_shortcode',
			array(
				'args'   => array( $post_content, 'my_sc' ),
				'return' => false,
			)
		);

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertSame( $post_content, $result );
	}

	/**
	 * Test process_registered_shortcode_data replaces single shortcode attribute.
	 */
	public function test_process_registered_shortcode_data_single_attribute(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = 'Here is [my_gallery id="10"] and more text';

		$reg_data = array(
			'attributes'         => array(
				'shortcode'           => 'my_gallery',
				'shortcode_attribute' => 'id',
			),
			'post_distribute_cb' => function( $extra, $source ) {
				return 99;
			},
		);

		\WP_Mock::userFunction(
			'has_shortcode',
			array(
				'args'   => array( $post_content, 'my_gallery' ),
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_shortcode_regex',
			array(
				'args'   => array( array( 'my_gallery' ) ),
				'return' => '\\[(\\[?)(my_gallery)( [^\\]]+)?\\]',
			)
		);

		\WP_Mock::userFunction(
			'shortcode_parse_atts',
			array(
				'args'   => array( ' id="10"' ),
				'return' => array( 'id' => '10' ),
			)
		);

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertStringContainsString( '[my_gallery id="99"]', $result );
	}

	/**
	 * Test process_registered_shortcode_data replaces multiple shortcode attributes.
	 */
	public function test_process_registered_shortcode_data_array_attribute(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = 'Testing [item_card id="5" category="tech"]';

		$reg_data = array(
			'attributes'         => array(
				'shortcode'           => 'item_card',
				'shortcode_attribute' => array( 'id', 'category' ),
			),
			'post_distribute_cb' => function( $extra, $source ) {
				return array(
					'id'       => '50',
					'category' => 'science',
				);
			},
		);

		\WP_Mock::userFunction(
			'has_shortcode',
			array(
				'args'   => array( $post_content, 'item_card' ),
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_shortcode_regex',
			array(
				'args'   => array( array( 'item_card' ) ),
				'return' => '\\[(\\[?)(item_card)( [^\\]]+)?\\]',
			)
		);

		\WP_Mock::userFunction(
			'shortcode_parse_atts',
			array(
				'args'   => array( ' id="5" category="tech"' ),
				'return' => array(
					'id'       => '5',
					'category' => 'tech',
				),
			)
		);

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertStringContainsString( 'id="50"', $result );
		$this->assertStringContainsString( 'category="science"', $result );
	}

	/**
	 * Test process_registered_data exits early when dt_process_extra_data filter returns false.
	 */
	public function test_process_registered_data_bypassed_by_filter(): void {
		$handler   = new RegisteredDataHandler();
		$post_data = array( 'post_title' => 'Original' );

		\WP_Mock::onFilter( 'dt_process_extra_data' )
			->with( true, $post_data )
			->reply( false );

		$result = $handler->process_registered_data( $post_data );
		$this->assertSame( $post_data, $result );
	}

	/**
	 * Test process_registered_data returns unchanged when no registered data exists.
	 */
	public function test_process_registered_data_empty_registry(): void {
		$handler   = new RegisteredDataHandler();
		$post_data = array( 'post_title' => 'Test Post' );

		$GLOBALS['distributor_registered_data'] = array();

		$result = $handler->process_registered_data( $post_data );
		$this->assertSame( $post_data, $result );
	}

	/**
	 * Test process_registered_data orchestrates post_meta processing.
	 */
	public function test_process_registered_data_with_meta(): void {
		$handler = new RegisteredDataHandler();

		$GLOBALS['distributor_registered_data'] = array(
			'test_meta' => array(
				'location'           => 'post_meta',
				'attributes'         => array( 'meta_key' => 'custom_field' ),
				'post_distribute_cb' => function( $extra, $orig ) {
					return 'processed_' . $orig;
				},
			),
		);

		$post_data = array(
			'distributor_meta' => array(
				'custom_field' => 'raw_value',
			),
		);

		$result = $handler->process_registered_data( $post_data );
		$this->assertSame( 'processed_raw_value', $result['distributor_meta']['custom_field'] );
	}

	/**
	 * Test pre_process_registered_data_post returns early when distributor_extra_data is empty.
	 */
	public function test_pre_process_registered_data_post_empty_extra_data(): void {
		$handler    = new RegisteredDataHandler();
		$post_data  = array( 'post_title' => 'Sample Post' );
		$connection = $this->getMockBuilder( '\Distributor\ExternalConnection' )->disableOriginalConstructor()->getMock();

		$result = $handler->pre_process_registered_data_post( $post_data, $connection );
		$this->assertSame( $post_data, $result );
	}
}
