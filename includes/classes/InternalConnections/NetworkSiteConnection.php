<?php
/**
 * Network site functionality
 *
 * @package  distributor
 */

namespace Distributor\InternalConnections;

use \Distributor\Connection as Connection;
use Distributor\Utils;
use \WP_Site as WP_Site;

/**
 * A network site connection let's you push and pull content within your blog
 */
class NetworkSiteConnection extends Connection {

	/**
	 * Current site
	 *
	 * @var WP_Site
	 */
	public $site;

	/**
	 * Connection slug
	 *
	 * @var string
	 */
	static public $slug = 'networkblog';

	/**
	 * Default post type to pull.
	 *
	 * @var string
	 */
	public $pull_post_type;

	/**
	 * Default post types supported.
	 *
	 * @var string
	 */
	public $pull_post_types;

	/**
	 * Set up network site connection
	 *
	 * @param WP_Site $site Site object.
	 * @since  0.8
	 */
	public function __construct( WP_Site $site ) {
		$this->site = $site;
	}

	/**
	 * Push post to another internal site
	 *
	 * @param  int   $post_id Post ID.
	 * @param  array $args Push args.
	 * @since  0.8
	 * @return int|WP_Error
	 */
	public function push( $post_id, $args = array() ) {
		$post              = get_post( $post_id );
		$original_blog_id  = get_current_blog_id();
		$original_post_url = get_permalink( $post_id );
		$using_gutenberg   = \Distributor\Utils\is_using_gutenberg();

		$new_post_args = array(
			'post_title'   => get_the_title( $post_id ),
			'post_name'    => $post->post_name,
			'post_content' => apply_filters( 'the_content', $post->post_content ),
			'post_excerpt' => $post->post_excerpt,
			'post_type'    => $post->post_type,
			'post_author'  => get_current_user_id(),
			'post_status'  => 'publish',
		);

		$media = \Distributor\Utils\prepare_media( $post_id );
		$terms = \Distributor\Utils\prepare_taxonomy_terms( $post_id );
		$meta  = \Distributor\Utils\prepare_meta( $post_id );

		switch_to_blog( $this->site->blog_id );

		// Distribute raw HTML when going from Gutenberg enabled to Gutenberg enabled.
		$remote_using_gutenberg = \Distributor\Utils\is_using_gutenberg();
		if ( $using_gutenberg && $remote_using_gutenberg ) {
			$new_post_args['post_content'] = $post->post_content;
		}

		// Handle existing posts.
		if ( ! empty( $args['remote_post_id'] ) && get_post( $args['remote_post_id'] ) ) {

			// Setting the ID makes `wp_insert_post` perform an update.
			$new_post_args['ID'] = $args['remote_post_id'];
		}

		if ( empty( $args['post_status'] ) ) {
			if ( isset( $new_post_args['ID'] ) ) {

				// Avoid updating the status of previously distributed posts.
				$existing_status = get_post_status( (int) $new_post_args['ID'] );
				if ( $existing_status ) {
					$new_post_args['post_status'] = $existing_status;
				}
			}
		} else {
			$new_post_args['post_status'] = $args['post_status'];
		}

		add_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

		$new_post_id = wp_insert_post( apply_filters( 'dt_push_post_args', $new_post_args, $post, $args, $this ) );

		remove_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

		if ( ! is_wp_error( $new_post_id ) ) {
			update_post_meta( $new_post_id, 'dt_original_post_id', (int) $post_id );
			update_post_meta( $new_post_id, 'dt_original_blog_id', (int) $original_blog_id );
			update_post_meta( $new_post_id, 'dt_syndicate_time', time() );
			update_post_meta( $new_post_id, 'dt_original_post_url', esc_url_raw( $original_post_url ) );

			if ( ! empty( $post->post_parent ) ) {
				update_post_meta( $new_post, 'dt_original_post_parent', (int) $post->post_parent );
			}

			\Distributor\Utils\set_meta( $new_post_id, $meta );
			\Distributor\Utils\set_taxonomy_terms( $new_post_id, $terms );

			/**
			 * Allow bypassing of all media processing.
			 *
			 * @param bool                  true           If Distributor should set the post media.
			 * @param int                   $new_post_id   The newly created post ID.
			 * @param array                 $media         List of media items attached to the post, formatted by {@see \Distributor\Utils\prepare_media()}.
			 * @param int                   $post_id       The original post ID.
			 * @param array                 $args          The arguments passed into wp_insert_post.
			 * @param NetworkSiteConnection $this          The distributor connection being pushed to.
			 */
			if ( apply_filters( 'dt_push_post_media', true, $new_post_id, $media, $post_id, $args, $this ) ) {
				\Distributor\Utils\set_media( $new_post_id, $media );
			};
		}

		/**
		 * Action triggered when a post is pushed via distributor.
		 *
		 * @param int                $new_post_id   The newly created post ID.
		 * @param int                $post_id       The original post ID.
		 * @param array              $args          The arguments passed into wp_insert_post.
		 * @param ExternalConnection $this          The distributor connection being pushed to.
		 */
		do_action( 'dt_push_post', $new_post_id, $post_id, $args, $this );

		restore_current_blog();

		return $new_post_id;
	}

	/**
	 * Pull items. Pass array of posts, each post should look like:
	 * [ 'remote_post_id' => POST ID TO GET, 'post_id' (optional) => POST ID TO MAP TO ]
	 *
	 * @param  array $items Array of items to pull.
	 * @since  0.8
	 * @return array
	 */
	public function pull( $items ) {
		global $dt_pull_messages;

		$created_posts = array();

		foreach ( $items as $item_array ) {
			$post = $this->remote_get( [ 'id' => $item_array['remote_post_id'] ] );

			if ( is_wp_error( $post ) ) {
				$created_posts[] = $post;
				continue;
			}

			$post_props      = get_object_vars( $post );
			$post_array      = array();
			$current_blog_id = get_current_blog_id();

			if ( ! empty( $post_props['meta']['dt_connection_map'] ) ) {
				foreach ( $post_props['meta']['dt_connection_map'] as $distributed ) {
					$distributed = unserialize( $distributed );

					if ( array_key_exists( $current_blog_id, $distributed['internal'] ) ) {
						$dt_pull_messages['duplicated'] = 1;
						continue 2;
					}
				}
			}

			foreach ( $post_props as $key => $value ) {
				$post_array[ $key ] = $value;
			}

			if ( ! empty( $item_array['post_id'] ) ) {
				$post_array['ID'] = $item_array['post_id'];
			} else {
				unset( $post_array['ID'] );
			}

			if ( isset( $post_array['post_parent'] ) ) {
				unset( $post_array['post_parent'] );
			}

			add_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

			$new_post_id = wp_insert_post( apply_filters( 'dt_pull_post_args', $post_array, $item_array['remote_post_id'], $post, $this ) );

			remove_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

			if ( ! is_wp_error( $new_post_id ) ) {
				update_post_meta( $new_post_id, 'dt_original_post_id', (int) $item_array['remote_post_id'] );
				update_post_meta( $new_post_id, 'dt_original_blog_id', (int) $this->site->blog_id );
				update_post_meta( $new_post_id, 'dt_syndicate_time', time() );
				update_post_meta( $new_post_id, 'dt_original_post_url', esc_url_raw( $post->link ) );

				if ( ! empty( $post->post_parent ) ) {
					update_post_meta( $new_post, 'dt_original_post_parent', (int) $post->post_parent );
				}

				\Distributor\Utils\set_meta( $new_post_id, $post->meta );
				\Distributor\Utils\set_taxonomy_terms( $new_post_id, $post->terms );

				/**
				 * Allow bypassing of all media processing.
				 *
				 * @param bool                  true                          If Distributor should set the post media.
				 * @param int                   $new_post_id                  The newly created post ID.
				 * @param array                 $post->media                  List of media items attached to the post, formatted by {@see \Distributor\Utils\prepare_media()}.
				 * @param int                   $item_array['remote_post_id'] The original post ID.
				 * @param array                 $post_array                   The arguments passed into wp_insert_post.
				 * @param NetworkSiteConnection $this                         The distributor connection being pulled from.
				 */
				if ( apply_filters( 'dt_pull_post_media', true, $new_post_id, $post->media, $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_media( $new_post_id, $post->media );
				};
			}

			switch_to_blog( $this->site->blog_id );

			$connection_map = get_post_meta( $item_array['remote_post_id'], 'dt_connection_map', true );

			if ( empty( $connection_map ) ) {
				$connection_map = [
					'internal' => [],
					'external' => [],
				];
			}

			if ( empty( $connection_map['internal'] ) ) {
				$connection_map['internal'] = [];
			}

			$connection_map['internal'][ $current_blog_id ] = [
				'post_id' => (int) $new_post_id,
				'time'    => time(),
			];

			update_post_meta( $item_array['remote_post_id'], 'dt_connection_map', $connection_map );

			restore_current_blog();

			/**
			 * Action triggered when a post is pulled via distributor.
			 *
			 * @since 1.0
			 *
			 * @param int                $new_post_id   The new post ID that was pulled.
			 * @param ExternalConnection $this          The distributor connection pulling the post.
			 * @param array              $post_array    The original post data retrieved via the connection.
			 */
			do_action( 'dt_pull_post', $new_post_id, $this, $post_array );

			$created_posts[] = $new_post_id;
		}

		return $created_posts;
	}

	/**
	 * Log a sync. Unfortunately have to use options. We store like this:
	 *
	 * {
	 *  original_connection_id: {
	 *      old_post_id: new_post_id (false means skipped)
	 *  }
	 * }
	 *
	 * This let's us grab all the IDs of posts we've PULLED from a given site
	 *
	 * @param array $item_id_mappings Mapping to log; key = origin post ID, value = new post ID.
	 * @param int   $blog_id Blog ID
	 * @since 0.8
	 */
	public function log_sync( array $item_id_mappings, $blog_id = 0 ) {
		$blog_id = 0 === $blog_id ? $this->site->blog_id : $blog_id;

		$sync_log = get_option( 'dt_sync_log', array() );

		$current_site_log = [];
		if ( ! empty( $sync_log[ $blog_id ] ) ) {
			$current_site_log = $sync_log[ $blog_id ];
		}

		foreach ( $item_id_mappings as $old_item_id => $new_item_id ) {
			if ( empty( $new_item_id ) || is_wp_error( $new_item_id ) ) {
				$current_site_log[ $old_item_id ] = false;
			} else {
				$current_site_log[ $old_item_id ] = (int) $new_item_id;
			}
		}

		$sync_log[ $blog_id ] = $current_site_log;

		update_option( 'dt_sync_log', $sync_log );

		/**
		 * Action fired when a sync is being logged.
		 *
		 * @since 1.0
		 *
		 * @param array $item_id_mappings Item ID mappings.
		 * @param array $sync_log The sync log
		 * @param object $this This class.
		 */
		do_action( 'dt_log_sync', $item_id_mappings, $sync_log, $this );
	}

	/**
	 * Get the available post types.
	 *
	 * @since 1.3
	 * @return array
	 */
	public function get_post_types() {
		switch_to_blog( $this->site->blog_id );
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		restore_current_blog();

		return $post_types;
	}

	/**
	 * Remotely get posts so we can list them for pulling
	 *
	 * @param  array $args Array of args for getting.
	 * @since  0.8
	 * @return array|WP_Post|bool
	 */
	public function remote_get( $args = array() ) {

		$id = ( empty( $args['id'] ) ) ? false : $args['id'];

		switch_to_blog( $this->site->blog_id );

		$query_args = array();

		if ( empty( $id ) ) {

			if ( isset( $args['post__in'] ) ) {
				if ( empty( $args['post__in'] ) ) {

					// If post__in is empty, we can just stop right here
					restore_current_blog();

					return apply_filters(
						'dt_remote_get',
						[
							'items'       => array(),
							'total_items' => 0,
						],
						$args,
						$this
					);
				}

				$query_args['post__in'] = $args['post__in'];
			} elseif ( isset( $args['post__not_in'] ) ) {
				$query_args['post__not_in'] = $args['post__not_in'];
			}

			$query_args['post_type']      = ( empty( $args['post_type'] ) ) ? 'post' : $args['post_type'];
			$query_args['post_status']    = ( empty( $args['post_status'] ) ) ? [ 'publish', 'draft', 'private', 'pending', 'future' ] : $args['post_status'];
			$query_args['posts_per_page'] = ( empty( $args['posts_per_page'] ) ) ? get_option( 'posts_per_page' ) : $args['posts_per_page'];
			$query_args['paged']          = ( empty( $args['paged'] ) ) ? 1 : $args['paged'];

			if ( isset( $args['meta_query'] ) ) {
				$query_args['meta_query'] = $args['meta_query'];
			}

			if ( isset( $args['s'] ) ) {
				$query_args['s'] = $args['s'];
			}

			$posts_query = new \WP_Query( apply_filters( 'dt_remote_get_query_args', $query_args, $args, $this ) );

			$posts = $posts_query->posts;

			$formatted_posts = [];

			foreach ( $posts as $post ) {
				$post->link  = get_permalink( $post->ID );
				$post->meta  = \Distributor\Utils\prepare_meta( $post->ID );
				$post->terms = \Distributor\Utils\prepare_taxonomy_terms( $post->ID );
				$post->media = \Distributor\Utils\prepare_media( $post->ID );

				$formatted_posts[] = $post;
			}

			restore_current_blog();

			return apply_filters(
				'dt_remote_get',
				[
					'items'       => $formatted_posts,
					'total_items' => $posts_query->found_posts,
				],
				$args,
				$this
			);

		} else {
			$post = get_post( $id );

			if ( empty( $post ) ) {
				$formatted_post = false;
			} else {
				$post->link  = get_permalink( $id );
				$post->meta  = \Distributor\Utils\prepare_meta( $id );
				$post->terms = \Distributor\Utils\prepare_taxonomy_terms( $id );
				$post->media = \Distributor\Utils\prepare_media( $id );

				$formatted_post = $post;
			}

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
		add_action( 'save_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'update_syndicated' ) );
		add_action( 'before_delete_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'separate_syndicated_on_delete' ) );
		add_action( 'before_delete_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'remove_distributor_post_from_original' ) );
		add_action( 'wp_trash_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'separate_syndicated_on_delete' ) );
		add_action( 'untrash_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'connect_syndicated_on_untrash' ) );
	}

	/**
	 * Make the original post available for distribution when deleting a post.
	 *
	 * @param  int $post_id Post ID.
	 * @since  1.2
	 */
	public static function remove_distributor_post_from_original( $post_id ) {
		$original_blog_id = get_post_meta( $post_id, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post_id, 'dt_original_post_id', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) ) {
			return;
		}

		$blog_id = get_current_blog_id();

		switch_to_blog( $original_blog_id );

		$connection_map = get_post_meta( $original_post_id, 'dt_connection_map', true );

		if ( ! empty( $connection_map['internal'] ) && ! empty( $connection_map['internal'][ (int) $blog_id ] ) ) {
			unset( $connection_map['internal'][ (int) $blog_id ] );

			update_post_meta( $original_post_id, 'dt_connection_map', $connection_map );
		}

		restore_current_blog();

		// Mark deleted post as being skipped in the sync log.
		$sync_log = get_option( 'dt_sync_log', array() );

		if ( isset( $sync_log[ $original_blog_id ][ $original_post_id ] ) ) {
			$sync_log[ $original_blog_id ][ $original_post_id ] = false;
			update_option( 'dt_sync_log', $sync_log );
		}
	}

	/**
	 * When an original is deleted, we need to let internal syndicated posts know
	 *
	 * @param  int $post_id Post ID.
	 * @since 1.0
	 */
	public static function separate_syndicated_on_delete( $post_id ) {
		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );

		// If no connections do nothing
		if ( empty( $connection_map ) || empty( $connection_map['internal'] ) ) {
			return;
		}

		foreach ( $connection_map['internal'] as $blog_id => $post_array ) {
			$connection = new self( get_site( $blog_id ) );

			switch_to_blog( $blog_id );

			$unlinked = (bool) get_post_meta( $post_array['post_id'], 'dt_unlinked', true );

			update_post_meta( $post_array['post_id'], 'dt_original_post_deleted', true );

			restore_current_blog();

			if ( 'trash' !== get_post_status( $post_id ) && ! $unlinked ) {
				$connection->push( $post_id, array( 'remote_post_id' => $post_array['post_id'] ) );
			}
		}
	}

	/**
	 * When an original is untrashed, we need to let internal syndicated posts know
	 *
	 * @param  int $post_id Post ID.
	 * @since 1.0
	 */
	public static function connect_syndicated_on_untrash( $post_id ) {
		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );

		// If no connections do nothing
		if ( empty( $connection_map ) || empty( $connection_map['internal'] ) ) {
			return;
		}

		foreach ( $connection_map['internal'] as $site_id => $post_array ) {
			switch_to_blog( $site_id );

			delete_post_meta( $post_array['post_id'], 'dt_original_post_deleted' );

			restore_current_blog();
		}
	}

	/**
	 * Update syndicated post when original changes
	 *
	 * @param  int $post_id Post ID.
	 */
	public static function update_syndicated( $post_id ) {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( 'trash' === get_post_status( $post_id ) ) {
			return;
		}

		$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );

		if ( empty( $connection_map ) || ! is_array( $connection_map ) || empty( $connection_map['internal'] ) ) {
			return;
		}

		foreach ( $connection_map['internal'] as $blog_id => $syndicated_post ) {
			// Make sure this site is still available
			$site = get_site( (int) $blog_id );
			if ( null === $site ) {

				// If the site isn't available anymore, remove this item from the connection map
				if ( ! empty( $connection_map['internal'][ (int) $blog_id ] ) ) {
					unset( $connection_map['internal'][ (int) $blog_id ] );

					update_post_meta( $post_id, 'dt_connection_map', $connection_map );
				}

				continue;
			}

			$connection = new self( $site );

			switch_to_blog( $blog_id );

			$unlinked = (bool) get_post_meta( $syndicated_post['post_id'], 'dt_unlinked', true );

			restore_current_blog();

			if ( ! $unlinked ) {
				$connection->push( $post_id, array( 'remote_post_id' => $syndicated_post['post_id'] ) );
			}
		}
	}

	/**
	 * Maybe set post modified date
	 * On wp_insert_post, modified date is overriden by post date
	 *
	 * https://core.trac.wordpress.org/browser/tags/4.7.2/src/wp-includes/post.php#L3151
	 *
	 * @param array $data Post data.
	 * @param array $postarr Post args.
	 * @since 0.8.1
	 * @return array
	 */
	public static function maybe_set_modified_date( $data, $postarr ) {
		if ( ! empty( $postarr['post_modified'] ) && ! empty( $postarr['post_modified_gmt'] ) ) {
			$data['post_modified']     = $postarr['post_modified'];
			$data['post_modified_gmt'] = $postarr['post_modified_gmt'];
		}

		return $data;
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

		$post_types            = get_post_types();
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

		/**
		 * Allow plugins to override the default {@see \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites()} function.
		 *
		 * @since 1.2
		 *
		 * @param array  $authorized_sites {
		 *     @type array {
		 *         'site'       => $site,  // WP_Site object.
		 *         'post_types' => $array, // List of post type objects the user can edit.
		 * }
		 */
		$authorized_sites = apply_filters( 'dt_pre_get_authorized_sites', array() );
		if ( ! empty( $authorized_sites ) ) {
			return $authorized_sites;
		}

		$sites           = get_sites();
		$current_blog_id = (int) get_current_blog_id();
		$current_user    = wp_get_current_user();

		foreach ( $sites as $site ) {
			$blog_id = (int) $site->blog_id;

			if ( $blog_id === $current_blog_id ) {
				continue;
			}

			$base_url = get_site_url( $blog_id );

			if ( empty( $base_url ) ) {
				continue;
			}

			$response = wp_remote_post(
				untrailingslashit( $base_url ) . '/wp-admin/admin-ajax.php',
				array(
					'body'    => array(
						'nonce'    => wp_create_nonce( 'dt-auth-check' ),
						'username' => $current_user->user_login,
						'action'   => 'dt_auth_check',
					),
					'cookies' => $_COOKIE, // WPCS: Input var ok.
				)
			);

			if ( ! is_wp_error( $response ) ) {

				$body = wp_remote_retrieve_body( $response );

				if ( ! empty( $body ) ) {
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

		/**
		 * Allow plugins to modify the array of authorized sites.
		 *
		 * @since 1.2
		 *
		 * @param array  $authorized_sites {
		 *     @type array {
		 *         'site'       => $site,  // WP_Site object.
		 *         'post_types' => $array, // List of post type objects the user can edit.
		 * }
		 */
		return apply_filters( 'dt_authorized_sites', $authorized_sites );
	}

	/**
	 * Setup canonicalization on front end
	 *
	 * @since  0.8
	 */
	public static function canonicalize_front_end() {
		add_filter( 'get_canonical_url', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'canonical_url' ), 10, 2 );
		add_filter( 'wpseo_canonical', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'wpseo_canonical_url' ) );
		add_filter( 'wpseo_opengraph_url', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'wpseo_og_url' ) );
		add_filter( 'the_author', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'the_author_distributed' ) );
		add_filter( 'author_link', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'author_posts_url_distributed' ), 10, 3 );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @param  string $link Author link
	 * @param  int    $author_id Author id.
	 * @param  string $author_nicename Author name.
	 * @since  1.0
	 * @return string
	 */
	public static function author_posts_url_distributed( $link, $author_id, $author_nicename ) {
		global $post;

		if ( empty( $post ) ) {
			return $link;
		}

		$settings = Utils\get_settings();

		if ( empty( $settings['override_author_byline'] ) ) {
			return $link;
		}

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		$unlinked         = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) || $unlinked ) {
			return $link;
		}

		return get_home_url( $original_blog_id );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @param  string $author Author name.
	 * @since  1.0
	 * @return string
	 */
	public static function the_author_distributed( $author ) {
		global $post;

		if ( empty( $post ) ) {
			return $author;
		}

		$settings = Utils\get_settings();

		if ( empty( $settings['override_author_byline'] ) ) {
			return $author;
		}

		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		$unlinked         = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) || $unlinked ) {
			return $author;
		}

		$blog_details = get_blog_details( $original_blog_id );

		return $blog_details->blogname;
	}

	/**
	 * Make sure canonical url header is outputted
	 *
	 * @param  string $canonical_url Canonical url.
	 * @param  object $post Post object.
	 * @since  0.8
	 * @return string
	 */
	public static function canonical_url( $canonical_url, $post ) {
		$original_blog_id = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		$unlinked         = (bool) get_post_meta( $post->ID, 'dt_unlinked', true );
		$original_deleted = (bool) get_post_meta( $post->ID, 'dt_original_post_deleted', true );

		if ( empty( $original_blog_id ) || empty( $original_post_id ) || $unlinked || $original_deleted ) {
			return $canonical_url;
		}

		$original_post_url = get_post_meta( $post->ID, 'dt_original_post_url', true );

		return $original_post_url;
	}

	/**
	 * Handles the canonical URL change for distributed content when Yoast SEO is in use
	 *
	 * @param string $canonical_url The Yoast WPSEO deduced canonical URL
	 * @since  1.0
	 * @return string $canonical_url The updated distributor friendly URL
	 */
	public static function wpseo_canonical_url( $canonical_url ) {

		// Return as is if not on a singular page - taken from rel_canonical()
		if ( ! is_singular() ) {
			$canonical_url;
		}

		$id = get_queried_object_id();

		// Return as is if we do not have a object id for context - taken from rel_canonical()
		if ( 0 === $id ) {
			return $canonical_url;
		}

		$post = get_post( $id );

		// Return as is if we don't have a valid post object - taken from wp_get_canonical_url()
		if ( ! $post ) {
			return $canonical_url;
		}

		// Return as is if current post is not published - taken from wp_get_canonical_url()
		if ( 'publish' !== $post->post_status ) {
			return $canonical_url;
		}

		return self::canonical_url( $canonical_url, $post );
	}

	/**
	 * Handles the og:url change for distributed content when Yoast SEO is in use
	 *
	 * @param string $og_url The Yoast WPSEO deduced OG URL which is a result of wpseo_canonical_url
	 *
	 * @return string $og_url The updated distributor friendly URL
	 */
	public static function wpseo_og_url( $og_url ) {
		if ( is_singular() ) {
			$og_url = get_permalink();
		}

		return $og_url;
	}
}
