<?php
/**
 * Template Tags Integration Tests
 *
 * @package distributor
 */

namespace Distributor\IntegrationTests;

use WP_UnitTestCase;

/**
 * Integration tests for includes/template-tags.php.
 *
 * @group template-tags
 */
class Test_TemplateTags extends WP_UnitTestCase {

	/**
	 * Store the original global post.
	 *
	 * @var \WP_Post|null
	 */
	protected $original_global_post = null;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->original_global_post = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
	}

	/**
	 * Tear down after each test.
	 */
	public function tearDown(): void {
		$GLOBALS['post'] = $this->original_global_post;
		parent::tearDown();
	}

	/**
	 * Test distributor_is_distributed returns false for a standard non-distributed post.
	 */
	public function test_distributor_is_distributed_returns_false_for_local_post(): void {
		$post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Standard Local Post',
			)
		);

		$this->assertFalse( distributor_is_distributed( $post_id ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( $post_id ) );
	}

	/**
	 * Test distributor_is_distributed returns true for a post with dt_original_post_id meta.
	 */
	public function test_distributor_is_distributed_returns_true_with_original_post_id(): void {
		$post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Distributed Post with Original Post ID',
			)
		);

		update_post_meta( $post_id, 'dt_original_post_id', 12345 );

		$this->assertTrue( distributor_is_distributed( $post_id ) );
		$this->assertTrue( \Distributor\Utils\is_distributed_post( $post_id ) );
	}

	/**
	 * Test distributor_is_distributed returns true for a post with dt_original_source_id meta.
	 */
	public function test_distributor_is_distributed_returns_true_with_original_source_id(): void {
		$post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Distributed Post with Original Source ID',
			)
		);

		update_post_meta( $post_id, 'dt_original_source_id', 42 );

		$this->assertTrue( distributor_is_distributed( $post_id ) );
		$this->assertTrue( \Distributor\Utils\is_distributed_post( $post_id ) );
	}

	/**
	 * Test distributor_is_distributed accepts a WP_Post object.
	 */
	public function test_distributor_is_distributed_with_post_object(): void {
		$post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Distributed Post Object',
			)
		);

		update_post_meta( $post_id, 'dt_original_post_id', 54321 );
		$post_obj = get_post( $post_id );

		$this->assertTrue( distributor_is_distributed( $post_obj ) );
		$this->assertTrue( \Distributor\Utils\is_distributed_post( $post_obj ) );
	}

	/**
	 * Test distributor_is_distributed defaults to the global post in The Loop context.
	 */
	public function test_distributor_is_distributed_with_global_post_in_loop(): void {
		$distributed_post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Global Distributed Post',
			)
		);
		update_post_meta( $distributed_post_id, 'dt_original_post_id', 9876 );

		$local_post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Global Local Post',
			)
		);

		// Set global post to distributed post.
		$GLOBALS['post'] = get_post( $distributed_post_id );
		$this->assertTrue( distributor_is_distributed() );
		$this->assertTrue( \Distributor\Utils\is_distributed_post() );

		// Set global post to local post.
		$GLOBALS['post'] = get_post( $local_post_id );
		$this->assertFalse( distributor_is_distributed() );
		$this->assertFalse( \Distributor\Utils\is_distributed_post() );
	}

	/**
	 * Test distributor_is_distributed maintains true even when a post is unlinked.
	 */
	public function test_distributor_is_distributed_when_post_is_unlinked(): void {
		$post_id = $this->factory()->post->create(
			array(
				'post_title' => 'Unlinked Distributed Post',
			)
		);

		update_post_meta( $post_id, 'dt_original_post_id', 777 );
		update_post_meta( $post_id, 'dt_unlinked', true );

		// The post originated from distribution, so distributor_is_distributed remains true.
		$this->assertTrue( distributor_is_distributed( $post_id ) );

		// While distributor_is_unlinked correctly reports the unlinked status.
		$this->assertTrue( distributor_is_unlinked( $post_id ) );
	}

	/**
	 * Test distributor_is_distributed handles invalid, empty, or non-existent inputs safely.
	 */
	public function test_distributor_is_distributed_safe_fallbacks(): void {
		$GLOBALS['post'] = null;

		// Null without global post.
		$this->assertFalse( distributor_is_distributed( null ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( null ) );

		// Zero ID.
		$this->assertFalse( distributor_is_distributed( 0 ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( 0 ) );

		// Negative ID.
		$this->assertFalse( distributor_is_distributed( -1 ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( -1 ) );

		// Non-existent ID.
		$this->assertFalse( distributor_is_distributed( 99999999 ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( 99999999 ) );

		// Invalid string.
		$this->assertFalse( distributor_is_distributed( '' ) );
		$this->assertFalse( \Distributor\Utils\is_distributed_post( '' ) );
	}

	/**
	 * Test distributor_get_the_connection_source returns expected structured data.
	 */
	public function test_distributor_get_the_connection_source(): void {
		$post_id = $this->factory()->post->create();
		update_post_meta( $post_id, 'dt_original_site_name', 'Source Site' );
		update_post_meta( $post_id, 'dt_original_post_url', 'https://source.example.com/post' );
		update_post_meta( $post_id, 'dt_original_site_url', 'https://source.example.com' );

		$source = distributor_get_the_connection_source( $post_id );

		$this->assertIsArray( $source );
		$this->assertSame( 'Source Site', $source['site_name'] );
		$this->assertSame( 'https://source.example.com/post', $source['original_post_url'] );
		$this->assertSame( 'https://source.example.com', $source['url'] );
		$this->assertFalse( $source['post_is_unlinked'] );
	}
}
