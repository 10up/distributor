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
		$original_blog_id = get_current_blog_id();

		$new_post_args = array(
			'post_title'   => get_the_title( $post_id ),
			'post_content' => apply_filters( 'the_content', $post->post_content ),
			'post_excerpt' => $post->post_excerpt,
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
			'post_status'  => 'publish',
		);

		switch_to_blog( $this->site->blog_id );

		if ( ! empty( $args['remote_post_id'] ) && get_post( $args['remote_post_id'] ) ) {
			$new_post_args['ID'] = $args['remote_post_id'];
		}

		$new_post = wp_insert_post( apply_filters( 'sy_push_post_args', $new_post_args, $post, $args, $this ) );

		if ( ! is_wp_error( $new_post ) ) {
			update_post_meta( $new_post, 'sy_original_post_id', (int) $post_id );
			update_post_meta( $new_post, 'sy_original_blog_id', (int) $original_blog_id );
		}

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

			// Remove date stuff
			unset( $post_array['post_date'] );
			unset( $post_array['post_date_gmt'] );
			unset( $post_array['post_modified'] );
			unset( $post_array['post_modified_gmt'] );

			$new_post = wp_insert_post( apply_filters( 'sy_pull_post_args', $post_array, $item_id, $post, $this ) );

			do_action( 'sy_pull_post', $new_post, $this );

			$created_posts[] = $new_post;
		}

		return $created_posts;
	}

	/**
	 * Log a sync. Unfortunately have to use options
	 *
	 * @param  array $item_id_mappings
	 * @param  string|bool $status
	 * @since  1.0
	 */
	public function log_sync( array $item_id_mappings ) {
		$sync_log = get_site_option( 'sy_sync_log_' . $this->site->blog_id, array() );

		foreach ( $item_id_mappings as $old_item_id => $new_item_id ) {
			if ( empty( $new_item_id ) ) {
				$sync_log[ $old_item_id ] = false;
			} else {
				$sync_log[ $old_item_id ] = (int) $new_item_id;
			}
		}

		update_site_option( 'sy_sync_log_' .  $this->site->blog_id, $sync_log );

		do_action( 'sy_log_sync', $item_id_mappings,  $sync_log, $this );
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
			$query_args['post_type'] = ( empty( $args['post_type'] ) ) ? 'post' : $args['post_type'];
			$query_args['post_status'] = ( empty( $args['post_status'] ) ) ? 'any' : $args['post_status'];
			$query_args['posts_per_page'] = ( empty( $args['posts_per_page'] ) ) ? get_option( 'posts_per_page' ) : $args['posts_per_page'];
			$query_args['paged'] = ( empty( $args['paged'] ) ) ? 1 : $args['paged'];

			if ( isset( $args['post__in'] ) ) {
				if ( empty( $args['post__in'] ) ) {
					// If post__in is empty, we can just stop right here
					return apply_filters( 'sy_remote_get', [
						'items'       => array(),
						'total_items' => 0,
					], $args, $this );
				}

				$query_args['post__in'] = $args['post__in'];
			} elseif ( isset( $args['post__not_in'] ) ) {
				$query_args['post__not_in'] = $args['post__not_in'];
			}

			$posts_query = new \WP_Query( apply_filters( 'sy_remote_get_query_args', $query_args, $args, $this ) );

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
