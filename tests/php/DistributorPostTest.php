<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;

/**
 * Tests for the DistributorPost class.
 *
 * @covers \Distributor\DistributorPost
 * @group Post
 */
class DistributorPostTest extends TestCase {

	/**
	 * Set up with WP_Mock
	 *
	 * Set up common mocks required for multiple tests.
	 *
	 * @since x.x.x
	 */
	public function setUp(): void {
		parent::setUp();
		$this->setup_common();
	}

	public function setup_common() {
		\WP_Mock::userFunction(
			'apply_filters_deprecated',
			[
				'return' => function( $name, $args ) {
					return $args[0];
				},
			]
		);

		// Return voids.
		\WP_Mock::userFunction( '_prime_post_caches' );
		\WP_Mock::userFunction( 'update_object_term_cache' );
		\WP_Mock::userFunction( 'update_postmeta_cache' );
	}

	/**
	 * Helper function to mock get_post.
	 */
	public function setup_post_mock( $post_overrides = array() ) {
		$defaults = array(
			'ID' => 1,
			'post_title' => 'Test Post',
			'post_content' => 'Test Content',
			'post_excerpt' => 'Test Excerpt',
			'post_status' => 'publish',
			'post_type' => 'post',
			'post_author' => 1,
			'post_date' => '2020-01-01 00:00:00',
			'post_date_gmt' => '2020-01-01 00:00:00',
			'post_modified' => '2020-01-01 00:00:00',
			'post_modified_gmt' => '2020-01-01 00:00:00',
			'post_parent' => 0,
			'post_mime_type' => '',
			'comment_count' => 0,
			'comment_status' => 'open',
			'ping_status' => 'open',
			'guid' => 'http://example.org/?p=1',
			'menu_order' => 0,
			'pinged' => '',
			'to_ping' => '',
			'post_password' => '',
			'post_name' => 'test-post',
			'post_content_filtered' => '',
		);

		$post = array_merge( $defaults, $post_overrides );

		\WP_Mock::userFunction(
			'get_post',
			array(
				'return' => (object) $post,
			)
		);
	}

	/**
	 * Helper function to mock get_post_meta.
	 */
	public function setup_post_meta_mock( $post_meta ) {
		$get_post_meta = function( $post_id, $key = '', $single = false ) use ( $post_meta ) {
			if ( empty( $key ) ) {
				return $post_meta;
			}

			if ( isset( $post_meta[ $key ] ) ) {
				if ( $single ) {
					return $post_meta[ $key ][0];
				}
				return $post_meta[ $key ];
			}

			return '';
		};

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'return' => $get_post_meta,
			)
		);
	}

	/**
	 * Test the DistributorPost object for internal connections.
	 *
	 * @covers ::__construct
	 * @runInSeparateProcess
	 */
	public function test_internal_connection() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
			)
		);

		\WP_Mock::userFunction(
			'get_current_blog_id',
			array(
				'return' => 1,
			)
		);

		\WP_Mock::userFunction( 'switch_to_blog' );
		\WP_Mock::userFunction( 'restore_current_blog' );

		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'name':
							return 'Test Internal Origin';
						default:
							return '';
					}
				},
			)
		);

		// Generic values for the origin site.
		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://origin.example.org/?p=10',
			)
		);

		\WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'http://origin.example.org/',
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 2, $dt_post->connection_id, 'Origin site ID does not match expected value.' );
		$this->assertSame( 'bidirectional', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'internal', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test Internal Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers ::__construct
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pushed_post() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pushed Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '1' ),
				'dt_original_source_id'     => array( '2' ),
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->connection_id, 'Origin connection ID does not match source URL.' );
		$this->assertSame( 'pushed', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pushed Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object for external, pushed posts.
	 *
	 * @covers ::__construct
	 * @runInSeparateProcess
	 */
	public function test_external_connection_with_pulled_post() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 10, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 'http://origin.example.org/?p=10', $dt_post->original_post_url, 'Origin post URL does not match expected value.' );
		$this->assertSame( 3, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( 'pulled', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( 'external', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://origin.example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Test External, Pulled Origin', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( false, $dt_post->is_source, 'Post is incorrectly marked as the source.' );
	}

	/**
	 * Test the DistributorPost object a source post.
	 *
	 * @covers ::__construct
	 * @runInSeparateProcess
	 */
	public function test_source_post() {
		$this->setup_post_mock();
		// There is no post meta to mock for a source post.
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'home_url',
			array(
				'return' => 'http://example.org/',
			)
		);

		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => function( $info ) {
					switch ( $info ) {
						case 'name':
							return 'Example Dot Org';
						default:
							return '';
					}
				},
			)
		);

		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 1, $dt_post->original_post_id, 'Origin post ID does not match expected value.' );
		$this->assertSame( true, $dt_post->is_linked, 'Origin post is not linked.' );
		$this->assertSame( 0, $dt_post->connection_id, 'Origin connection ID does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_direction, 'Connection direction does not match expected value.' );
		$this->assertSame( '', $dt_post->connection_type, 'Connection type is incorrect.' );
		$this->assertSame( 'http://example.org/', $dt_post->source_site['home_url'], 'Original home_url does not match expected value.' );
		$this->assertSame( 'Example Dot Org', $dt_post->source_site['name'], 'Original site name does not match expected value.' );
		$this->assertSame( true, $dt_post->is_source, 'Post is incorrectly marked as distributed.' );
	}

	/**
	 * Test the get_the_id() method.
	 *
	 * @covers ::get_the_id
	 * @runInSeparateProcess
	 */
	public function test_get_the_id() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 1, $dt_post->get_the_id() );
	}

	/**
	 * Test the get_permalink() method.
	 *
	 * @covers ::get_permalink
	 * @runInSeparateProcess
	 */
	public function test_get_permalink() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 'http://example.org/?p=1', $dt_post->get_permalink() );
	}

	/**
	 * Test the get_post_type() method.
	 *
	 * @covers ::get_post_type
	 * @runInSeparateProcess
	 */
	public function test_get_post_type() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_post_type',
			array(
				'return' => 'post',
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 'post', $dt_post->get_post_type() );
	}

	/**
	 * Test the get_post_thumbnail_id() method.
	 *
	 * @covers ::get_post_thumbnail_id
	 * @runInSeparateProcess
	 */
	public function test_get_post_thumbnail_id() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => 4,
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 4, $dt_post->get_post_thumbnail_id() );
	}

	/**
	 * Test the get_post_thumbnail_url() method.
	 *
	 * @covers ::get_post_thumbnail_url
	 * @runInSeparateProcess
	 */
	public function test_get_post_thumbnail_url() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_the_post_thumbnail_url',
			array(
				'return' => 'http://example.org/wp-content/uploads/2018/01/featured-image.jpg',
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( 'http://example.org/wp-content/uploads/2018/01/featured-image.jpg', $dt_post->get_post_thumbnail_url() );
	}

	/**
	 * Test the get_the_post_thumbnail() method.
	 *
	 * @covers ::get_the_post_thumbnail
	 * @runInSeparateProcess
	 */
	public function test_get_the_post_thumbnail() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );
		$thumbnail = '<img width="1200" height="900" src="//ms-distributor.local/wp-content/uploads/sites/3/2022/12/daveed-diggs.jpg" class="attachment-post-thumbnail size-post-thumbnail wp-post-image" alt="" />';

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_the_post_thumbnail',
			array(
				'return' => $thumbnail,
			)
		);
		$dt_post = new DistributorPost( 1 );

		$this->assertSame( $thumbnail, $dt_post->get_the_post_thumbnail() );
	}

	/**
	 * Test the get_meta() method.
	 *
	 * @covers ::get_meta
	 * @runInSeparateProcess
	 */
	public function test_get_meta() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
				'distributable_meta_data'   => array( 'This will be distributed.' ),
			)
		);

		$dt_post = new DistributorPost( 1 );
		$excluded_meta      = Utils\excluded_meta();
		$distributable_meta = $dt_post->get_meta();

		$this->assertArrayHasKey( 'distributable_meta_data', $distributable_meta, 'Distributable meta should be included.' );

		foreach( $excluded_meta as $meta_key ) {
			$this->assertArrayNotHasKey( $meta_key, $distributable_meta, "Excluded meta '{$meta_key}' should not be included." );
		}
	}

	/**
	 * Test the get_terms() method.
	 *
	 * @covers ::get_terms
	 * @runInSeparateProcess
	 */
	public function test_get_terms() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pulled Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '' ),
				'dt_original_source_id'     => array( '3' ),
				'distributable_meta_data'   => array( 'This will be distributed.' ),
			)
		);

		\WP_Mock::userFunction(
			'get_taxonomies',
			array(
				'return' => array( 'category', 'post_tag' ),
			)
		);

		\WP_Mock::userFunction(
			'wp_get_object_terms',
			array(
				'return_in_order' => array(
					array(
						(object) array(
							'term_id'          => 1,
							'name'             => 'Test Category',
							'slug'             => 'test-category',
							'term_group'       => 0,
							'term_taxonomy_id' => 1,
							'taxonomy'         => 'category',
							'description'      => '',
							'parent'           => 0,
							'count'            => 1,
							'filter'           => 'raw',
						)
					),
					array(
						(object) array(
							'term_id'          => 2,
							'name'             => 'Test Tag',
							'slug'             => 'test-tag',
							'term_group'       => 0,
							'term_taxonomy_id' => 2,
							'taxonomy'         => 'post_tag',
							'description'      => '',
							'parent'           => 0,
							'count'            => 1,
							'filter'           => 'raw'
						)
					),
				),
			)
		);

		$dt_post = new DistributorPost( 1 );
		$distributable_terms = $dt_post->get_terms();

		$expected_terms = array(
			'category' => array(
				(object) array(
					'term_id'          => 1,
					'name'             => 'Test Category',
					'slug'             => 'test-category',
					'term_group'       => 0,
					'term_taxonomy_id' => 1,
					'taxonomy'         => 'category',
					'description'      => '',
					'parent'           => 0,
					'count'            => 1,
					'filter'           => 'raw',
				)
			),
			'post_tag' => array(
				(object) array(
					'term_id'          => 2,
					'name'             => 'Test Tag',
					'slug'             => 'test-tag',
					'term_group'       => 0,
					'term_taxonomy_id' => 2,
					'taxonomy'         => 'post_tag',
					'description'      => '',
					'parent'           => 0,
					'count'            => 1,
					'filter'           => 'raw'
				)
			),
		);

		$this->assertEquals( $expected_terms, $distributable_terms );
	}

	/**
	 * Test the get_media() method with blocks.
	 *
	 * @covers ::get_media
	 * @covers ::parse_media_blocks
	 * @covers ::parse_blocks_for_attachment_id
	 * @runInSeparateProcess
	 */
	public function test_get_media_with_blocks() {
		$post_content = '<!-- wp:image {"id":11,"sizeSlug":"large","linkDestination":"none"} -->
		<figure class="wp-block-image size-large"><img src="//xu-distributor.local/wp-content/uploads/2022/12/deh-platt-1024x683.jpg" alt="" class="wp-image-11"/></figure>
		<!-- /wp:image -->
		<!-- wp:audio {"id":12} -->
		<figure class="wp-block-audio"><audio controls src="//xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3"></audio></figure>
		<!-- /wp:audio -->
		<!-- wp:video {"id":13} -->
		<figure class="wp-block-video"><video controls src="//xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4"></video></figure>
		<!-- /wp:video -->
		';

		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_post',
			array(
				'return_in_order' => array(
					(object) array(
						'ID'           => 1,
						'post_title'   => 'Block post with media',
						'post_content' => $post_content,
						'post_excerpt' => '',
						'guid'         => 'http://example.org/?p=1',
						'post_name'    => 'test-post',
					),
					(object) array(
						'ID'             => 11,
						'post_title'     => 'deh-platt',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=11',
						'post_name'      => 'deh-platt',
						'post_parent'    => 0,
						'post_mime_type' => 'image/jpeg',
					),
					(object) array(
						'ID'             => 12,
						'post_title'     => 'mp3-5mg',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=12',
						'post_name'      => 'mp3-5mg',
						'post_parent'    => 0,
						'post_mime_type' => 'audio/mpeg',
					),
					(object) array(
						'ID'             => 13,
						'post_title'     => 'sample-mp4',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=13',
						'post_name'      => 'sample-mp4',
						'post_parent'    => 0,
						'post_mime_type' => 'video/mp4',
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_attachment_is_image',
			array(
				'return_in_order' => array(
					true,
					false,
					false,
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_metadata',
			array(
				'return_in_order' => array(
					array(
						'file'     => '2022/12/deh-platt.jpg',
						'width'    => 1024,
						'height'   => 683,
						'filesize' => 404298,
						'sizes'    => array(
							'thumbnail'    => array(
								'file'      => 'deh-platt-150x150.jpg',
								'width'     => 150,
								'height'    => 150,
								'mime-type' => 'image/jpeg',
							),
							'medium'       => array(
								'file'      => 'deh-platt-300x200.jpg',
								'width'     => 300,
								'height'    => 200,
								'mime-type' => 'image/jpeg',
							),
							'medium_large' => array(
								'file'      => 'deh-platt-768x512.jpg',
								'width'     => 768,
								'height'    => 512,
								'mime-type' => 'image/jpeg',
							),
							'large'        => array(
								'file'      => 'deh-platt-1024x683.jpg',
								'width'     => 1024,
								'height'    => 683,
								'mime-type' => 'image/jpeg',
							),
						),
					),
					array (
						'dataformat'        => 'mp3',
						'channels'          => 2,
						'sample_rate'       => 44100,
						'bitrate'           => 320000,
						'channelmode'       => 'stereo',
						'bitrate_mode'      => 'cbr',
						'codec'             => 'LAME',
						'encoder'           => 'LAME3.99r',
						'lossless'          => false,
						'encoder_options'   => '--preset insane',
						'compression_ratio' => 0.22675736961451248,
						'fileformat'        => 'mp3',
						'filesize'          => 5289384,
						'mime_type'         => 'audio/mpeg',
						'length'            => 132,
						'length_formatted'  => '2:12',
						'genre'             => 'Cinematic',
						'album'             => 'YouTube Audio Library',
						'title'             => 'Impact Moderato',
						'artist'            => 'Kevin MacLeod',
					),
					array (
						'filesize'          => 1570024,
						'mime_type'         => 'video/mp4',
						'length'            => 31,
						'length_formatted'  => '0:31',
						'width'             => 480,
						'height'            => 270,
						'fileformat'        => 'mp4',
						'dataformat'        => 'quicktime',
						'audio' => array (
							'dataformat'      => 'mp4',
							'codec'           => 'ISO/IEC 14496-3 AAC',
							'sample_rate'     => 48000.0,
							'channels'        => 2,
							'bits_per_sample' => 16,
							'lossless'        => false,
							'channelmode'     => 'stereo',
						),
						'created_timestamp' => 1438938782,
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_url',
			array(
				'return_in_order' => array(
					'http://xu-distributor.local/wp-content/uploads/2022/12/deh-platt.jpg',
					'http://xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3',
					'http://xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4',
				),
			)
		);

		\WP_Mock::userFunction(
			'get_attached_file',
			array(
				'return_in_order' => array(
					'/var/www/html/wp-content/uploads/2022/12/deh-platt.jpg',
					'/var/www/html/wp-content/uploads/2022/12/mp3-5mg.mp3',
					'/var/www/html/wp-content/uploads/2022/12/sample-mp4.mp4',
				),
			)
		);

		\WP_Mock::userFunction(
			'has_blocks',
			array(
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return_in_order' => array(
					'http://xu-distributor.local/?p=2',
					'http://xu-distributor.local/?p=11',
					'http://xu-distributor.local/?p=12',
					'http://xu-distributor.local/?p=13',
				),
			),
		);

		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'return' => false
			)
		);

		\WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'return' => false
			)
		);

		$blocks = array(
			array(
				'blockName'    => 'core/image',
				'attrs'        => array(
					'id'              => 11,
					'sizeSlug'        => 'large',
					'linkDestination' => 'none',
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-image size-large"><img src="//xu-distributor.local/wp-content/uploads/2022/12/deh-platt-1024x683.jpg" alt="" class="wp-image-11"/></figure>',
				'innerContent' => array(
					'<figure class="wp-block-image size-large"><img src="//xu-distributor.local/wp-content/uploads/2022/12/deh-platt-1024x683.jpg" alt="" class="wp-image-11"/></figure>',
				),
			),
			array(
				'blockName'    => 'core/audio',
				'attrs'        => array(
					'id' => 12,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-audio"><audio controls src="//xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3"></audio></figure>',
				'innerContent' => array(
					'<figure class="wp-block-audio"><audio controls src="//xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3"></audio></figure>',
				),
			),
			array(
				'blockName'    => 'core/video',
				'attrs'        => array(
					'id' => 13,
				),
				'innerBlocks'  => array(),
				'innerHTML'    => '<figure class="wp-block-video"><video controls src="//xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4"></video></figure>',
				'innerContent' => array(
					'<figure class="wp-block-video"><video controls src="//xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4"></video></figure>',
				),
			),
		);

		\WP_Mock::userFunction(
			'parse_blocks',
			array(
				'return' => $blocks,
			)
		);


		$dt_post = new DistributorPost( 1 );
		$post_media_actual = $dt_post->get_media();

		$post_media_expected = array(
			array(
				'id'            => 11,
				'title'         => 'deh-platt',
				'featured'      => false,
				'description'   => array(
					'raw'      => '',
					'rendered' => '',
				),
				'caption' => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'image',
				'mime_type'     => 'image/jpeg',
				'post'          => 0,
				'source_url'    => 'http://xu-distributor.local/wp-content/uploads/2022/12/deh-platt.jpg',
				'source_file'   => '/var/www/html/wp-content/uploads/2022/12/deh-platt.jpg',
				'meta'          => array(),
				'media_details' => array(
					'width'    => 1024,
					'height'   => 683,
					'file'     => '2022/12/deh-platt.jpg',
					'filesize' => 404298,
					'sizes'    => array(
						'thumbnail' => array(
							'file'      => 'deh-platt-150x150.jpg',
							'width'     => 150,
							'height'    => 150,
							'mime-type' => 'image/jpeg',
						),
						'medium' => array(
							'file'      => 'deh-platt-300x200.jpg',
							'width'     => 300,
							'height'    => 200,
							'mime-type' => 'image/jpeg',
						),
						'medium_large' => array(
							'file'      => 'deh-platt-768x512.jpg',
							'width'     => 768,
							'height'    => 512,
							'mime-type' => 'image/jpeg',
						),
						'large' => array(
							'file'      => 'deh-platt-1024x683.jpg',
							'width'     => 1024,
							'height'    => 683,
							'mime-type' => 'image/jpeg',
						),
					),
				),
			),
			array(
				'id'            => 12,
				'title'         => 'mp3-5mg',
				'featured'      => false,
				'description'   => array(
					'raw'      => '',
					'rendered' => '',
				),
				'caption'       => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'file',
				'mime_type'     => 'audio/mpeg',
				'post'          => 0,
				'source_url'    => 'http://xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3',
				'source_file'   => '/var/www/html/wp-content/uploads/2022/12/mp3-5mg.mp3',
				'meta'          => array(),
				'media_details' => array (
					'dataformat'        => 'mp3',
					'channels'          => 2,
					'sample_rate'       => 44100,
					'bitrate'           => 320000,
					'channelmode'       => 'stereo',
					'bitrate_mode'      => 'cbr',
					'codec'             => 'LAME',
					'encoder'           => 'LAME3.99r',
					'lossless'          => false,
					'encoder_options'   => '--preset insane',
					'compression_ratio' => 0.22675736961451248,
					'fileformat'        => 'mp3',
					'filesize'          => 5289384,
					'mime_type'         => 'audio/mpeg',
					'length'            => 132,
					'length_formatted'  => '2:12',
					'genre'             => 'Cinematic',
					'album'             => 'YouTube Audio Library',
					'title'             => 'Impact Moderato',
					'artist'            => 'Kevin MacLeod',
				),
			),
			array(
				'id'            => 13,
				'title'         => 'sample-mp4',
				'featured'      => false,
				'description'   => array(
					'raw' => '',
					'rendered' => '',
				),
				'caption'       => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'file',
				'mime_type'     => 'video/mp4',
				'media_details' => array (
					'filesize' => 1570024,
					'mime_type' => 'video/mp4',
					'length' => 31,
					'length_formatted' => '0:31',
					'width' => 480,
					'height' => 270,
					'fileformat' => 'mp4',
					'dataformat' => 'quicktime',
					'audio' => array (
						'dataformat'      => 'mp4',
						'codec'           => 'ISO/IEC 14496-3 AAC',
						'sample_rate'     => 48000.0,
						'channels'        => 2,
						'bits_per_sample' => 16,
						'lossless'        => false,
						'channelmode'     => 'stereo',
					),
					'created_timestamp' => 1438938782,
				),
				'post'        => 0,
				'source_url'  => 'http://xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4',
				'source_file' => '/var/www/html/wp-content/uploads/2022/12/sample-mp4.mp4',
				'meta'        => array(),

			),
		);

		$this->assertEquals( $post_media_expected, $post_media_actual );
	}

	/**
	 * Test the get_media() method with attachments.
	 *
	 * This tests the legacy fork of the method which is still used by posts
	 * authored in the classic editor.
	 *
	 * @covers ::get_media()
	 * @runInSeparateProcess
	 */
	public function test_get_media_with_attachments() {
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_post',
			array(
				'return_in_order' => array(
					(object) array(
						'ID'           => 1,
						'post_title'   => 'Classic editor post with media',
						'post_content' => 'No content, just child posts.',
						'post_excerpt' => '',
						'guid'         => 'http://example.org/?p=1',
						'post_name'    => 'test-post',
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return_in_order' => array(
					'http://xu-distributor.local/?p=2',
					'http://xu-distributor.local/?p=11',
					'http://xu-distributor.local/?p=12',
					'http://xu-distributor.local/?p=13',
				),
			),
		);

		\WP_Mock::userFunction(
			'has_blocks',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_attached_media',
			array(
				'return' => array(
					(object) array(
						'ID'             => 11,
						'post_title'     => 'deh-platt',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=11',
						'post_name'      => 'deh-platt',
						'post_parent'    => 1,
						'post_mime_type' => 'image/jpeg',
					),
					(object) array(
						'ID'             => 12,
						'post_title'     => 'mp3-5mg',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=12',
						'post_name'      => 'mp3-5mg',
						'post_parent'    => 1,
						'post_mime_type' => 'audio/mpeg',
					),
					(object) array(
						'ID'             => 13,
						'post_title'     => 'sample-mp4',
						'post_content'   => '',
						'post_excerpt'   => '',
						'guid'           => 'http://example.org/?p=13',
						'post_name'      => 'sample-mp4',
						'post_parent'    => 1,
						'post_mime_type' => 'video/mp4',
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'wp_attachment_is_image',
			array(
				'return_in_order' => array(
					true,
					false,
					false,
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_metadata',
			array(
				'return_in_order' => array(
					array(
						'file'     => '2022/12/deh-platt.jpg',
						'width'    => 1024,
						'height'   => 683,
						'filesize' => 404298,
						'sizes'    => array(
							'thumbnail'    => array(
								'file'      => 'deh-platt-150x150.jpg',
								'width'     => 150,
								'height'    => 150,
								'mime-type' => 'image/jpeg',
							),
							'medium'       => array(
								'file'      => 'deh-platt-300x200.jpg',
								'width'     => 300,
								'height'    => 200,
								'mime-type' => 'image/jpeg',
							),
							'medium_large' => array(
								'file'      => 'deh-platt-768x512.jpg',
								'width'     => 768,
								'height'    => 512,
								'mime-type' => 'image/jpeg',
							),
							'large'        => array(
								'file'      => 'deh-platt-1024x683.jpg',
								'width'     => 1024,
								'height'    => 683,
								'mime-type' => 'image/jpeg',
							),
						),
					),
					array (
						'dataformat'        => 'mp3',
						'channels'          => 2,
						'sample_rate'       => 44100,
						'bitrate'           => 320000,
						'channelmode'       => 'stereo',
						'bitrate_mode'      => 'cbr',
						'codec'             => 'LAME',
						'encoder'           => 'LAME3.99r',
						'lossless'          => false,
						'encoder_options'   => '--preset insane',
						'compression_ratio' => 0.22675736961451248,
						'fileformat'        => 'mp3',
						'filesize'          => 5289384,
						'mime_type'         => 'audio/mpeg',
						'length'            => 132,
						'length_formatted'  => '2:12',
						'genre'             => 'Cinematic',
						'album'             => 'YouTube Audio Library',
						'title'             => 'Impact Moderato',
						'artist'            => 'Kevin MacLeod',
					),
					array (
						'filesize'          => 1570024,
						'mime_type'         => 'video/mp4',
						'length'            => 31,
						'length_formatted'  => '0:31',
						'width'             => 480,
						'height'            => 270,
						'fileformat'        => 'mp4',
						'dataformat'        => 'quicktime',
						'audio' => array (
							'dataformat'      => 'mp4',
							'codec'           => 'ISO/IEC 14496-3 AAC',
							'sample_rate'     => 48000.0,
							'channels'        => 2,
							'bits_per_sample' => 16,
							'lossless'        => false,
							'channelmode'     => 'stereo',
						),
						'created_timestamp' => 1438938782,
					),
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_get_attachment_url',
			array(
				'return_in_order' => array(
					'http://xu-distributor.local/wp-content/uploads/2022/12/deh-platt.jpg',
					'http://xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3',
					'http://xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4',
				),
			)
		);

		\WP_Mock::userFunction(
			'get_attached_file',
			array(
				'return_in_order' => array(
					'/var/www/html/wp-content/uploads/2022/12/deh-platt.jpg',
					'/var/www/html/wp-content/uploads/2022/12/mp3-5mg.mp3',
					'/var/www/html/wp-content/uploads/2022/12/sample-mp4.mp4',
				),
			)
		);

		$dt_post = new DistributorPost( 1 );
		$post_media_actual = $dt_post->get_media();

		$post_media_expected = array(
			array(
				'id'            => 11,
				'title'         => 'deh-platt',
				'featured'      => false,
				'description'   => array(
					'raw'      => '',
					'rendered' => '',
				),
				'caption' => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'image',
				'mime_type'     => 'image/jpeg',
				'post'          => 1,
				'source_url'    => 'http://xu-distributor.local/wp-content/uploads/2022/12/deh-platt.jpg',
				'source_file'   => '/var/www/html/wp-content/uploads/2022/12/deh-platt.jpg',
				'meta'          => array(),
				'media_details' => array(
					'width'    => 1024,
					'height'   => 683,
					'file'     => '2022/12/deh-platt.jpg',
					'filesize' => 404298,
					'sizes'    => array(
						'thumbnail' => array(
							'file'      => 'deh-platt-150x150.jpg',
							'width'     => 150,
							'height'    => 150,
							'mime-type' => 'image/jpeg',
						),
						'medium' => array(
							'file'      => 'deh-platt-300x200.jpg',
							'width'     => 300,
							'height'    => 200,
							'mime-type' => 'image/jpeg',
						),
						'medium_large' => array(
							'file'      => 'deh-platt-768x512.jpg',
							'width'     => 768,
							'height'    => 512,
							'mime-type' => 'image/jpeg',
						),
						'large' => array(
							'file'      => 'deh-platt-1024x683.jpg',
							'width'     => 1024,
							'height'    => 683,
							'mime-type' => 'image/jpeg',
						),
					),
				),
			),
			array(
				'id'            => 12,
				'title'         => 'mp3-5mg',
				'featured'      => false,
				'description'   => array(
					'raw'      => '',
					'rendered' => '',
				),
				'caption'       => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'file',
				'mime_type'     => 'audio/mpeg',
				'post'          => 1,
				'source_url'    => 'http://xu-distributor.local/wp-content/uploads/2022/12/mp3-5mg.mp3',
				'source_file'   => '/var/www/html/wp-content/uploads/2022/12/mp3-5mg.mp3',
				'meta'          => array(),
				'media_details' => array (
					'dataformat'        => 'mp3',
					'channels'          => 2,
					'sample_rate'       => 44100,
					'bitrate'           => 320000,
					'channelmode'       => 'stereo',
					'bitrate_mode'      => 'cbr',
					'codec'             => 'LAME',
					'encoder'           => 'LAME3.99r',
					'lossless'          => false,
					'encoder_options'   => '--preset insane',
					'compression_ratio' => 0.22675736961451248,
					'fileformat'        => 'mp3',
					'filesize'          => 5289384,
					'mime_type'         => 'audio/mpeg',
					'length'            => 132,
					'length_formatted'  => '2:12',
					'genre'             => 'Cinematic',
					'album'             => 'YouTube Audio Library',
					'title'             => 'Impact Moderato',
					'artist'            => 'Kevin MacLeod',
				),
			),
			array(
				'id'            => 13,
				'title'         => 'sample-mp4',
				'featured'      => false,
				'description'   => array(
					'raw' => '',
					'rendered' => '',
				),
				'caption'       => array(
					'raw' => '',
				),
				'alt_text'      => '',
				'media_type'    => 'file',
				'mime_type'     => 'video/mp4',
				'media_details' => array (
					'filesize' => 1570024,
					'mime_type' => 'video/mp4',
					'length' => 31,
					'length_formatted' => '0:31',
					'width' => 480,
					'height' => 270,
					'fileformat' => 'mp4',
					'dataformat' => 'quicktime',
					'audio' => array (
						'dataformat'      => 'mp4',
						'codec'           => 'ISO/IEC 14496-3 AAC',
						'sample_rate'     => 48000.0,
						'channels'        => 2,
						'bits_per_sample' => 16,
						'lossless'        => false,
						'channelmode'     => 'stereo',
					),
					'created_timestamp' => 1438938782,
				),
				'post'        => 1,
				'source_url'  => 'http://xu-distributor.local/wp-content/uploads/2022/12/sample-mp4.mp4',
				'source_file' => '/var/www/html/wp-content/uploads/2022/12/sample-mp4.mp4',
				'meta'        => array(),

			),
		);

		$this->assertEquals( $post_media_expected, $post_media_actual );
	}

	/**
	 * Test methods for formatting the post data without blocks.
	 *
	 * @covers ::post_data()
	 * @covers ::to_insert()
	 * @covers ::to_json()
	 * @runInSeparateProcess
	 */
	public function test_post_data_without_blocks() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
			)
		);

		\WP_Mock::userFunction(
			'get_the_title',
			array(
				'return' => 'Test Post',
			)
		);
		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => 'UTF-8',
			)
		);

		// Get Media: mock empty set as method is tested above.
		\WP_Mock::userFunction(
			'has_blocks',
			array(
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'get_attached_media',
			array(
				'return' => array(),
			)
		);
		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);

		// Get Terms: mock empty set as method is tested above.
		\WP_Mock::userFunction(
			'get_taxonomies',
			array(
				'return' => array( 'category', 'post_tag' ),
			)
		);
		\WP_Mock::userFunction(
			'wp_get_object_terms',
			array(
				'return' => array(),
			)
		);

		$dt_post = new DistributorPost( 1 );
		$post_data_actual = $dt_post->post_data();

		$post_data_expected = array(
			'title'             => 'Test Post',
			'slug'              => 'test-post',
			'post_type'         => 'post',
			'content'           => 'Test Content',
			'excerpt'           => 'Test Excerpt',
			'distributor_media' => array(),
			'distributor_terms' => array(
				'category' => array(),
				'post_tag' => array(),
			),
			'distributor_meta'  => array(),
		);

		$this->assertSame( $post_data_expected, $post_data_actual );

		// Make sure it looks good to insert.
		$to_insert_actual = $dt_post->to_insert();
		$to_insert_expected = array(
			'post_title'        => 'Test Post',
			'post_name'         => 'test-post',
			'post_type'         => 'post',
			'post_content'      => 'Test Content',
			'post_excerpt'      => 'Test Excerpt',
			'tax_input'         => array(
				'category'      => array(),
				'post_tag'      => array(),
			),
			'meta_input'        => array(),
			'distributor_media' => array(),
		);

		$this->assertSame( $to_insert_expected, $to_insert_actual );

		// Make sure it looks correct for a REST request.
		$to_json_actual = $dt_post->to_json();
		$to_json_expected = wp_json_encode(
			array(
				'title'             => 'Test Post',
				'slug'              => 'test-post',
				'post_type'         => 'post',
				'content'           => 'Test Content',
				'excerpt'           => 'Test Excerpt',
				'distributor_media' => array(),
				'distributor_terms' => array(
					'category' => array(),
					'post_tag' => array(),
				),
				'distributor_meta'  => array(),
			)
		);

		$this->assertSame( $to_json_expected, $to_json_actual );
	}

	/**
	 * Test methods for formatting the post data with blocks.
	 *
	 * @covers ::post_data()
	 * @covers ::to_insert()
	 * @covers ::to_json()
	 * @runInSeparateProcess
	 */
	public function test_post_data_with_blocks() {
		$block_content = '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->';
		$this->setup_post_mock( array( 'post_content' => $block_content ) );
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
			)
		);

		\WP_Mock::userFunction(
			'get_the_title',
			array(
				'return' => 'Test Post',
			)
		);
		\WP_Mock::userFunction(
			'get_bloginfo',
			array(
				'return' => 'UTF-8',
			)
		);

		// Get Media: mock empty set as method is tested above.
		\WP_Mock::userFunction(
			'has_blocks',
			array(
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'wp_cache_get',
			array(
				'return' => false
			)
		);
		\WP_Mock::userFunction(
			'wp_cache_set',
			array(
				'return' => false
			)
		);
		$blocks = array(
			array(
				'blockName'    => 'core/paragraph',
				'attrs'        => array(),
				'innerBlocks'  => array(),
				'innerHtml'    => '<p>Test Content</p>',
				'innerContent' => array( '<p>Test Content</p>' ),
			),
		);
		\WP_Mock::userFunction(
			'parse_blocks',
			array(
				'return' => $blocks,
			)
		);
		\WP_Mock::userFunction(
			'get_post_thumbnail_id',
			array(
				'return' => false,
			)
		);


		// Get Terms: mock empty set as method is tested above.
		\WP_Mock::userFunction(
			'get_taxonomies',
			array(
				'return' => array( 'category', 'post_tag' ),
			)
		);
		\WP_Mock::userFunction(
			'wp_get_object_terms',
			array(
				'return' => array(),
			)
		);

		$dt_post = new DistributorPost( 1 );
		$post_data_actual = $dt_post->post_data();

		$post_data_expected = array(
			'title'             => 'Test Post',
			'slug'              => 'test-post',
			'post_type'         => 'post',
			'content'           => '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->',
			'excerpt'           => 'Test Excerpt',
			'distributor_media' => array(),
			'distributor_terms' => array(
				'category' => array(),
				'post_tag' => array(),
			),
			'distributor_meta'  => array(),
		);

		$this->assertSame( $post_data_expected, $post_data_actual );

		// Make sure it looks good to insert.
		$to_insert_actual = $dt_post->to_insert();
		$to_insert_expected = array(
			'post_title'        => 'Test Post',
			'post_name'         => 'test-post',
			'post_type'         => 'post',
			'post_content'      => '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->',
			'post_excerpt'      => 'Test Excerpt',
			'tax_input'         => array(
				'category'      => array(),
				'post_tag'      => array(),
			),
			'meta_input'        => array(),
			'distributor_media' => array(),
		);

		$this->assertSame( $to_insert_expected, $to_insert_actual );

		// Make sure it looks correct for a REST request.
		$to_json_actual = $dt_post->to_json();
		$to_json_expected = wp_json_encode(
			array(
				'title'                   => 'Test Post',
				'slug'                    => 'test-post',
				'post_type'               => 'post',
				'content'                 => '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->',
				'excerpt'                 => 'Test Excerpt',
				'distributor_media'       => array(),
				'distributor_terms'       => array(
					'category' => array(),
					'post_tag' => array(),
				),
				'distributor_meta'        => array(),
				'distributor_raw_content' => '<!-- wp:paragraph --><p>Test Content</p><!-- /wp:paragraph -->',
			)
		);

		$this->assertSame( $to_json_expected, $to_json_actual );
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$canonical_url_actual   = $dt_post->get_canonical_url();
		$canonical_url_expected = 'http://origin.example.org/?p=10';

		$this->assertSame( $canonical_url_expected, $canonical_url_actual );
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url_unlinked() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
				'dt_unlinked'          => array ( '1' ),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://distributed.example.org/?p=1',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$canonical_url_actual   = $dt_post->get_canonical_url();
		$canonical_url_expected = 'http://distributed.example.org/?p=1';

		$this->assertSame( $canonical_url_expected, $canonical_url_actual );
	}

	/**
	 * Test get_canonical_url() method.
	 *
	 * @covers ::get_canonical_url()
	 */
	public function test_get_canonical_url_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://source.example.org/?p=1',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$canonical_url_actual   = $dt_post->get_canonical_url();
		$canonical_url_expected = 'http://source.example.org/?p=1';

		$this->assertSame( $canonical_url_expected, $canonical_url_actual );
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pushed Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '1' ),
				'dt_original_source_id'     => array( '2' ),
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_name_actual   = $dt_post->get_author_name();
		$author_name_expected = 'Test External, Pushed Origin';

		$this->assertSame( $author_name_expected, $author_name_actual );
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name_unlinked() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
				'dt_unlinked'          => array ( '1' ),
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'get_the_author_meta',
			array(
				'return' => 'Unlinked author name',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_name_actual   = $dt_post->get_author_name();
		$author_name_expected = 'Unlinked author name';

		$this->assertSame( $author_name_expected, $author_name_actual );
	}

	/**
	 * Test get_author_name() method.
	 *
	 * @covers ::get_author_name()
	 */
	public function test_get_author_name_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://source.example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_the_author_meta',
			array(
				'return' => 'Original site author name',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_name_actual   = $dt_post->get_author_name();
		$author_name_expected = 'Original site author name';

		$this->assertSame( $author_name_expected, $author_name_actual );
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'       => array( '10' ),
				'dt_original_site_name'     => array( 'Test External, Pushed Origin' ),
				'dt_original_site_url'      => array( 'http://origin.example.org/' ),
				'dt_original_post_url'      => array( 'http://origin.example.org/?p=10' ),
				'dt_subscription_signature' => array( 'abcdefghijklmnopqrstuvwxyz' ),
				'dt_syndicate_time'         => array( '1670384223' ),
				'dt_full_connection'        => array( '1' ),
				'dt_original_source_id'     => array( '2' ),
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_link_actual   = $dt_post->get_author_link();
		$author_link_expected = 'http://origin.example.org/';

		$this->assertSame( $author_link_expected, $author_link_actual );
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link_unlinked() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock(
			array (
				'dt_original_post_id'  => array( '10' ),
				'dt_original_blog_id'  => array( '2' ),
				'dt_syndicate_time'    => array ( '1670383190' ),
				'dt_original_post_url' => array ( 'http://origin.example.org/?p=10' ),
				'dt_unlinked'          => array ( '1' ),
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'get_author_posts_url',
			array(
				'return' => 'http://destination.example.org/author/unlinked-author-name/',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_link_actual   = $dt_post->get_author_link();
		$author_link_expected = 'http://destination.example.org/author/unlinked-author-name/';

		$this->assertSame( $author_link_expected, $author_link_actual );
	}

	/**
	 * Test get_author_link() method.
	 *
	 * @covers ::get_author_link()
	 */
	public function test_get_author_link_source() {
		$this->setup_post_mock();
		$this->setup_post_meta_mock( array() );

		\WP_Mock::userFunction(
			'get_permalink',
			array(
				'return' => 'http://source.example.org/?p=1',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'get_author_posts_url',
			array(
				'return' => 'http://source.example.org/author/original-author-name/',
			)
		);

		$dt_post                = new DistributorPost( 1 );
		$author_link_actual   = $dt_post->get_author_link();
		$author_link_expected = 'http://source.example.org/author/original-author-name/';

		$this->assertSame( $author_link_expected, $author_link_actual );
	}
}
