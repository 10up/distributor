<?php

namespace Distributor\Tests\Utils;

/**
 * Class ExternalConnectionPostGenerator.
 */
class ExternalConnectionPostGenerator {
	public static function create( array $postData = [], array $metaData = [] ): int {
		// Insert post to external connection.
		$postData = wp_parse_args(
			$postData,
			[
				'post_title'            => 'Subscription 1 1664535082',
				'post_content'          => 'Test Content',
				'post_excerpt'          => 'Test Excerpt',
				'post_status'           => 'publish',
				'post_type'             => 'dt_subscription',
				'post_author'           => get_current_user_id(),
				'post_date'             => '2020-01-01 00:00:00',
				'post_date_gmt'         => '2020-01-01 00:00:00',
				'post_modified'         => '2020-01-01 00:00:00',
				'post_modified_gmt'     => '2020-01-01 00:00:00',
				'post_parent'           => 0,
				'post_mime_type'        => '',
				'comment_count'         => 0,
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'menu_order'            => 0,
				'pinged'                => '',
				'to_ping'               => '',
				'post_password'         => '',
				'post_name'             => 'subscription-1-1664535082',
				'post_content_filtered' => '',
			]
		);

		$postId = wp_insert_post( $postData );

		// Insert meta data.
		$metaData = wp_parse_args(
			$metaData,
			[
				'dt_subscription_post_id'        => 16,
				'dt_subscription_signature'      => 'WDexP3lVEcbbPwBjAcSukLHe70',
				'dt_subscription_remote_post_id' => 23,
				'dt_external_connection_url'     => 'https://destination.test/wp-json',
				'dt_external_connection_type'    => 'wp'
			]
		);

		foreach ( $metaData as $metaKey => $metaValue ) {
			update_post_meta( $postId, $metaKey, $metaValue );
		}

		return $postId;
	}
}
