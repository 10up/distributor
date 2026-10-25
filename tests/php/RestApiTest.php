<?php
/**
 * Tests for the receive hooks on posts pushed through the REST API.
 *
 * @package distributor
 */

namespace Distributor\RestApi;

use WP_Mock\Tools\TestCase;

require_once __DIR__ . '/../../includes/rest-api.php';

class RestApiTest extends TestCase {

	/**
	 * Build a request carrying the given parameters.
	 *
	 * @param array $params Request parameters.
	 * @return \WP_REST_Request
	 */
	protected function get_request( array $params ) {
		$request = new \WP_REST_Request( 'POST', '/wp/v2/posts' );

		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}

		return $request;
	}

	/**
	 * The context the receive hooks get for a push of original post 7.
	 *
	 * @param bool $is_update True when the push updates an existing post.
	 * @param int  $post_id   The receiving site's post ID.
	 * @return array
	 */
	protected function get_push_context( $is_update, $post_id ) {
		return [
			'source'           => 'push',
			'connection_type'  => 'external',
			'is_update'        => $is_update,
			'post_id'          => $post_id,
			'original_post_id' => 7,
			'connection'       => null,
		];
	}

	/**
	 * Mock the functions process_distributor_attributes() calls.
	 */
	protected function mock_process_attributes_dependencies() {
		\WP_Mock::userFunction( 'update_post_meta' );
		\WP_Mock::userFunction( 'sanitize_text_field', [ 'return_arg' => 0 ] );
		\WP_Mock::userFunction( 'esc_url_raw', [ 'return_arg' => 0 ] );
	}

	/**
	 * Request data for a push carrying meta, terms and media.
	 *
	 * @return array
	 */
	protected function get_full_push_request_params() {
		return [
			'distributor_original_source_id' => 3,
			'distributor_remote_post_id'     => 7,
			'distributor_meta'               => [ 'origin_key' => [ 'origin value' ] ],
			'distributor_terms'              => [ 'category' => [ 'news' ] ],
			'distributor_media'              => [ [ 'source_url' => 'https://example.com/a.jpg' ] ],
		];
	}

	/**
	 * dt_receive_post_args filters a new pushed post.
	 *
	 * @since x.x.x
	 * @group RestApi
	 */
	public function test_filter_distributor_content_applies_receive_post_args() {
		\WP_Mock::userFunction( 'distributor_get_registered_data', [ 'return' => [] ] );

		\WP_Mock::onFilter( 'dt_receive_post_args' )
			->with(
				[
					'post_title' => 'Title',
					'post_type'  => 'post',
				],
				$this->get_push_context( false, 0 )
			)
			->reply(
				[
					'post_title' => 'Filtered title',
					'post_type'  => 'post',
				]
			);

		$prepared_post = (object) [
			'post_title' => 'Title',
			'post_type'  => 'post',
		];

		$actual = filter_distributor_content(
			$prepared_post,
			$this->get_request(
				[
					'distributor_original_source_id' => 3,
					'distributor_remote_post_id'     => 7,
				]
			)
		);

		$this->assertIsObject( $actual );
		$this->assertSame( 'Filtered title', $actual->post_title );
	}

	/**
	 * A push to an existing post marks the context as an update.
	 *
	 * @since x.x.x
	 * @group RestApi
	 */
	public function test_filter_distributor_content_update_context() {
		\WP_Mock::userFunction( 'distributor_get_registered_data', [ 'return' => [] ] );

		\WP_Mock::onFilter( 'dt_receive_post_args' )
			->with(
				[
					'ID'         => 12,
					'post_title' => 'Title',
				],
				$this->get_push_context( true, 12 )
			)
			->reply(
				[
					'ID'         => 12,
					'post_title' => 'Filtered title',
				]
			);

		$prepared_post = (object) [
			'ID'         => 12,
			'post_title' => 'Title',
		];

		$actual = filter_distributor_content(
			$prepared_post,
			$this->get_request(
				[
					'distributor_original_source_id' => 3,
					'distributor_remote_post_id'     => 7,
				]
			)
		);

		$this->assertSame( 'Filtered title', $actual->post_title );
	}

	/**
	 * REST requests that are not Distributor pushes skip dt_receive_post_args.
	 *
	 * @since x.x.x
	 * @group RestApi
	 */
	public function test_filter_distributor_content_ignores_other_requests() {
		\WP_Mock::onFilter( 'dt_receive_post_args' )
			->with(
				[ 'post_title' => 'Title' ],
				[
					'source'           => 'push',
					'connection_type'  => 'external',
					'is_update'        => false,
					'post_id'          => 0,
					'original_post_id' => 0,
					'connection'       => null,
				]
			)
			->reply( [ 'post_title' => 'Filtered title' ] );

		$prepared_post = (object) [ 'post_title' => 'Title' ];

		$actual = filter_distributor_content( $prepared_post, $this->get_request( [] ) );

		$this->assertSame( 'Title', $actual->post_title );
	}

	/**
	 * The meta, terms and media filters change the data saved for a new push,
	 * then dt_receive_post fires.
	 *
	 * @since x.x.x
	 * @group RestApi
	 */
	public function test_process_distributor_attributes_applies_receive_hooks() {
		$this->mock_process_attributes_dependencies();

		// The REST API passes `$update` as true when creating a post.
		$context = $this->get_push_context( false, 12 );

		\WP_Mock::onFilter( 'dt_receive_post_meta' )
			->with( [ 'origin_key' => [ 'origin value' ] ], $context )
			->reply( [ 'local_key' => [ 'local value' ] ] );
		\WP_Mock::onFilter( 'dt_receive_post_terms' )
			->with( [ 'category' => [ 'news' ] ], $context )
			->reply( [ 'category' => [ 'local-news' ] ] );
		\WP_Mock::onFilter( 'dt_receive_post_media' )
			->with( [ [ 'source_url' => 'https://example.com/a.jpg' ] ], $context )
			->reply( [] );

		\WP_Mock::userFunction(
			'Distributor\Utils\set_meta',
			[
				'times' => 1,
				'args'  => [ 12, [ 'local_key' => [ 'local value' ] ] ],
			]
		);
		\WP_Mock::userFunction(
			'Distributor\Utils\set_taxonomy_terms',
			[
				'times' => 1,
				'args'  => [ 12, [ 'category' => [ 'local-news' ] ] ],
			]
		);
		\WP_Mock::userFunction(
			'Distributor\Utils\set_media',
			[
				'times' => 1,
				'args'  => [ 12, [] ],
			]
		);

		\WP_Mock::expectAction( 'dt_receive_post', 12, $context );

		process_distributor_attributes(
			new \WP_Post( (object) [ 'ID' => 12 ] ),
			$this->get_request( $this->get_full_push_request_params() ),
			true
		);

		$this->assertConditionsMet();
	}

	/**
	 * Returning false from the meta, terms and media filters skips each step
	 * on an updated push, and keeps the featured image.
	 *
	 * @since x.x.x
	 * @group RestApi
	 */
	public function test_process_distributor_attributes_skips_data_when_filters_return_false() {
		$this->mock_process_attributes_dependencies();

		$context = $this->get_push_context( true, 12 );

		\WP_Mock::onFilter( 'dt_receive_post_meta' )
			->with( [ 'origin_key' => [ 'origin value' ] ], $context )
			->reply( false );
		\WP_Mock::onFilter( 'dt_receive_post_terms' )
			->with( [ 'category' => [ 'news' ] ], $context )
			->reply( false );
		\WP_Mock::onFilter( 'dt_receive_post_media' )
			->with( [ [ 'source_url' => 'https://example.com/a.jpg' ] ], $context )
			->reply( false );

		\WP_Mock::userFunction( 'Distributor\Utils\set_meta', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'Distributor\Utils\set_taxonomy_terms', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'Distributor\Utils\set_media', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'delete_post_meta', [ 'times' => 0 ] );

		\WP_Mock::expectAction( 'dt_receive_post', 12, $context );

		process_distributor_attributes(
			new \WP_Post( (object) [ 'ID' => 12 ] ),
			$this->get_request( $this->get_full_push_request_params() ),
			false
		);

		$this->assertConditionsMet();
	}
}
