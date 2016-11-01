<?php

namespace Distributor;

class PullListTableTest extends \TestCase {

	public function setUp(){

		$this->table_obj = new PullListTable;

        $_GET = [];

	}

	/**
	 * Test that the given HTML needed, and array keys are available to create
	 * a column.
	 *
	 * @since 0.8
	 */
	public function test_columns(){

		$this->assertTrue( empty( array_diff( $this->table_obj->get_columns(), [
			'cb'           => '<input type="checkbox" />',
			'name'         => '',
			'content_type' => '',
			'date'         => '',
		] ) ) );

	}

	/**
	 * Verify that our table has the following key's present, which allow for
	 * sorting.
	 *
	 * @since 0.8
	 */
	public function test_sortable_columns(){

		$sortable_columns = $this->table_obj->get_sortable_columns();

		$proposed_array = [
			'name' => '',
			'date' => [
				0 => 'date',
				1 => 1,
			]
		];

		$this->assertArrayHasKey( 'name', $sortable_columns );
		$this->assertArrayHasKey( 'date', $sortable_columns );

	}

	/**
	 * Verifies that the column_date produces the desired output. This does
	 * not test the following conditionals; that global $_GET['status']
	 * is set, $post->post_date, $post->post_status, or $mode
	 *
	 * @since  0.8
	 *
	 */
	public function test_column_date(){

		\WP_Mock::userFunction('get_the_time', [
			'return' => ''
		]);
		\WP_Mock::userFunction('get_post_time', [
			'return' => ''
		]);
		\WP_Mock::userFunction('mysql2date', [
			'return' => ''
		]);

		$this->table_obj->column_date( (object) [
			'ID' => 123,
			'post_date' => '',
			'post_status' => '',
		] );

		$this->expectOutputString( '<br /><abbr title=""></abbr>' );

	}

	/**
	 * If column_default is set for "name" only the given post_title is returned
	 * if column_default is set for "content_type" AND we do have post type
	 * labels set in our object we return the singular name.
	 *
	 * @since 0.8
	 * @return [type] [description]
	 */
	public function test_column_default(){

		$this->assertEquals( $this->table_obj->column_default( [
			'post_title' => 'My title'
		], 'name' ), 'My title' );

		\WP_Mock::userFunction( 'get_post_type_object', [
			'return' => (object) [
				'labels' => (object) [
					'singular_name' => 'Singular Name'
				]
			]
		] );

		$this->assertEquals( $this->table_obj->column_default( (object) [
			'post_type' => 'some_post_type'
		], 'content_type' ), 'Singular Name' );

	}

	/**
	 * If column_default is set to for the column "name" and has no post_type
	 * labels set only the post_type is returned. Hence we test against
	 * a given post_type.
	 *
	 * @since  0.8
	 */
	public function test_column_default_content_type_empty(){

		\WP_Mock::userFunction( 'get_post_type_object', [
			'return' => ''
		] );

		$content_type = $this->table_obj->column_default( (object) [
			'post_type' => 'some_post_type'
		], 'content_type' );

		$this->assertEquals( $content_type, 'some_post_type' );

	}

	/**
	 * Skipped, as there is ample HTML in this that may change.
	 *
	 * @since 0.8
	 */
	public function test_column_name() {}

	/**
	 * Skipped.
	 * @since 0.8
	 */
	public function test_prepare_items() {}

	/**
	 * Verify that our checkbox HTML is as expected.
	 *
	 * @since 0.8
	 */
	public function test_column_cb() {

		\WP_Mock::userFunction( '_draft_or_post_title' );

		$this->table_obj->column_cb( (object) [
			'ID' => '123',
		] );

		$this->expectOutputString('		<label class="screen-reader-text" for="cb-select-123"></label>
		<input id="cb-select-123" type="checkbox" name="post[]" value="123" />
		<div class="locked-indicator"></div>
		');

	}

	/**
	 * Verify that the given actions are available via the "bulk actions" select
	 * box.
	 *
	 * @since 0.8
	 */
	public function test_get_bulk_actions() {

		$_GET['status'] = 'new';

		$this->assertTrue( empty( array_diff( $this->table_obj->get_bulk_actions(), [
			'bulk-syndicate' => '',
			'bulk-skip' => ''
		] ) ) );

		$_GET['status'] = 'skipped';

		$this->assertTrue( empty( array_diff( $this->table_obj->get_bulk_actions(), [
			'bulk-syndicate' => '',
		] ) ) );

	}

}
