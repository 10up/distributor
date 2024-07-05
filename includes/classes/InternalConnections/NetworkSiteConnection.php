<?php
/**
 * Network site functionality
 *
 * @package  distributor
 */

namespace Distributor\InternalConnections;

use \Distributor\DistributorPost;
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
	public static $slug = 'networkblog';

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
	 * @param  int|WP_Post $post Post or Post ID to push. Required.
	 * @param  array       $args {
	 *     Optional. Array of push arguments
	 *
	 *     @type int    $remote_post_id Post ID on remote site. If not provided,
	 *                                  a new post will be created.
	 *     @type string $post_status    The post status to use on the remote site.
	 *                                  Ignored when updating posts. Default 'publish'.
	 * }
	 * @since  0.8
	 * @return array|\WP_Error
	 */
	public function push( $post, $args = array() ) {
		if ( empty( $post ) ) {
			return new \WP_Error( 'no-push-post-id', esc_html__( 'Post ID required to push.', 'distributor' ) );
		}
		$post = get_post( $post );
		if ( empty( $post ) ) {
			return new \WP_Error( 'invalid-push-post-id', esc_html__( 'Post does not exist.', 'distributor' ) );
		}
		$post_id = $post->ID;

		$args = wp_parse_args(
			$args,
			array(
				'post_status' => 'publish',
			)
		);

		$dt_post          = new DistributorPost( $post_id );
		$dt_post_args     = $dt_post->to_insert( $args );
		$original_blog_id = get_current_blog_id();
		$output           = array();
		$post             = Utils\prepare_post( get_post( $post_id ) );
		$update           = false;
		$post_meta        = $dt_post->get_meta();
		$post_terms       = $dt_post->get_terms();
		$post_media       = $dt_post->get_media();

		switch_to_blog( $this->site->blog_id );

		if ( ! empty( $args['remote_post_id'] ) && ! get_post( $args['remote_post_id'] ) ) {
			// The remote post ID is not valid.
			restore_current_blog();
			return new \WP_Error( 'dt_invalid_remote_post_id', __( 'Invalid remote post ID', 'distributor' ) );
		}

		// If we have a remote post ID, we're updating.
		if ( ! empty( $args['remote_post_id'] ) ) {
			$update = true;
		}

		add_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );
		// Filter documented in includes/classes/ExternalConnections/WordPressExternalConnection.php
		$new_post_args = Utils\post_args_allow_list( apply_filters( 'dt_push_post_args', $dt_post_args, $post, $args, $this ) );
		if ( $update ) {
			$new_post_id = wp_update_post( wp_slash( $new_post_args ), true );
		} else {
			$new_post_id = wp_insert_post( wp_slash( $new_post_args ) );
		}
		remove_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

		if ( is_wp_error( $new_post_id ) ) {
			/**
			 * Fires after a post is pushed via Distributor before `restore_current_blog()`.
			 *
			 * @since 1.2.2
			 * @deprecated 2.0.0 The dt_push_post action has been deprecated. Please use dt_push_network_post or dt_push_external_post instead.
			 * @hook  dt_push_post
			 *
			 * @param {int}        $new_post_id The newly created post.
			 * @param {int}        $post_id     The original post.
			 * @param {array}      $args        The arguments passed into wp_insert_post.
			 * @param {Connection} $this        The Distributor connection being pushed to.
			 */
			do_action_deprecated(
				'dt_push_post',
				array( $new_post_id, $post_id, $args, $this ),
				'2.0.0',
				'dt_push_network_post|dt_push_external_post'
			);

			/**
			 * Fires the action after a post is pushed via Distributor before `restore_current_blog()`.
			 *
			 * @since 2.0.0
			 * @hook  dt_push_network_post
			 *
			 * @param {int}                   $new_post_id The newly created post.
			 * @param {int}                   $post_id     The original post.
			 * @param {array}                 $args        The arguments passed into wp_insert_post.
			 * @param {NetworkSiteConnection} $this        The Distributor connection being pushed to.
			 */
			do_action( 'dt_push_network_post', $new_post_id, $post_id, $args, $this );

			restore_current_blog();

			return $new_post_id;
		}


		$output['id'] = $new_post_id;

		update_post_meta( $new_post_id, 'dt_original_blog_id', absint( $original_blog_id ) );
		update_post_meta( $new_post_id, 'dt_syndicate_time', absint( time() ) );

		/**
		 * Allow bypassing of all media processing.
		 *
		 * @hook dt_push_post_media
		 *
		 * @param {bool}       true           If Distributor should push the post media.
		 * @param {int}        $new_post_id   The newly created post ID.
		 * @param {array}      $post_media    List of media items attached to the post, formatted by {@link \Distributor\Utils\prepare_media()}.
		 * @param {int}        $post_id       The original post ID.
		 * @param {array}      $args          The arguments passed into wp_insert_post.
		 * @param {Connection} $this          The distributor connection being pushed to.
		 *
		 * @return {bool} If Distributor should push the post media.
		 */
		if ( apply_filters( 'dt_push_post_media', true, $new_post_id, $post_media, $post_id, $args, $this ) ) {
			Utils\set_media( $new_post_id, $post_media, [ 'use_filesystem' => true ] );
		};

		$media_errors = get_transient( 'dt_media_errors_' . $new_post_id );

		if ( $media_errors ) {
			$output['push-errors'] = $media_errors;
			delete_transient( 'dt_media_errors_' . $new_post_id );
		}

		/**
		 * Allow bypassing of all term processing.
		 *
		 * @hook dt_push_post_terms
		 *
		 * @param {bool}       true           If Distributor should push the post terms.
		 * @param {int}        $new_post_id   The newly created post ID.
		 * @param {array}      $post_terms    Terms attached to the post, formatted by {@link \Distributor\Utils\prepare_taxonomy_terms()}.
		 * @param {int}        $post_id       The original post ID.
		 * @param {array}      $args          The arguments passed into wp_insert_post.
		 * @param {Connection} $this          The distributor connection being pushed to.
		 *
		 * @return {bool} If Distributor should push the post terms.
		 */
		if ( apply_filters( 'dt_push_post_terms', true, $new_post_id, $post_terms, $post_id, $args, $this ) ) {
			Utils\set_taxonomy_terms( $new_post_id, $post_terms );
		}

		/**
		 * Allow bypassing of all meta processing.
		 *
		 * @hook dt_push_post_meta
		 *
		 * @param {bool}       true           If Distributor should push the post meta.
		 * @param {int}        $new_post_id   The newly created post ID.
		 * @param {array}      $post_meta     Meta attached to the post, formatted by {@link \Distributor\Utils\prepare_meta()}.
		 * @param {int}        $post_id       The original post ID.
		 * @param {array}      $args          The arguments passed into wp_insert_post.
		 * @param {Connection} $this          The distributor connection being pushed to.
		 *
		 * @return {bool} If Distributor should push the post meta.
		 */
		if ( apply_filters( 'dt_push_post_meta', true, $new_post_id, $post_meta, $post_id, $args, $this ) ) {
			$post_meta = $this->exclude_additional_meta_data( $post_meta );
			Utils\set_meta( $new_post_id, $post_meta );
		}

		/** This filter is documented in includes/classes/InternalConnections/NetworkSiteConnection.php */
		do_action_deprecated(
			'dt_push_post',
			array( $new_post_id, $post_id, $args, $this ),
			'2.0.0',
			'dt_push_network_post|dt_push_external_post',
			esc_html__( 'The dt_push_post action has been deprecated. Please use dt_push_network_post or dt_push_external_post instead.', 'distributor' )
		);

		/** This filter is documented in includes/classes/InternalConnections/NetworkSiteConnection.php */
		do_action( 'dt_push_network_post', $new_post_id, $post_id, $args, $this );

		restore_current_blog();

		return $output;
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
			$update      = false;
			$insert_args = array();
			if ( ! empty( $item_array['post_status'] ) ) {
				$insert_args['post_status'] = $item_array['post_status'];
			}
			if ( ! empty( $item_array['post_id'] ) ) {
				$insert_args['remote_post_id'] = $item_array['post_id'];
				$update                        = true;
			}

			$post = $this->remote_get( [ 'id' => $item_array['remote_post_id'] ], $insert_args );

			if ( is_wp_error( $post ) ) {
				$created_posts[] = $post;
				continue;
			}

			$post_props      = $post;
			$post_array      = $post_props;
			$current_blog_id = get_current_blog_id();

			if ( ! empty( $post_props['meta']['dt_connection_map'] ) ) {
				foreach ( $post_props['meta']['dt_connection_map'] as $connection_type => $distributed ) {
					$distributed = maybe_unserialize( $distributed );

					if ( 'internal' === $connection_type && array_key_exists( $current_blog_id, $distributed ) ) {
						$dt_pull_messages['duplicated'] = 1;
						continue 2;
					}
				}
			}

			add_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

			// Filter documented in includes/classes/ExternalConnections/WordPressExternalConnection.php
			$new_post_args = Utils\post_args_allow_list( apply_filters( 'dt_pull_post_args', $post_array, $item_array['remote_post_id'], $post, $this ) );
			if ( $update ) {
				$new_post_id = wp_update_post( wp_slash( $new_post_args ) );
			} else {
				$new_post_id = wp_insert_post( wp_slash( $new_post_args ) );
			}
			remove_filter( 'wp_insert_post_data', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'maybe_set_modified_date' ), 10, 2 );

			if ( ! is_wp_error( $new_post_id ) ) {
				update_post_meta( $new_post_id, 'dt_original_blog_id', absint( $this->site->blog_id ) );
				update_post_meta( $new_post_id, 'dt_syndicate_time', absint( time() ) );
				update_post_meta( $new_post_id, 'dt_original_post_id', absint( $new_post_args['meta_input']['dt_original_post_id'] ) );
				update_post_meta( $new_post_id, 'dt_original_post_url', wp_slash( sanitize_url( $new_post_args['meta_input']['dt_original_post_url'] ) ) );

				/**
				 * Allow bypassing of all media processing.
				 *
				 * @hook dt_pull_post_media
				 *
				 * @param {bool}                  true            If Distributor should set the post media.
				 * @param {int}                   $new_post_id    The newly created post ID.
				 * @param {array}                 $post_media     List of media items attached to the post, formatted by {@link \Distributor\Utils\prepare_media()}.
				 * @param {int}                   $remote_post_id The original post ID.
				 * @param {array}                 $post_array     The arguments passed into wp_insert_post.
				 * @param {NetworkSiteConnection} $this           The Distributor connection being pulled from.
				 *
				 * @return {bool} If Distributor should set the post media.
				 */
				if ( apply_filters( 'dt_pull_post_media', true, $new_post_id, $post['media'], $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_media( $new_post_id, $post['media'], [ 'use_filesystem' => true ] );
				};

				/**
				 * Allow bypassing of all terms processing.
				 *
				 * @hook dt_pull_post_terms
				 *
				 * @param {bool}                  true            If Distributor should set the post terms.
				 * @param {int}                   $new_post_id    The newly created post ID.
				 * @param {array}                 $post_terms     List of terms items attached to the post, formatted by {@link \Distributor\Utils\prepare_taxonomy_terms()}.
				 * @param {int}                   $remote_post_id The original post ID.
				 * @param {array}                 $post_array     The arguments passed into wp_insert_post.
				 * @param {NetworkSiteConnection} $this           The Distributor connection being pulled from.
				 *
				 * @return {bool} If Distributor should set the post terms.
				 */
				if ( apply_filters( 'dt_pull_post_terms', true, $new_post_id, $post['terms'], $item_array['remote_post_id'], $post_array, $this ) ) {
					\Distributor\Utils\set_taxonomy_terms( $new_post_id, $post['terms'] );
				}

				/**
				 * Allow bypassing of all meta processing.
				 *
				 * @hook dt_pull_post_meta
				 *
				 * @param {bool}                  true            If Distributor should set the post meta.
				 * @param {int}                   $new_post_id    The newly created post ID.
				 * @param {array}                 $post_meta      List of meta items attached to the post, formatted by {@link \Distributor\Utils\prepare_meta()}.
				 * @param {int}                   $remote_post_id The original post ID.
				 * @param {array}                 $post_array     The arguments passed into wp_insert_post.
				 * @param {NetworkSiteConnection} $this           The Distributor connection being pulled from.
				 *
				 * @return {bool} If Distributor should set the post meta.
				 */
				if ( apply_filters( 'dt_pull_post_meta', true, $new_post_id, $post['meta'], $item_array['remote_post_id'], $post_array, $this ) ) {
					$post_meta = $this->exclude_additional_meta_data( $post['meta'] );
					\Distributor\Utils\set_meta( $new_post_id, $post_meta );
				}
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
			 * Allow the sync'ed post to be updated via a REST request get the rendered content.
			 *
			 * @hook dt_pull_post_apply_rendered_content
			 *
			 * @param {bool}        false          Apply rendered content after a pull? Defaults to false.
			 * @param {int}         $new_post_id   The new post ID.
			 * @param {Connection}  $this          The Distributor connection pulling the post.
			 * @param {array}       $post_array    The post array used to create the new post.
			 *
			 * @return {bool} Whether to apply rendered content after a pull.
			 */
			if ( apply_filters( 'dt_pull_post_apply_rendered_content', false, $new_post_id, $this, $post_array ) ) {
				$this->update_content_via_rest( $new_post_id );
			}

			/**
			 * Action triggered when a post is pulled via distributor.
			 * Fires after a post is pulled via Distributor and after `restore_current_blog()`.
			 *
			 * @since 1.0
			 * @hook dt_pull_post
			 *
			 * @param {int}         $new_post_id   The new post ID that was pulled.
			 * @param {Connection}  $this          The Distributor connection pulling the post.
			 * @param {array}       $post_array    The original post data retrieved via the connection.
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
	 * @param array   $item_id_mappings Mapping to log; key = origin post ID, value = new post ID.
	 * @param int     $blog_id Blog ID
	 * @param boolean $overwrite Whether to overwrite the sync log for this site. Default false.
	 * @since 0.8
	 */
	public function log_sync( array $item_id_mappings, $blog_id = 0, $overwrite = false ) {
		$blog_id          = 0 === $blog_id ? $this->site->blog_id : $blog_id;
		$current_site_log = [];

		if ( false === $overwrite ) {
			$current_site_log = $this->get_sync_log( $blog_id );
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

		// Action documented in includes/classes/ExternalConnection.php.
		do_action( 'dt_log_sync', $item_id_mappings, $sync_log, $this );
	}

	/**
	 * Return the sync log for a specific site
	 *
	 * @param int $blog_id Blog ID
	 * @return array
	 */
	public function get_sync_log( $blog_id = 0 ) {
		$blog_id = 0 === $blog_id ? $this->site->blog_id : $blog_id;

		$sync_log = get_option( 'dt_sync_log', [] );

		$current_site_log = [];
		if ( ! empty( $sync_log[ $blog_id ] ) ) {
			$current_site_log = $sync_log[ $blog_id ];
		}

		return $current_site_log;
	}

	/**
	 * Get the available post types.
	 *
	 * @since 1.3
	 * @return array
	 */
	public function get_post_types() {
		switch_to_blog( $this->site->blog_id );
		$post_types = Utils\distributable_post_types( 'objects' );
		restore_current_blog();

		return $post_types;
	}

	/**
	 * Remotely get posts so we can list them for pulling
	 *
	 * @since  0.8
	 * @since 2.0.0 Added $new_post_args parameter.
	 *
	 * @param  array $args Array of args for getting.
	 * @param  array $new_post_args {
	 *    Array of args for creating new post.
	 *
	 *    @type string $post_status Post status for new post.
	 * }
	 * @return array|WP_Post|bool
	 */
	public function remote_get( $args = array(), $new_post_args = array() ) {

		$id = ( empty( $args['id'] ) ) ? false : $args['id'];

		switch_to_blog( $this->site->blog_id );

		$query_args = array();

		if ( empty( $id ) ) {

			if ( isset( $args['post__in'] ) ) {
				if ( empty( $args['post__in'] ) ) {

					// If post__in is empty, we can just stop right here
					restore_current_blog();

					// Filter documented in includes/classes/ExternalConnections/WordPressExternalConnection.php
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
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$query_args['meta_query'] = $args['meta_query'];
			}

			if ( isset( $args['s'] ) ) {
				$query_args['s'] = urldecode( $args['s'] );
			}

			if ( ! empty( $args['orderby'] ) ) {
				$query_args['orderby'] = $args['orderby'];
			}

			if ( ! empty( $args['order'] ) ) {
				$query_args['order'] = $args['order'];
			}

			// Filter documented in includes/classes/ExternalConnections/WordPressExternalConnection.php
			$posts_query = new \WP_Query( apply_filters( 'dt_remote_get_query_args', $query_args, $args, $this ) );

			$posts = $posts_query->posts;

			$formatted_posts = [];

			foreach ( $posts as $post ) {
				$formatted_posts[] = Utils\prepare_post( $post );
			}

			restore_current_blog();

			// Filter documented in /includes/classes/ExternalConnections/WordPressExternalConnection.php.
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
				$dt_post        = new DistributorPost( $post );
				$formatted_post = $dt_post->to_insert( $new_post_args );
				// The pull method requires the connection map despite it being on the deny list.
				$formatted_post['meta']['dt_connection_map'] = get_post_meta( $id, 'dt_connection_map', true );
			}

			restore_current_blog();

			// Filter documented in /includes/classes/ExternalConnections/WordPressExternalConnection.php.
			return apply_filters( 'dt_remote_get', $formatted_post, $args, $this );
		}
	}

	/**
	 * Setup actions and filters that are need on every page load
	 *
	 * @since 0.8
	 */
	public static function bootstrap() {
		add_action( 'wp_after_insert_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'update_syndicated' ), 99 );
		add_action( 'before_delete_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'separate_syndicated_on_delete' ) );
		add_action( 'before_delete_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'remove_distributor_post_from_original' ) );
		add_action( 'wp_trash_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'separate_syndicated_on_delete' ) );
		add_action( 'untrash_post', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'connect_syndicated_on_untrash' ) );
		add_action( 'clean_site_cache', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'set_sites_last_changed_time' ) );
		add_action( 'wp_insert_site', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'set_sites_last_changed_time' ) );
		add_action( 'add_user_to_blog', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'clear_authorized_sites_cache' ) );
		add_action( 'remove_user_from_blog', array( '\Distributor\InternalConnections\NetworkSiteConnection', 'clear_authorized_sites_cache' ) );
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
			$site = get_site( $blog_id );
			if ( ! $site || ! is_a( $site, '\WP_Site' ) ) {
				continue;
			}

			$connection = new self( $site );

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
	 * @param  int|WP_Post $post Post ID or WP_Post
	 * depending on which action the method is hooked to.
	 */
	public static function update_syndicated( $post ) {
		$post    = get_post( $post );
		$post_id = $post->ID;

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
	 * On wp_insert_post, modified date is overridden by post date
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
	 * Find out which sites user can create post type on
	 *
	 * @since  0.8
	 * @since  1.3.7 Added the `$context` parameter.
	 *
	 * @param string $context The context of the authorization.
	 *
	 * @return array
	 */
	public static function get_available_authorized_sites( $context = null ) {
		if ( ! is_multisite() ) {
			return array();
		}

		/**
		 * Enable plugins to filter the authorized sites, before they are retrieved.
		 *
		 * @since 1.2
		 * @since 1.3.7 Added the `$context` parameter.
		 * @hook dt_pre_get_authorized_sites
		 *
		 * @see \Distributor\InternalConnections\NetworkSiteConnection::get_available_authorized_sites()
		 *
		 * @param {array}  $authorized_sites Array of `WP_Site` object and post type objects the user can edit.
		 * @param {string} $context          The context of the authorization.
		 *
		 * @return {array} Array of `WP_Site` object and post type objects.
		 */
		$authorized_sites = apply_filters( 'dt_pre_get_authorized_sites', array(), $context );
		if ( ! empty( $authorized_sites ) ) {
			return $authorized_sites;
		}

		$authorized_sites = self::build_available_authorized_sites( get_current_user_id(), $context );

		/**
		 * Filter the array of authorized sites.
		 *
		 * @since 1.2
		 * @since 1.3.7 Added the `$context` parameter.
		 * @hook dt_authorized_sites
		 * @tutorial snippets
		 *
		 * @param {array}  $authorized_sites An array of `WP_Site` objects and the post type objects the user can edit.
		 * @param {string} $context          The context of the authorization.
		 *
		 * @return {array} An array of `WP_Site` objects and the post type objects.
		 */
		return apply_filters( 'dt_authorized_sites', $authorized_sites, $context );
	}

	/**
	 * Build the available sites a specific user is authorized to use.
	 *
	 * @param int|bool $user_id Current user ID
	 * @param string   $context The context of the authorization. Either push or pull
	 * @param bool     $force   Force a cache clear. Default false
	 *
	 * @return array
	 */
	public static function build_available_authorized_sites( $user_id = false, $context = null, $force = false ) {
		$user_id      = ! $user_id ? get_current_user_id() : $user_id;
		$last_changed = get_site_option( 'last_changed_sites' );

		if ( ! $last_changed ) {
			$last_changed = self::set_sites_last_changed_time();
		}

		$cache_key        = "authorized_sites:$user_id:$context:$last_changed";
		$authorized_sites = get_transient( $cache_key );

		if ( $force || false === $authorized_sites ) {
			$authorized_sites = array();
			$sites            = get_sites(
				array(
					'number' => 1000,
				)
			);
			$current_blog_id  = (int) get_current_blog_id();

			foreach ( $sites as $site ) {
				$blog_id = (int) $site->blog_id;

				if ( $blog_id === $current_blog_id ) {
					continue;
				}

				$base_url = get_site_url( $blog_id );

				if ( empty( $base_url ) ) {
					continue;
				}

				switch_to_blog( $blog_id );

				$post_types            = get_post_types();
				$authorized_post_types = array();

				foreach ( $post_types as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					if ( current_user_can( $post_type_object->cap->create_posts ) ) {
						$authorized_post_types[] = $post_type;
					}
				}

				if ( ! empty( $authorized_post_types ) ) {
					$authorized_sites[] = array(
						'site'       => $site,
						'post_types' => $authorized_post_types,
					);
				}

				restore_current_blog();
			}
		}

		// Make sure we save and return an array.
		$authorized_sites = ! is_array( $authorized_sites ) ? array() : $authorized_sites;

		set_transient( $cache_key, $authorized_sites, 15 * MINUTE_IN_SECONDS );

		return $authorized_sites;
	}

	/**
	 * Whenever site data changes, save the timestamp.
	 *
	 * WordPress stores this same information in the cache
	 * {@see clean_blog_cache()}, but not all environments
	 * will have caching enabled, so we also store it
	 * in a site option.
	 *
	 * @return string
	 */
	public static function set_sites_last_changed_time() {
		$time = microtime();
		update_site_option( 'last_changed_sites', $time );

		return $time;
	}

	/**
	 * Clear the authorized sites cache for a specific user.
	 *
	 * @param int $user_id Current user ID.
	 */
	public static function clear_authorized_sites_cache( $user_id = false ) {
		$last_changed = get_site_option( 'last_changed_sites' );

		if ( ! $last_changed ) {
			self::set_sites_last_changed_time();
		} else {
			delete_transient( "authorized_sites:$user_id:push:$last_changed" );
			delete_transient( "authorized_sites:$user_id:pull:$last_changed" );
		}
	}

	/**
	 * Setup canonicalization on front end
	 *
	 * @since  0.8
	 * @deprecated 2.0.0
	 */
	public static function canonicalize_front_end() {
		_deprecated_function( __METHOD__, '2.0.0' );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\filter_author_link instead.
	 *
	 * @param  string $link Author link.
	 * @param  int    $author_id Author ID.
	 * @param  string $author_nicename Author name.
	 * @return string
	 */
	public static function author_posts_url_distributed( $link, $author_id, $author_nicename ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\filter_author_link' );
		return \Distributor\Hooks\filter_author_link( $link );
	}

	/**
	 * Override author with site name on distributed post
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\filter_the_author instead.
	 *
	 * @param  string $author Author name.
	 * @return string
	 */
	public static function the_author_distributed( $author ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\filter_the_author' );
		return \Distributor\Hooks\filter_the_author( $author );
	}

	/**
	 * Make sure canonical url header is outputted
	 *
	 * @since  0.8
	 * @deprecated 2.0.0 Use Distributor\Hooks\get_canonical_url instead.
	 *
	 * @param  string $canonical_url Canonical URL.
	 * @param  object $post Post object.
	 * @return string
	 */
	public static function canonical_url( $canonical_url, $post ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\get_canonical_url' );
		return \Distributor\Hooks\get_canonical_url( $canonical_url, $post );
	}

	/**
	 * Handles the canonical URL change for distributed content when Yoast SEO is in use
	 *
	 * @since  1.0
	 * @deprecated 2.0.0 Use Distributor\Hooks\wpseo_canonical instead.
	 *
	 * @param string $canonical_url The Yoast WPSEO deduced canonical URL
	 * @return string $canonical_url The updated distributor friendly URL
	 */
	public static function wpseo_canonical_url( $canonical_url ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\wpseo_canonical' );
		$presentation = false;
		if ( is_singular() ) {
			$source       = get_post();
			$presentation = (object) array( 'source' => $source );
		}
		return \Distributor\Hooks\wpseo_canonical( $canonical_url, $presentation );
	}

	/**
	 * Handles the og:url change for distributed content when Yoast SEO is in use
	 *
	 * @deprecated 2.0.0 Use Distributor\Hooks\wpseo_opengraph_url instead.
	 *
	 * @param string $og_url The Yoast WPSEO deduced OG URL which is a result of wpseo_canonical_url
	 * @return string $og_url The updated distributor friendly URL
	 */
	public static function wpseo_opengraph_url( $og_url ) {
		_deprecated_function( __METHOD__, '2.0.0', 'Distributor\Hooks\wpseo_opengraph_url' );
		$presentation = false;
		if ( is_singular() ) {
			$source       = get_post();
			$presentation = (object) array( 'source' => $source );
		}
		return \Distributor\Hooks\wpseo_opengraph_url( $og_url, $presentation );
	}

	/**
	 * Updates a post content via a REST request after the new post is created
	 * in order to get the rendered content.
	 *
	 * @param int $new_post_id The new post ID that was pulled.
	 * @return void
	 */
	public function update_content_via_rest( $new_post_id ) {

		$post = get_post( $new_post_id );
		if ( ! is_a( $post, '\WP_Post' ) ) {
			return;
		}

		$original_blog_id = absint( get_post_meta( $post->ID, 'dt_original_blog_id', true ) );
		$original_post_id = absint( get_post_meta( $post->ID, 'dt_original_post_id', true ) );

		$rest_url = false;
		if ( ! empty( $original_blog_id ) && ! empty( $original_post_id ) ) {
			$rest_url = Utils\get_rest_url( $original_blog_id, $original_post_id );
		}

		if ( empty( $rest_url ) ) {
			return;
		}

		/**
		 * Allow filtering of the HTTP request args before updating content
		 * via a REST API call.
		 *
		 * @hook dt_update_content_via_request_args
		 *
		 * @param {array}                 list            List of request args.
		 * @param {int}                   $new_post_id    The new post ID.
		 * @param {NetworkSiteConnection} $this           The distributor connection being pulled from.
		 *
		 * @return {array} List of filtered request args.
		 */
		$request = apply_filters( 'dt_update_content_via_request_args', [], $new_post_id, $this );

		$response = Utils\remote_http_request( $rest_url, $request );

		$body = false;
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			$body = wp_remote_retrieve_body( $response );
		}

		if ( empty( $body ) ) {
			return;
		}

		$data = json_decode( $body );

		// Grab the rendered response and update the current post.
		if ( is_a( $data, '\stdClass' ) && isset( $data->content, $data->content->rendered ) ) {

			wp_update_post(
				[
					'ID'           => $post->ID,
					'post_content' => $data->content->rendered,
				]
			);
		}
	}

	/**
	 * Exclude additional meta data for network distributions
	 *
	 * In network connections the featured image is set prior to the meta data.
	 * Excluding the `_thumbnail_id` meta from distribution prevents the meta
	 * data from referring to the attachment ID of the original site.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $post_meta Array of meta to include in the distribution.
	 * @return string[] Array of meta to include in the distribution after filtering out excluded meta.
	 */
	public static function exclude_additional_meta_data( $post_meta ) {
		unset( $post_meta['_thumbnail_id'] );
		return $post_meta;
	}
}
