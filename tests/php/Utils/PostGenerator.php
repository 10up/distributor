<?php

namespace Distributor\Tests\Utils;

/**
 * Class PostGenerator
 */
class PostGenerator {
	/* @var \WP_Post $post */
	private $post;

	/**
	 * This function should create a "post" post type post.
	 */
	public function create(): self {
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
			'post_content_filtered' => '',
		);

		$this->post = get_post( wp_insert_post( $postData ) );

		return $this;
	}

	/**
	 * This function should save meta to post.
	 */
	public function withMeta( array $metaData ): self {
		foreach ( $metaData as $metaKey => $metaValue ) {
			update_post_meta( $this->post->ID, $metaKey, $metaValue );
		}

		return $this;
	}

	/**
	 * This function should return post.
	 */
	public function getPost(): \WP_Post {
		return $this->post;
	}
}
