<?php

namespace Syndicate\InternalConnections;
use \Syndicate\Connection as Connection;

/**
 * A network site connection let's you push and pull content within your blog
 */
class NetworkSiteConnection extends Connection {

	public $site;

	static $slug = 'networkblog';

	/**
	 * Set up network site connection
	 *
	 * @param WP_Site $site
	 * @since  0.8
	 */
	public function __construct( \WP_Site $site ) {
		$this->site = $site;
	}

	/**
	 * Push post to another internal site
	 * 
	 * @param  int $post_id
	 * @param  array  $args
	 * @since  0.8
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
			'post_status'  => ( ! empty( $args['post_status'] ) ) ? $args['post_status'] : 'publish',
		);

		$meta = get_post_meta( $post_id );

		switch_to_blog( $this->site->blog_id );

		if ( ! empty( $args['remote_post_id'] ) && get_post( $args['remote_post_id'] ) ) {
			$new_post_args['ID'] = $args['remote_post_id'];
		}

		$new_post = wp_insert_post( apply_filters( 'sy_push_post_args', $new_post_args, $post, $args, $this ) );

		if ( ! is_wp_error( $new_post ) ) {
			update_post_meta( $new_post, 'sy_original_post_id', (int) $post_id );
			update_post_meta( $new_post, 'sy_original_blog_id', (int) $original_blog_id );

			// Transfer all meta
			foreach ( $meta as $meta_key => $meta_array ) {
				foreach ( $meta_array as $meta ) {
					update_post_meta( $new_post, $meta_key, $meta );
				}
			}
		}

		do_action( 'sy_push_post', $new_post, $post_id, $args, $this );

		restore_current_blog();

		return $new_post;
	}

	/**
	 * Pull items
	 * 
	 * @param  array $items
	 * @since  0.8
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

			if ( ! is_wp_error( $new_post ) ) {
				foreach ( $post->meta as $meta_key => $meta_array ) {
					foreach ( $meta_array as $meta ) {
						update_post_meta( $new_post, $meta_key, $meta );
					}
				}
			}

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
	 * @since  0.8
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
	 * @since  0.8
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
				$post->link  = get_permalink( $post->ID );
				$post->meta = get_post_meta( $post->ID );
				$formatted_posts[] = $post;
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

			$post->link  = get_permalink( $id );
			$post->meta = get_post_meta( $id );
			$formatted_post = $post;

			restore_current_blog();

			return apply_filters( 'sy_remote_get', $formatted_post, $args, $this );
		}
	}

	/**
	 * Setup actions and filters that are need on every page load
	 * 
	 * @since 0.8
	 */
	public static function bootstrap() {
		add_action( 'template_redirect', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'canonicalize_front_end' ) );
		add_action( 'wp_ajax_sy_auth_check', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'auth_check' ) );
		add_action( 'edit_form_top', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'canonical_admin_post' ) );
	}

	/**
	 * Setup canonicalization on front end
	 *
	 * @since  0.8
	 */
	public static function canonicalize_front_end() {
		if ( is_single() ) {
			global $post;

			$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
			$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

			if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
				return;
			}

			add_filter( 'the_title', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'the_title' ), 10, 2 );
			add_filter( 'the_content', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'the_content' ), 10, 1 );
			add_filter( 'the_date', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'the_date' ), 10, 1 );
			add_filter( 'get_the_excerpt', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'get_the_excerpt' ), 10, 1 );
			add_filter( 'get_canonical_url', array( '\Syndicate\InternalConnections\NetworkSiteConnection', 'canonical_url' ), 10, 2 );
		}
	}

	/**
	 * Setup canonicalization on back end
	 *
	 * @since  0.8
	 */
	public static function canonical_admin_post() {
		global $post, $pagenow;

		if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
	    	return;
	    }

	    $original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

		if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
			return;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'sy_unlinked', true );

		if ( $unlinked ) {
			return;
		}

		switch_to_blog( $original_blog_id );
		$post = get_post( $original_post_id );
		restore_current_blog();
	}

	/**
	 * Check if current user can create a post type with ajax
	 *
	 * @since  0.8
	 */
	public static function auth_check() {
		if ( ! check_ajax_referer( 'sy-auth-check', 'nonce', false ) ) {
			wp_send_json_error();
			exit;
		}

		if ( empty( $_POST['username'] ) ) {
			wp_send_json_error();
			exit;
		}

		$post_types = get_post_types();
		$authorized_post_types = array();

		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( current_user_can( $post_type_object->cap->create_posts ) ) {
				$authorized_post_types[] = $post_type;
			}
		}


		wp_send_json_success( $authorized_post_types );
		exit;
	}

	/**
	 * Find out which sites user can create post type on
	 * 
	 * @since  0.8
	 * @return array
	 */
	public static function get_available_authorized_sites() {
		if ( ! is_multisite() ) {
			return array();
		}
		
		$sites = get_sites();
		$authorized_sites = array();

		$current_blog_id = get_current_blog_id();

		foreach ( $sites as $site ) {
			$blog_id = $site->blog_id;

			if ( $blog_id == $current_blog_id ) {
				continue;
			}

			$base_url = get_site_url( $blog_id );

			if ( empty( $base_url ) ) {
				continue;
			}

			global $current_user;
			get_currentuserinfo();

			$response = wp_remote_post( untrailingslashit( $base_url ) . '/wp-admin/admin-ajax.php', array(
				'body' => array(
					'nonce'     => wp_create_nonce( 'sy-auth-check' ),
					'username'  => $current_user->user_login,
					'action'    => 'sy_auth_check',
				),
				'cookies' => $_COOKIE
			) );

			if ( ! is_wp_error( $response ) ) {

				$body = wp_remote_retrieve_body( $response );

				if ( ! is_wp_error( $body ) ) {
					try {
						$body_array = json_decode( $body, true );
						
						if ( ! empty( $body_array['success'] ) ) {
							$authorized_sites[] = array(
								'site'       => $site,
								'post_types' => $body_array['data'],
							);
						}
					} catch ( \Exception $e ) {
						continue;
					}
				}
			}
		}

		return $authorized_sites;
	}

	/**
	 * Make sure canonical url header is outputted
	 * 
	 * @param  string $canonical_url
	 * @param  object $post
	 * @since  0.8
	 * @return string
	 */
	public static function canonical_url( $canonical_url, $post ) {
		$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

		switch_to_blog( $original_blog_id );
		$canonical_url = get_permalink( $original_post_id );
		restore_current_blog();

		return $canonical_url;
	}

	/**
	 * Use canonical title
	 * 
	 * @param  string $title
	 * @param  int $id
	 * @since  0.8
	 * @return string
	 */
	public static function the_title( $title, $id ) {
		$original_blog_id = get_post_meta( $id, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $id, 'sy_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $title;
		}

		switch_to_blog( $original_blog_id );
		$title = get_the_title( $original_post_id );
		restore_current_blog();

		return $title;
	}

	/**
	 * Use canonical content
	 * 
	 * @param  string $content
	 * @since  0.8
	 * @return string
	 */
	public static function the_content( $content ) {
		global $post;

		$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $content;
		}

		switch_to_blog( $original_blog_id );
		$original_post = get_post( $original_post_id );
		$content = apply_filters( 'the_content', $original_post->post_content );
		restore_current_blog();

		return $content;
	}

	/**
	 * Use canonical date
	 * 
	 * @param  string $date
	 * @since  0.8
	 * @return string
	 */
	public static function the_date( $date ) {
		global $post;

		$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $date;
		}

		switch_to_blog( $original_blog_id );

		$date = get_the_date( get_option( 'date_format' ), $original_post_id);

		restore_current_blog();

		return $date;
	}

	/**
	 * Use canonical excerpt
	 * 
	 * @param  string $excerpt
	 * @since  0.8
	 * @return string
	 */
	public static function get_the_excerpt( $excerpt ) {
		$original_blog_id = get_post_meta( $id, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $id, 'sy_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $excerpt;
		}

		switch_to_blog( $original_blog_id );
		$original_post = get_post( $original_post_id );
		$excerpt = $original_post->post_excerpt;
		restore_current_blog();

		return $excerpt;
	}
}
