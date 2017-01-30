<?php

namespace Distributor\InternalConnections;
use \Distributor\Connection as Connection;

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
	 * @param  int   $post_id
	 * @param  array $args
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

		$new_post = wp_insert_post( apply_filters( 'dt_push_post_args', $new_post_args, $post, $args, $this ) );

		if ( ! is_wp_error( $new_post ) ) {
			update_post_meta( $new_post, 'dt_original_post_id', (int) $post_id );
			update_post_meta( $new_post, 'dt_original_blog_id', (int) $original_blog_id );
			update_post_meta( $new_post, 'dt_syndicate_time', time() );

			$blacklisted_meta = [ 'dt_unlinked', 'dt_original_post_id', 'dt_original_blog_id', 'dt_syndicate_time' ];

			// Transfer all meta
			foreach ( $meta as $meta_key => $meta_array ) {
				foreach ( $meta_array as $meta ) {
					if ( ! in_array( $meta_key, $blacklisted_meta ) ) {
						$meta = maybe_unserialize( $meta );
						update_post_meta( $new_post, $meta_key, $meta );
					}
				}
			}
		}

		do_action( 'dt_push_post', $new_post, $post_id, $args, $this );

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

			$new_post = wp_insert_post( apply_filters( 'dt_pull_post_args', $post_array, $item_id, $post, $this ) );

			if ( ! is_wp_error( $new_post ) ) {
				update_post_meta( $new_post, 'dt_original_post_id', (int) $item_id );
				update_post_meta( $new_post, 'dt_original_blog_id', (int) $this->site->blog_id );
				update_post_meta( $new_post, 'dt_syndicate_time', time() );
				$blacklisted_meta = [ 'dt_unlinked', 'dt_original_post_id', 'dt_original_blog_id', 'dt_syndicate_time' ];

				// Transfer meta
				foreach ( $post->meta as $meta_key => $meta_array ) {
					foreach ( $meta_array as $meta ) {
						if ( ! in_array( $meta_key, $blacklisted_meta ) ) {
							$meta = maybe_unserialize( $meta );
							update_post_meta( $new_post, $meta_key, $meta );
						}
					}
				}
			}

			$current_blog_id = get_current_blog_id();

			switch_to_blog( $this->site->blog_id );

			$connection_map = get_post_meta( $item_id, 'dt_connection_map', true );
			if( ! empty( $connection_map ) ) {
			    $new_map = [
			        'post_id'   => (int) $new_post,
                    'time'      => time()
                ];

			    $connection_map['internal'][$current_blog_id] = $new_map;
            } else {
                $connection_map = [
                    'external' => [],
                    'internal' => [
                        $current_blog_id => [
                            'post_id'   => (int) $new_post,
                            'time'      => time()
                        ]
                    ]
                ];
            }

			update_post_meta( $item_id, 'dt_connection_map', $connection_map );

			restore_current_blog();

			do_action( 'dt_pull_post', $new_post, $this );

			$created_posts[] = $new_post;
		}

		return $created_posts;
	}

	/**
	 * Log a sync. Unfortunately have to use options
	 *
	 * @param  array       $item_id_mappings
	 * @param  string|bool $status
	 * @since  0.8
	 */
	public function log_sync( array $item_id_mappings ) {
		$sync_log = get_site_option( 'dt_sync_log_' . $this->site->blog_id, array() );

		foreach ( $item_id_mappings as $old_item_id => $new_item_id ) {
			if ( empty( $new_item_id ) ) {
				$sync_log[ $old_item_id ] = false;
			} else {
				$sync_log[ $old_item_id ] = (int) $new_item_id;
			}
		}

		update_site_option( 'dt_sync_log_' .  $this->site->blog_id, $sync_log );

		do_action( 'dt_log_sync', $item_id_mappings,  $sync_log, $this );
	}

	/**
	 * Remotely get posts so we can list them for pulling
	 *
	 * @param  array $args
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
					return apply_filters( 'dt_remote_get', [
						'items'       => array(),
						'total_items' => 0,
					], $args, $this );
				}

				$query_args['post__in'] = $args['post__in'];
			} elseif ( isset( $args['post__not_in'] ) ) {
				$query_args['post__not_in'] = $args['post__not_in'];
			}

			if( isset( $args['meta_query'] ) ) {
				$query_args['meta_query'] = $args['meta_query'];
			}

			$posts_query = new \WP_Query( apply_filters( 'dt_remote_get_query_args', $query_args, $args, $this ) );

			$posts = $posts_query->posts;

			$formatted_posts = [];

			foreach ( $posts as $post ) {
				$post->link  = get_permalink( $post->ID );
				$post->meta = get_post_meta( $post->ID );
				$formatted_posts[] = $post;
			}

			restore_current_blog();

			return apply_filters( 'dt_remote_get', [
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

			return apply_filters( 'dt_remote_get', $formatted_post, $args, $this );
		}
	}

	/**
	 * Setup actions and filters that are need on every page load
	 *
	 * @since 0.8
	 */
	public static function bootstrap() {
		add_action( 'template_redirect', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'canonicalize_front_end' ) );
		add_action( 'wp_ajax_dt_auth_check', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'auth_check' ) );
		add_action( 'edit_form_top', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'canonical_admin_post' ) );
		add_action( 'in_admin_footer', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'end_canonical_admin_post' ) );
		add_filter( 'get_sample_permalink_html', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'fix_sample_permalink_html' ), 10, 1 );
		add_filter( 'get_delete_post_link', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'fix_delete_link' ), 10, 3 );
	}

	/**
	 * Make sure delete link works for correct post
	 * 
	 * @param  string $url
	 * @param  int $id
	 * @param  bool $force_delete
	 * @since  0.8
	 * @return string
	 */
	public static function fix_delete_link( $url, $id, $force_delete ) {
		global $dt_original_post, $dt_blog_id;

		if ( empty( $dt_original_post ) ) {
			return $url;
		}

		$post = $dt_original_post;

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
			return;
		}

		$action = ( $force_delete || ! EMPTY_TRASH_DAYS ) ? 'delete' : 'trash';

		$delete_link = add_query_arg( 'action', $action, get_admin_url( $dt_blog_id ) . sprintf( $post_type_object->_edit_link, $post->ID ) );

		return wp_nonce_url( $delete_link, "$action-post_{$post->ID}" );
	}

	/**
	 * Fix permalink HTML to be for the correct blog
	 * 
	 * @param  string $permalink_html
	 * @since  0.8
	 * @return string
	 */
	public static function fix_sample_permalink_html( $permalink_html ) {
		global $dt_original_post;

		if ( ! empty( $dt_original_post ) && ! empty( $dt_original_post->permalink ) ) {
			return sprintf( __( '<strong>Permalink:</strong> <a href="%s">%s</a>', 'distributor' ), esc_url( $dt_original_post->permalink ), esc_url( $dt_original_post->permalink ) );
		}

		return $permalink_html;
	}

	/**
	 * Restore current blog and post after canonicalization in the admin
	 *
	 * @since 0.8
	 */
	public static function end_canonical_admin_post() {
		global $dt_original_post, $post;

		if ( ! empty( $dt_original_post ) ) {
			restore_current_blog();
			$post = $dt_original_post;
		}
	}

	/**
	 * Setup canonicalization on back end
	 *
	 * @since  0.8
	 */
	public static function canonical_admin_post() {
		global $post, $pagenow, $dt_original_post, $dt_blog_id;

		if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
	    	return;
	    }

	    $original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		$syndicate_time = get_post_meta( $post->ID, 'dt_syndicate_time', true );

		if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
			return;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( $unlinked ) {
			return;
		}

		$dt_blog_id = get_current_blog_id();
		$dt_original_post = $post;
		$dt_original_post->permalink = ( 'publish' === $post->post_status ) ? get_permalink( $post->ID ) : get_preview_post_link( $post );
		$dt_original_post->syndicate_time = $syndicate_time;

		switch_to_blog( $original_blog_id );
		$post = get_post( $original_post_id );
		$post->post_status = $dt_original_post->post_status;
	}

	/**
	 * Check if current user can create a post type with ajax
	 *
	 * @since  0.8
	 */
	public static function auth_check() {
		if ( ! check_ajax_referer( 'dt-auth-check', 'nonce', false ) ) {
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

			$current_user = wp_get_current_user();

			$response = wp_remote_post( untrailingslashit( $base_url ) . '/wp-admin/admin-ajax.php', array(
				'body' => array(
					'nonce'     => wp_create_nonce( 'dt-auth-check' ),
					'username'  => $current_user->user_login,
					'action'    => 'dt_auth_check',
				),
				'cookies' => $_COOKIE,
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
	 * Setup canonicalization on front end
	 *
	 * @since  0.8
	 */
	public static function canonicalize_front_end() {
		add_filter( 'the_title', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'the_title' ), 10, 2 );
		add_filter( 'the_content', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'the_content' ), 10, 1 );
		add_filter( 'the_date', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'the_date' ), 10, 1 );
		add_filter( 'get_the_excerpt', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'get_the_excerpt' ), 10, 1 );
		add_filter( 'get_canonical_url', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'canonical_url' ), 10, 2 );
		add_filter( 'post_thumbnail_html', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'post_thumbnail' ), 10, 2 );
		add_filter( 'get_the_terms', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'get_the_terms' ), 10, 3 );
		add_filter( 'term_link', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'term_link' ), 10, 3 );
	}

	/**
	 * [term_link description]
	 * @param  string $termlink
	 * @param  object $term
	 * @param  string $taxonomy
	 * @since  0.8
	 * @return string
	 */
	public static function term_link( $termlink, $term, $taxonomy ) {
		global $post;

		if ( empty( $post ) ) {
			return $termlink;
		}

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

		if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
			return $termlink;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( $unlinked ) {
			return $termlink;
		}

		switch_to_blog( $original_blog_id );
		$termlink = get_term_link( $term, $taxonomy );
		restore_current_blog();

		return $termlink;
	}

	/**
	 * Filter terms for linked posts
	 *
	 * @since  0.8
	 * @return array
	 */
	public static function get_the_terms( $terms, $post_id, $taxonomy ) {
		$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );

		if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
			return $terms;
		}

		$unlinked = (bool) get_post_meta( $post_id, 'dt_unlinked', true );

		if ( $unlinked ) {
			return $terms;
		}

		switch_to_blog( $original_blog_id );
		$terms = wp_get_object_terms( $original_post_id, $taxonomy );
		restore_current_blog();

		return $terms;
	}

	/**
	 * Return canonical post thumbnail URL
	 * 
	 * @param  string $html
	 * @param  int $id
	 * @since  0.8
	 * @return string
	 */
	public static function post_thumbnail( $html, $id ) {
		$original_blog_id = get_post_meta( $id, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $id, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $html;
		}

		$unlinked = (bool) get_post_meta( $id, 'dt_unlinked', true );

		if ( $unlinked ) {
			return $html;
		}

		switch_to_blog( $original_blog_id );
		$html = get_the_post_thumbnail( $original_post_id );
		restore_current_blog();

		return $html;
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
		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $canonical_url;
		}

		switch_to_blog( $original_blog_id );
		$canonical_url = get_permalink( $original_post_id );
		restore_current_blog();

		return $canonical_url;
	}

	/**
	 * Use canonical title
	 *
	 * @param  string $title
	 * @param  int    $id
	 * @since  0.8
	 * @return string
	 */
	public static function the_title( $title, $id ) {
		$original_blog_id = get_post_meta( $id, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $id, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $title;
		}

		$unlinked = (bool) get_post_meta( $id, 'dt_unlinked', true );

		if ( $unlinked ) {
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

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $content;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( $unlinked ) {
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

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $date;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( $unlinked ) {
			return $date;
		}

		switch_to_blog( $original_blog_id );

		$date = get_the_date( get_option( 'date_format' ), $original_post_id );

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
		global $post;

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return $excerpt;
		}

		$unlinked = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( $unlinked ) {
			return $excerpt;
		}

		switch_to_blog( $original_blog_id );
		$original_post = get_post( $original_post_id );
		$excerpt = $original_post->post_excerpt;
		restore_current_blog();

		return $excerpt;
	}
}
