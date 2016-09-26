<?php

namespace Syndicate\InternalConnections;
use \Syndicate\Connection as Connection;

/**
 * A network site connection let's you push and pull content within your blog
 */
class NetworkSiteConnection extends Connection {

	public $site;
	static $mapping_handler_class = '\Syndicate\Mappings\NetworkSitePost';

	/**
	 * Set up network site connection
	 *
	 * @param WP_Site $site
	 * @since  1.0
	 */
	public function __construct( \WP_Site $site ) {
		$this->mapping_handler = new self::$mapping_handler_class;
		$this->site = $site;
	}

	/**
	 * Push post to another internal site
	 * 
	 * @param  int $post_id
	 * @param  array  $args
	 * @since  1.0
	 * @return int|WP_Error
	 */
	public function push( $post_id, $args = array() ) {
		$post = get_post( $post_id );

		$new_post_args = array(
			'post_title'   => get_the_title( $post_id ),
			'post_content' => apply_filters( 'the_content', $post->post_content ),
			'post_excerpt' => $post->post_excerpt,
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
			'post_status'  => 'draft',
		);

		switch_to_blog( $this->site->blog_id );

		if ( ! empty( $args['remote_post_id'] ) && get_post( $args['remote_post_id'] ) ) {
			$new_post_args['ID'] = $args['remote_post_id'];
		}

		$new_post = wp_insert_post( $new_post_args );

		do_action( 'sy_push_post', $new_post, $post_id, $args, $this );

		restore_current_blog();

		return $new_post;
	}

	/**
	 * Pull items
	 * 
	 * @param  array $items
	 * @since  1.0
	 * @return array
	 */
	public function pull( $items ) {
		$created_posts = array();

		foreach ( $items as $item_id ) {
			$post = $this->remote_get( [ 'id' => $item_id ] );

			if ( is_wp_error( $post ) ) {
				$created_posts[] = $post;
				continue;
			}

			$post_props = get_object_vars( $post );
			$post_array = array();

			foreach ( $post_props as $key => $value ) {
				$post_array[ $key ] = $value;
			}

			unset( $post_array['ID'] );

			$new_post = wp_insert_post( $post_array );

			do_action( 'sy_pull_post', $new_post, $this );

			$created_posts[] = $new_post;
		}

		return $created_posts;
	}

	/**
	 * Log statuses for sync posts
	 *
	 * @param  array|int $item_ids
	 * @param  string $status
	 * @since  1.0
	 */
	public function log_sync_statuses( $item_ids, string $status ) {
		if ( ! is_array( $item_ids ) ) {
			$item_ids = array( $item_ids );
		}

		switch_to_blog( $this->site->blog_id );

		foreach ( $item_ids as $item_id ) {
			wp_set_post_terms( $item_id, array( $status ), 'sy-sync-status' );
		}

		restore_current_blog();

		do_action( 'sy_log_sync_statuses', $item_ids, $status, $this );
	}

	/**
	 * Remotely get posts so we can list them for pulling
	 * 
	 * @param  array  $args
	 * @since  1.0
	 * @return array|WP_Post|bool
	 */
	public function remote_get( $args = array() ) {
		$id = ( empty( $args['id'] ) ) ? false : $args['id'];

		switch_to_blog( $this->site->blog_id );

		if ( empty( $id ) ) {
			$post_type = ( empty( $args['post_type'] ) ) ? 'post' : $args['post_type'];
			$post_status = ( empty( $args['post_status'] ) ) ? 'any' : $args['post_status'];
			$posts_per_page = ( empty( $args['posts_per_page'] ) ) ? get_option( 'posts_per_page' ) : $args['posts_per_page'];
			$paged = ( empty( $args['paged'] ) ) ? 1 : $args['paged'];
			$tax_query = ( empty( $args['tax_query'] ) ) ? [] : $args['tax_query'];

			$posts_query = new \WP_Query( [
				'post_type'      => $post_type,
				'posts_per_page' => $posts_per_page,
				'paged'          => $paged,
				'post_status'    => $post_status,
				'tax_query'      => $tax_query,
			] );

			$posts = $posts_query->posts;

			$formatted_posts = [];

			foreach ( $posts as $post ) {
				$formatted_posts[] = $this->mapping_handler->to_wp_post( $post );
			}

			restore_current_blog();

			return apply_filters( 'sy_remote_get', [
				'items'       => $formatted_posts,
				'total_items' => $posts_query->found_posts,
			], $args, $this );
		} else {
			$post = get_post( $id );
			
			if ( empty( $post ) ) {
				return false;
			}

			$formatted_post = $this->mapping_handler->to_wp_post( $post );

			restore_current_blog();

			return apply_filters( 'sy_remote_get', $formatted_post, $args, $this );
		}
	}

}