<?php
/**
 * RegisteredDataHandler Integration Tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;
use Distributor\RegisteredDataHandler;
use Distributor\ExternalConnection;

/**
 * Integration tests for Distributor\RegisteredDataHandler.
 *
 * @group registered-data-handler
 * @group registered-data
 */
class Test_RegisteredDataHandler extends WP_UnitTestCase {


	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		parent::tearDown();
		$GLOBALS['distributor_registered_data'] = array();
		remove_all_filters( 'dt_process_extra_data' );
		remove_all_filters( 'dt_after_registered_block_data_processed' );
		remove_all_filters( 'dt_after_registered_shortcode_data_processed' );
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
	 * Test prepare_registered_data_term returns 0 when get_term fails or term does not exist.
	 */
	public function test_prepare_registered_data_term_invalid(): void {
		$handler = new RegisteredDataHandler();
		$this->assertSame( 0, $handler->prepare_registered_data_term( 9999999 ) );
	}

	/**
	 * Test prepare_registered_data_term prepares term data array without parent.
	 */
	public function test_prepare_registered_data_term_flat(): void {
		$handler = new RegisteredDataHandler();

		$term_id = $this->factory()->term->create(
			array(
				'taxonomy'    => 'post_tag',
				'name'        => 'Tech News',
				'slug'        => 'tech-news',
				'description' => 'All tech articles',
			)
		);

		$result = $handler->prepare_registered_data_term( $term_id, false );

		$expected = array(
			'term_id'     => $term_id,
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

		$parent_id = $this->factory()->category->create(
			array(
				'name'        => 'Development',
				'slug'        => 'development',
				'description' => 'Dev category',
			)
		);

		$child_id = $this->factory()->category->create(
			array(
				'name'        => 'WordPress Core',
				'slug'        => 'wordpress-core',
				'description' => 'Core news',
				'parent'      => $parent_id,
			)
		);

		$result = $handler->prepare_registered_data_term( $child_id, true );

		$this->assertSame( $child_id, $result['term_id'] );
		$this->assertIsArray( $result['parent'] );
		$this->assertSame( $parent_id, $result['parent']['term_id'] );
		$this->assertSame( 'development', $result['parent']['slug'] );
		$this->assertSame( 'Development', $result['parent']['name'] );
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

		$term_id = $this->factory()->category->create(
			array(
				'name' => 'Existing Category',
				'slug' => 'existing-category',
			)
		);

		$term_data = array(
			'slug'        => 'existing-category',
			'taxonomy'    => 'category',
			'name'        => 'Existing Category',
			'description' => 'Category Description',
		);

		$result = $handler->process_registered_data_term( $term_data );
		$this->assertSame( $term_id, $result );
	}

	/**
	 * Test process_registered_data_term updates parent hierarchy when requested.
	 */
	public function test_process_registered_data_term_existing_with_hierarchy_update(): void {
		$handler = new RegisteredDataHandler();

		$parent_term_id = $this->factory()->category->create(
			array(
				'name' => 'Parent Category',
				'slug' => 'parent-category',
			)
		);

		$child_term_id = $this->factory()->category->create(
			array(
				'name'   => 'Child Category',
				'slug'   => 'child-category',
				'parent' => 0,
			)
		);

		$term_data = array(
			'slug'        => 'child-category',
			'taxonomy'    => 'category',
			'name'        => 'Child Category',
			'description' => 'Test',
			'parent'      => array(
				'term_id'     => $parent_term_id,
				'slug'        => 'parent-category',
				'taxonomy'    => 'category',
				'name'        => 'Parent Category',
				'description' => 'Parent',
			),
		);

		$result = $handler->process_registered_data_term( $term_data, true, true );
		$this->assertSame( $child_term_id, $result );

		$updated_child = get_term( $child_term_id, 'category' );
		$this->assertSame( $parent_term_id, $updated_child->parent );
	}

	/**
	 * Test process_registered_data_term inserts new term when not found.
	 */
	public function test_process_registered_data_term_inserts_new_term(): void {
		$handler = new RegisteredDataHandler();

		$term_data = array(
			'slug'        => 'brand-new-distributor-tag',
			'taxonomy'    => 'post_tag',
			'name'        => 'Brand New Distributor Tag',
			'description' => 'Tag description',
		);

		$result = $handler->process_registered_data_term( $term_data );
		$this->assertGreaterThan( 0, $result );

		$created_term = get_term_by( 'slug', 'brand-new-distributor-tag', 'post_tag' );
		$this->assertNotEmpty( $created_term );
		$this->assertSame( $result, $created_term->term_id );
		$this->assertSame( 'Brand New Distributor Tag', $created_term->name );
	}

	/**
	 * Test process_registered_data_term returns 0 on insert failure.
	 */
	public function test_process_registered_data_term_insert_failure(): void {
		$handler = new RegisteredDataHandler();

		// Passing an invalid taxonomy causes wp_insert_term to return WP_Error.
		$term_data = array(
			'slug'        => 'bad-term',
			'taxonomy'    => 'non_existent_taxonomy_xyz',
			'name'        => 'Bad Term',
			'description' => 'Failed term',
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
			'post_distribute_cb' => function () {
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
			'post_distribute_cb' => function ( $extra_data, $orig_data, $current_post_data, $conn_data ) {
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
			'post_distribute_cb' => function ( $extra_data, $orig_data ) {
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
			'post_distribute_cb' => function ( $extra_data, $orig_data ) {
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
			'post_distribute_cb' => function () {
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
			'post_distribute_cb' => function () {
				return array(
					'first'                      => 'new_first',
					'second'                     => 'new_second',
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

		$attachment_id = $this->factory()->post->create(
			array(
				'post_type' => 'attachment',
				'guid'      => 'https://target.com/new-img.jpg',
			)
		);

		$blocks = array(
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
			'post_distribute_cb' => function () use ( $attachment_id ) {
				return $attachment_id;
			},
		);

		$result = $handler->process_blocks_data_recursive( $blocks, $reg_data, $extra_data, array(), 0 );

		$this->assertTrue( $result['modified'] );
		$this->assertSame( $attachment_id, $result['blocks'][0]['attrs']['id'] );
		$this->assertStringContainsString( 'https://target.com/new-img.jpg', $result['blocks'][0]['innerHTML'] );
		$this->assertStringContainsString( 'wp-image-' . $attachment_id, $result['blocks'][0]['innerHTML'] );
	}

	/**
	 * Test process_blocks_data_recursive processes nested innerBlocks.
	 */
	public function test_process_blocks_data_recursive_nested_blocks(): void {
		$handler = new RegisteredDataHandler();
		$blocks  = array(
			array(
				'blockName'   => 'core/group',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName' => 'core/inner-target',
						'attrs'     => array( 'id' => 25 ),
					),
				),
			),
		);

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/inner-target',
				'block_attribute' => 'id',
			),
			'post_distribute_cb' => function () {
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

		$result = $handler->process_registered_block_data( $post_content, $reg_data, array(), array() );
		$this->assertSame( $post_content, $result );
	}

	/**
	 * Test process_registered_block_data parses, processes, and serializes modified blocks.
	 */
	public function test_process_registered_block_data_success(): void {
		$handler      = new RegisteredDataHandler();
		$post_content = '<!-- wp:paragraph {"fontSize":"small"} --><p>Sample</p><!-- /wp:paragraph -->';

		$reg_data = array(
			'attributes'         => array(
				'block_name'      => 'core/paragraph',
				'block_attribute' => 'fontSize',
			),
			'post_distribute_cb' => function () {
				return 'large';
			},
		);

		$result = $handler->process_registered_block_data( $post_content, $reg_data, array(), array() );
		$this->assertStringContainsString( '"fontSize":"large"', $result );
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

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertSame( $post_content, $result );
	}

	/**
	 * Test process_registered_shortcode_data replaces single shortcode attribute.
	 */
	public function test_process_registered_shortcode_data_single_attribute(): void {
		$handler = new RegisteredDataHandler();

		add_shortcode(
			'test_gallery_tag',
			function () {
				return '';
			}
		);

		$post_content = 'Here is [test_gallery_tag id="10"] and more text';

		$reg_data = array(
			'attributes'         => array(
				'shortcode'           => 'test_gallery_tag',
				'shortcode_attribute' => 'id',
			),
			'post_distribute_cb' => function () {
				return 99;
			},
		);

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertStringContainsString( '[test_gallery_tag id="99"]', $result );

		remove_shortcode( 'test_gallery_tag' );
	}

	/**
	 * Test process_registered_shortcode_data replaces multiple shortcode attributes.
	 */
	public function test_process_registered_shortcode_data_array_attribute(): void {
		$handler = new RegisteredDataHandler();

		add_shortcode(
			'test_card_tag',
			function () {
				return '';
			}
		);

		$post_content = 'Testing [test_card_tag id="5" category="tech"]';

		$reg_data = array(
			'attributes'         => array(
				'shortcode'           => 'test_card_tag',
				'shortcode_attribute' => array( 'id', 'category' ),
			),
			'post_distribute_cb' => function () {
				return array(
					'id'       => '50',
					'category' => 'science',
				);
			},
		);

		$result = $handler->process_registered_shortcode_data( $post_content, $reg_data, array(), array() );
		$this->assertStringContainsString( 'id="50"', $result );
		$this->assertStringContainsString( 'category="science"', $result );

		remove_shortcode( 'test_card_tag' );
	}

	/**
	 * Test process_registered_data exits early when dt_process_extra_data filter returns false.
	 */
	public function test_process_registered_data_bypassed_by_filter(): void {
		$handler   = new RegisteredDataHandler();
		$post_data = array( 'post_title' => 'Original' );

		add_filter( 'dt_process_extra_data', '__return_false' );

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
				'post_distribute_cb' => function ( $extra, $orig ) {
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
		$connection = $this->createMock( ExternalConnection::class );

		$result = $handler->pre_process_registered_data_post( $post_data, $connection );
		$this->assertSame( $post_data, $result );
	}

	/**
	 * Test pre_process_registered_data_post pushes post to external connection.
	 */
	public function test_pre_process_registered_data_post_pushes_external(): void {
		$handler = new RegisteredDataHandler();

		$source_post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Source Post',
			)
		);

		$connection     = $this->createMock( ExternalConnection::class );
		$connection->id = 12;
		$connection->expects( $this->once() )
			->method( 'push' )
			->with( $source_post_id, array( 'post_status' => 'publish' ) )
			->willReturn( array( 'id' => 999 ) );

		$connection->expects( $this->once() )
			->method( 'log_sync' )
			->with( array( 999 => $source_post_id ) );

		$GLOBALS['distributor_registered_data'] = array(
			'featured_post' => array(
				'type' => 'post',
			),
		);

		$post_data = array(
			'post_title'             => 'Parent Post',
			'post_status'            => 'publish',
			'distributor_extra_data' => array(
				'featured_post' => array(
					array(
						'source_post_id' => $source_post_id,
					),
				),
			),
		);

		$result = $handler->pre_process_registered_data_post( $post_data, $connection );

		$this->assertSame( 999, $result['distributor_extra_data']['featured_post'][0]['remote_post_id'] );

		$connection_map = get_post_meta( $source_post_id, 'dt_connection_map', true );
		$this->assertNotEmpty( $connection_map );
		$this->assertSame( 999, $connection_map['external'][12]['post_id'] );
	}
}
