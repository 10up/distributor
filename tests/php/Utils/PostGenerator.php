<?php

namespace Distributor\Tests\Utils;

/**
 * Class PostGenerator
 */
class PostGenerator {
	/**
	 * @return int|\WP_Error
	 */
	public static function create() {
		$postData = array(
			'post_title'            => 'Test Post',
			'post_content'          => 'Test Content',
			'post_excerpt'          => 'Test Excerpt',
			'post_status'           => 'publish',
			'post_type'             => 'post',
			'post_author'           => 1,
			'post_date'             => '2020-01-01 00:00:00',
			'post_date_gmt'         => '2020-01-01 00:00:00',
			'post_modified'         => '2020-01-01 00:00:00',
			'post_modified_gmt'     => '2020-01-01 00:00:00',
			'post_parent'           => 0,
			'post_mime_type'        => '',
			'comment_count'         => 0,
			'comment_status'        => 'open',
			'ping_status'           => 'open',
			'menu_order'            => 0,
			'pinged'                => '',
			'to_ping'               => '',
			'post_password'         => '',
			'post_name'             => 'test-post',
			'post_content_filtered' => '',
		);

		return wp_insert_post( $postData );
	}

	/**
	 * @param int   $postId
	 * @param array $metaData
	 *
	 * @return void
	 */
	public static function saveMeta( int $postId, array $metaData ): void {
		foreach ( $metaData as $metaKey => $metaValue ) {
			update_post_meta( $postId, $metaKey, $metaValue );
		}
	}
}
