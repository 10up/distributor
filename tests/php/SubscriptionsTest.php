<?php

namespace Distributor;

use WP_Mock\Tools\TestCase;
use WP_Mock\Functions;

class SubscriptionsTest extends TestCase {

	/**
	 * Test delete subscribed to post
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_delete_subscribed_post() {
		\WP_Mock::userFunction(
			'current_user_can', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_original_source_id', true ],
				'return' => null,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_original_post_id', true ],
				'return' => null,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_subscriptions', true ],
				'return' => [ 9, 10 ],
			]
		);

		\WP_Mock::userFunction(
			'wp_delete_post', [
				'times' => 1,
				'args'  => [ 9, true ],
			]
		);

		\WP_Mock::userFunction(
			'wp_delete_post', [
				'times' => 1,
				'args'  => [ 10, true ],
			]
		);

		\WP_Mock::passthruFunction( 'untrailingslashit' );

		$subscription_post_id = 9;
		$remote_post_id       = 100;
		$target_url           = 'http://target';
		$signature            = 'signature';

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 2,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_subscription_signature', true ],
				'return' => $signature,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 2,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_subscription_remote_post_id', true ],
				'return' => $remote_post_id,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 2,
				'args'   => [ \WP_Mock\Functions::type( 'int' ), 'dt_subscription_target_url', true ],
				'return' => $target_url,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_post', [
				'times' => 2,
				'args'  => [
					$target_url . '/wp/v2/dt_subscription/receive',
					[
						'timeout'  => 5,
						'blocking' => \Distributor\Utils\is_dt_debug(),
						'body'     => [
							'post_id'          => $remote_post_id,
							'signature'        => $signature,
							'original_deleted' => true,
						],
					],
				],
			]
		);

		Subscriptions\delete_subscriptions( 1 );

		$this->assertConditionsMet();
	}

	/**
	 * Test delete original subscribing
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_delete_subscribing_post() {
		\WP_Mock::userFunction(
			'current_user_can', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_original_source_id', true ],
				'return' => 5,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_original_post_id', true ],
				'return' => 6,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_subscriptions', true ],
				'return' => null,
			]
		);

		// Called when external connection is instantiated
		\WP_Mock::userFunction(
			'get_post_meta', [
				'times' => 3,
			]
		);

		\WP_Mock::userFunction(
			'get_the_title', [
				'times' => 1,
			]
		);

		// External connection comes back WP_Error and delete_subscription isn't actually called on connection
		Subscriptions\delete_subscriptions( 1 );

		$this->assertConditionsMet();
	}

	/**
	 * Test send notifications when no subscriptions
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_send_notifications_none() {

		$post = (object) [
			'ID' => 1,
			'post_type' => 'post',
		];

		\WP_Mock::passthruFunction( 'absint' );
		\WP_Mock::userFunction(
			'get_post', [
				'args'   => [ Functions::anyOf( $post->ID, $post ) ],
				'return' => $post,
			]
		);

		\WP_Mock::userFunction(
			'get_option', [
				'args'   => [ 'page_for_posts' ],
				'return' => 0,
			]
		);

		\WP_Mock::userFunction(
			'use_block_editor_for_post_type', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_is_post_revision', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'current_user_can', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_is_post_revision', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ 1, 'dt_subscriptions', true ],
				'return' => [],
			]
		);

		Subscriptions\send_notifications( $post->ID );

		$this->assertConditionsMet();
	}

	/**
	 * Test send notifications when the remote post does not exist, should delete local subscription
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_send_notifications_no_remote_post() {
		$post_id              = 1;
		$subscription_post_id = 2;
		$remote_post_id       = 9;
		$target_url           = 'http://target';
		$signature            = 'signature';

		$post               = new \stdClass();
		$post->post_content = 'content';
		$post->post_excerpt = 'excerpt';
		$post->post_name    = 'slug';
		$post->post_type    = 'post';
		$post->ID           = $post_id;

		\WP_Mock::userFunction(
			'current_user_can', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_is_post_revision', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $post_id, 'dt_subscriptions', true ],
				'return' => [ $subscription_post_id ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_signature', true ],
				'return' => $signature,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_remote_post_id', true ],
				'return' => $remote_post_id,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_target_url', true ],
				'return' => $target_url,
			]
		);

		\WP_Mock::passthruFunction( 'untrailingslashit' );

		\WP_Mock::userFunction(
			'get_the_title', [
				'return' => 'title',
			]
		);

		/**
		 * We will test the util prepare functions later
		 */
		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_media', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_taxonomy_terms', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_meta', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_post', [
				'args'   => [ Functions::anyOf( $post->ID, $post ) ],
				'return' => $post,
			]
		);

		\WP_Mock::passthruFunction( 'absint' );

		\WP_Mock::userFunction(
			'get_option', [
				'args'   => [ 'page_for_posts' ],
				'return' => 0,
			]
		);

		\WP_Mock::userFunction(
			'use_block_editor_for_post_type', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_post', [
				'times' => 1,
				'args'  => [
					$target_url . '/wp/v2/dt_subscription/receive',
					[
						'timeout' => 5,
						'body'    => wp_json_encode( [
							'post_id'   => $remote_post_id,
							'signature' => $signature,
							'post_data' => [
								'title'             => 'title',
								'slug'              => 'slug',
								'post_type'         => 'post',
								'content'           => 'content',
								'excerpt'           => 'excerpt',
								'distributor_media' => [],
								'distributor_terms' => [],
								'distributor_meta'  => [],
							]
						] ),
						'headers' => [
							'Content-Type' => 'application/json',
						],
					],
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code', [
				'return' => 404,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [
					'X-Distributor-Post-Deleted' => true,
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_delete_post', [
				'times' => 1,
				'args'  => [ $subscription_post_id, true ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $post_id, 'dt_subscriptions', [] ],
			]
		);

		\WP_Mock::userFunction(
			'remove_filter', [
				'times' => 1,
			]
		);

		\WP_Mock::userFunction(
			'get_bloginfo', [
				'args'   => 'charset',
				'return' => 'UTF-8',
			]
		);

		Subscriptions\send_notifications( $post_id );

		$this->assertConditionsMet();
	}

	/**
	 * Test send notifications when the remote post does exist, should NOT delete local subscription
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_send_notifications_remote_post_exists() {
		$post_id              = 1;
		$subscription_post_id = 2;
		$remote_post_id       = 9;
		$target_url           = 'http://target';
		$signature            = 'signature';

		$post               = new \stdClass();
		$post->post_content = 'content';
		$post->post_excerpt = 'excerpt';
		$post->post_name    = 'slug';
		$post->post_type    = 'post';
		$post->ID           = $post_id;

		\WP_Mock::userFunction(
			'current_user_can', [
				'return' => true,
			]
		);

		\WP_Mock::userFunction(
			'wp_is_post_revision', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $post_id, 'dt_subscriptions', true ],
				'return' => [ $subscription_post_id ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_signature', true ],
				'return' => $signature,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_remote_post_id', true ],
				'return' => $remote_post_id,
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $subscription_post_id, 'dt_subscription_target_url', true ],
				'return' => $target_url,
			]
		);

		\WP_Mock::passthruFunction( 'untrailingslashit' );

		\WP_Mock::userFunction(
			'get_the_title', [
				'return' => 'title',
			]
		);

		/**
		 * We will test the util prepare functions later
		 */
		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_media', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_taxonomy_terms', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'\Distributor\Utils\prepare_meta', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'get_post', [
				'args'   => [ Functions::anyOf( $post->ID, $post ) ],
				'return' => $post,
			]
		);

		\WP_Mock::passthruFunction( 'absint' );

		\WP_Mock::userFunction(
			'get_option', [
				'args'   => [ 'page_for_posts' ],
				'return' => 0,
			]
		);

		\WP_Mock::userFunction(
			'use_block_editor_for_post_type', [
				'return' => false,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_post', [
				'times' => 1,
				'args'  => [
					$target_url . '/wp/v2/dt_subscription/receive',
					[
						'timeout' => 5,
						'body'    => wp_json_encode( [
							'post_id'   => $remote_post_id,
							'signature' => $signature,
							'post_data' => [
								'title'             => 'title',
								'slug'              => 'slug',
								'post_type'         => 'post',
								'content'           => 'content',
								'excerpt'           => 'excerpt',
								'distributor_media' => [],
								'distributor_terms' => [],
								'distributor_meta'  => [],
							],
						] ),
						'headers' => [
							'Content-Type' => 'application/json',
						]
					],
				],
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_response_code', [
				'return' => 200,
			]
		);

		\WP_Mock::userFunction(
			'wp_remote_retrieve_headers', [
				'return' => [],
			]
		);

		\WP_Mock::userFunction(
			'wp_delete_post', [
				'times' => 0,
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 0,
			]
		);

		\WP_Mock::userFunction(
			'remove_filter', [
				'times' => 1,
			]
		);

		\WP_Mock::userFunction(
			'get_bloginfo', [
				'args'   => 'charset',
				'return' => 'UTF-8',
			]
		);

		Subscriptions\send_notifications( $post_id );

		$this->assertConditionsMet();
	}

	/**
	 * Test create subscription. This creates a subscription CPT locally that a
	 * remote post has subscribed to
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_create_subscription() {
		$post_id              = 1;
		$remote_post_id       = 2;
		$target_url           = 'http://test.com';
		$signature            = '12345';
		$subscription_post_id = 3;

		\WP_Mock::passThruFunction( 'sanitize_text_field' );

		\WP_Mock::userFunction(
			'wp_insert_post', [
				'times'  => 1,
				'return' => $subscription_post_id,
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $subscription_post_id, 'dt_subscription_post_id', $post_id ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $subscription_post_id, 'dt_subscription_signature', $signature ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $subscription_post_id, 'dt_subscription_remote_post_id', $remote_post_id ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [ $subscription_post_id, 'dt_subscription_target_url', $target_url ],
			]
		);

		\WP_Mock::userFunction(
			'get_post_meta', [
				'times'  => 1,
				'args'   => [ $post_id, 'dt_subscriptions', true ],
				'return' => [ 'sig' => 5 ],
			]
		);

		\WP_Mock::userFunction(
			'update_post_meta', [
				'times' => 1,
				'args'  => [
					$post_id,
					'dt_subscriptions',
					[
						'sig'             => 5,
						md5( $signature ) => $subscription_post_id,
					],
				],
			]
		);

		Subscriptions\create_subscription( $post_id, $remote_post_id, $target_url, $signature );

		$this->assertConditionsMet();
	}

	/**
	 * Test create remote subscription. This creates a subscription CPT remotely that a
	 * local post has subscribed to
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_create_remote_subscription() {
		$post_id        = 1;
		$remote_post_id = 2;
		$signature      = '12345';

		Connections::factory()->register( '\TestExternalConnection' );

		\WP_Mock::userFunction(
			'get_post_meta', array(
				'args'   => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_type', true ),
				'return' => 'test-external-connection',
			)
		);

		\WP_Mock::userFunction(
			'get_post_meta', array(
				'args'   => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_url', true ),
				'return' => 'fake',
			)
		);

		\WP_Mock::userFunction(
			'get_post_meta', array(
				'args'   => array( \WP_Mock\Functions::type( 'int' ), 'dt_external_connection_auth', true ),
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'get_the_title', array(
				'return' => '',
			)
		);

		$connection = ExternalConnection::instantiate( 1 );

		\WP_Mock::passThruFunction( 'untrailingslashit' );
		\WP_Mock::passThruFunction( 'sanitize_text_field' );

		\WP_Mock::userFunction(
			'wp_generate_password', array(
				'return' => $signature,
			)
		);

		\WP_Mock::userFunction(
			'update_post_meta', array(
				'times' => 1,
				'args'  => array( $post_id, 'dt_subscription_signature', $signature ),
			)
		);

		\WP_Mock::userFunction(
			'wp_remote_post', [
				'times' => 1,
				'args'  => [
					\WP_Mock\Functions::type( 'string' ),
					[
						'timeout'  => 5,
						'blocking' => \Distributor\Utils\is_dt_debug(),
						'body'     => [
							'post_id'        => $remote_post_id,
							'remote_post_id' => $post_id,
							'signature'      => $signature,
							'target_url'     => home_url() . '/wp-json',
						],
					],
				],
			]
		);

		Subscriptions\create_remote_subscription( $connection, $remote_post_id, $post_id );

		$this->assertConditionsMet();
	}

	/**
	 * Test a local subscription given a signature
	 *
	 * @since  1.0
	 * @group Subscriptions
	 * @runInSeparateProcess
	 */
	public function test_delete_subscription_local() {
		$post_id         = 1;
		$subscription_id = 9;
		$signature       = 'signature';

		\WP_Mock::userFunction(
			'get_post_meta', array(
				'times'  => 1,
				'args'   => array( $post_id, 'dt_subscriptions', true ),
				'return' => [ md5( $signature ) => $subscription_id ],
			)
		);

		\WP_Mock::userFunction(
			'wp_delete_post', array(
				'times' => 1,
				'args'  => array( $subscription_id, true ),
			)
		);

		\WP_Mock::userFunction(
			'update_post_meta', array(
				'times' => 1,
				'args'  => array( $post_id, 'dt_subscriptions', [] ),
			)
		);

		Subscriptions\delete_subscription( $post_id, $signature );

		$this->assertConditionsMet();
	}
}
