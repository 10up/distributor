<?php

namespace Distributor\Tests;

use Distributor\Tests\Utils\PostGenerator;
use Distributor\InternalConnections\NetworkSiteConnection;

class NetworkSiteConnectionsTest extends \WP_UnitTestCase {
	public \WP_Site $site_obj;
	public NetworkSiteConnection $connection_obj;

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Create a new blog.
		$data = new \stdClass();
		$data->blog = self::factory()->blog->create_and_get( array(
			'blog_id' => 2,
			'domain'  => 'origin.example.org',
		) );

		// $this->site_obj = get_site();
		$this->site_obj = $data->blog;

		$this->connection_obj = new NetworkSiteConnection( $this->site_obj ); // Setting a connection for a second site.
	}

	// public function 

	/**
	 * Push returns an post array on success, Instance of WP Error on failure.
	 *
	 * @since  0.8
	 * @group  NetworkSiteConnection
	 */
	public function test_push(): void {
		$dt_post_gen = new PostGenerator();
		$dt_post     = $dt_post_gen->create()->getPost();

		// Push current site's post to the second site.
		$this->assertIsArray( $this->connection_obj->push( $dt_post->ID ) );
	}

	/**
	 * Pull returns an array of Post IDs on success. This test simulates sending an
	 * array containing three IDs (integers) will receive an array containing
	 * three integers.
	 *
	 * @since  0.8
	 * @covers \Distributor\InternalConnections\NetworkSiteConnection::pull
	 * @dataProvider network_connection_data_provider
	 * @group  NetworkSiteConnection
	 */
	public function test_pull( $data ): void {
		$items = array(
			array( 'remote_post_id' => $data->post->ID ),
		);

		$connection_obj = new NetworkSiteConnection( $data->blog );
		$this->assertIsArray( $connection_obj->pull( $items ) );
	}

	/**
	 * Verifies that when passed no id the request can still return items
	 *
	 * @since 0.8
	 * @covers \Distributor\InternalConnections\NetworkSiteConnection::remote_get
	 * @group NetworkSiteConnection
	 */
	public function test_remote_get_empty_id(): void {
		$this->assertArrayHasKey( 'total_items', $this->connection_obj->remote_get() );
	}

	/**
	 * Verifies that the remote_get method returns an array containing the post title.
	 *
	 * @since 0.8
	 * @covers \Distributor\InternalConnections\NetworkSiteConnection::remote_get
	 * @dataProvider network_connection_data_provider
	 * @group NetworkSiteConnection
	 */
	public function test_remote_get( $data ): void {
		$connection_obj = new NetworkSiteConnection( $data->blog );
		$this->assertArrayHasKey(
			'post_title', (array) $connection_obj->remote_get(
				[
					'id' => $data->post->ID,
				]
			)
		);
	}

	/**
	 * This method provides data for the internal connection tests.
	 */
	public function network_connection_data_provider(): array {
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
