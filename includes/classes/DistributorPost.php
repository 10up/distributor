<?php
/**
 * Distributor post object abstraction.
 *
 * @package  distributor
 */

namespace Distributor;

use Distributor\Utils;
use WP_Post;

/**
 * This is the post abstraction for distributor posts.
 *
 * Although this class is within the Distributor namespace, the class itself
 * includes the phrase Distributor to make it clear to developers `use`ing
 * the class that they are not using the `WP_Post` object.
 *
 * @since x.x.x
 */
class DistributorPost {
	/**
	 * The WordPress post object.
	 *
	 * @var WP_Post
	 */
	public $post = false;

	/**
	 * Whether this is the source (true) or a distributed post (false).
	 *
	 * @var bool
	 */
	public $is_source = true;

	/**
	 * Whether this post is linked to the original version.
	 *
	 * For the original post this is set to true.
	 *
	 * @var bool
	 */
	public $is_linked = true;

	/**
	 * The original post ID.
	 *
	 * @var int
	 */
	public $original_post_id = 0;

	/**
	 * The original post's URL.
	 *
	 * This is marked private but can be accessed via the `__get` method to allow
	 * for live updates of the original post URL for internal connections.
	 *
	 * @var string
	 */
	private $original_post_url = '';

	/**
	 * Whether the original post has been deleted.
	 *
	 * @var bool
	 */
	public $original_deleted = false;

	/**
	 * The direction of the connection.
	 *
	 * Internal connections are identical regardless of whether they are pushed or pulled
	 * so are considered bidirectional.
	 *
	 * @var string pushed|pulled|bidirectional|empty
	 */
	public $connection_direction = '';

	/**
	 * The type of connection this post is distributed from.
	 *
	 * @var string internal|external|empty (for source)
	 */
	public $connection_type = '';

	/**
	 * The connection ID this post is distributed from.
	 *
	 * For internal connections this is the site ID. For external connections
	 * this refers to the connection ID.
	 *
	 * @var int|string
	 */
	public $connection_id = 0;

	/**
	 * The site ID of this post.
	 *
	 * @var int
	 */
	public $site_id = 0;

	/**
	 * The source site data for internal connections.
	 *
	 * This is an array of site data for the source site. This is set by
	 * the populate_source_site() method upon access to avoid switching
	 * sites unnecessarily.
	 *
	 * @var array {
	 *    @type string $home_url The site's home page.
	 *    @type string $site_url The site's WordPress address.
	 *    @type string $rest_url The site's REST API address.
	 *    @type string $name     The site name.
	 * }
	 */
	private $source_site = [];

	/**
	 * Initialize the DistributorPost object.
	 *
	 * @param WP_Post|int $post WordPress post object or post ID.
	 */
	public function __construct( $post ) {
		$post = get_post( $post );

		if ( ! $post ) {
			return;
		}

		$this->post    = $post;
		$this->site_id = get_current_blog_id();

		/*
		 * The original post ID is listed as excluded post meta and therefore
		 * unavailable in the meta property. We need to get it using the post
		 * meta API.
		 */
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		if ( empty( $original_post_id ) ) {
			// This is the source post.
			$this->is_source         = true;
			$this->is_linked         = true;
			$this->original_post_id  = $this->post->ID;
			$this->original_post_url = get_permalink( $this->post->ID );
			$this->original_deleted  = false;
			return;
		}

		// Set up information for a distributed post.
		$this->is_source = false;
		// Reverse value of the `dt_unlinked` meta data.
		$this->is_linked         = ! get_post_meta( $post->ID, 'dt_unlinked', true );
		$this->original_post_id  = (int) $original_post_id;
		$this->original_post_url = get_post_meta( $post->ID, 'dt_original_post_url', true );
		$this->original_deleted  = (bool) get_post_meta( $post->ID, 'dt_original_deleted', true );

		// Determine the connection type.
		if ( get_post_meta( $post->ID, 'dt_original_blog_id', true ) ) {
			/*
			 * Internal connections store the original blog's ID.
			 *
			 * Pushed and pulled posts are indistinguishable from each other.
			 */
			$this->connection_type      = 'internal';
			$this->connection_direction = 'bidirectional';
			$this->connection_id        = (int) get_post_meta( $post->ID, 'dt_original_blog_id', true );
		} elseif ( get_post_meta( $post->ID, 'dt_full_connection', true ) ) {
			// This connection was pushed from an external connection.
			$this->connection_type      = 'external';
			$this->connection_direction = 'pushed';

			/*
			 * The connection ID stored in post meta is incorrect.
			 *
			 * The stored connection is the ID of this connection on the source server.
			 * Instead this lists the remote site's URL as the connection ID.
			 */
			$this->connection_id = get_post_meta( $post->ID, 'dt_original_site_url', true );
		} elseif ( get_post_meta( $post->ID, 'dt_original_source_id', true ) ) {
			// Post was pulled from an external connection.
			$this->connection_type      = 'external';
			$this->connection_direction = 'pulled';
			$this->connection_id        = (int) get_post_meta( $post->ID, 'dt_original_source_id', true );
		}
	}

	/**
	 * Populate the source site data for internal connections.
	 *
	 * This populates data from the source site used by internal connections.
	 * The data is populated in one function call to avoid unnecessary calls to
	 * switch_to_blog() and restore_current_blog().
	 *
	 * @todo Consider populating if the `switch_blog` action fires before site data is accessed.
	 *
	 * @return void
	 */
	protected function populate_source_site() {
		if ( ! empty( $this->source_site ) ) {
			// Already populated.
			return;
		}

		if ( $this->is_source ) {
			// This is the source post.
			$this->source_site = [
				'home_url' => home_url(),
				'name'     => get_bloginfo( 'name' ),
			];
			return;
		} elseif ( 'internal' !== $this->connection_type || empty( $this->connection_id ) ) {
			// Populate from the meta data.
			$this->source_site = [
				'home_url' => get_post_meta( $this->post->ID, 'dt_original_site_url', true ),
				'name'     => get_post_meta( $this->post->ID, 'dt_original_site_name', true ),
			];
			return;
		}

		$switch_to_site = false;
		if ( get_current_blog_id() !== $this->connection_id ) {
			switch_to_blog( $this->connection_id );
			$switch_to_site = true;
		}

		// Get the site data.
		$this->source_site = [
			'home_url' => home_url(),
			'name'     => get_bloginfo( 'name' ),
		];

		// Update the original post permalink with live data.
		$this->original_post_url = get_permalink( $this->original_post_id );

		// Restore the current site.
		if ( $switch_to_site ) {
			restore_current_blog();
		}
	}

	/**
	 * Magic getter method.
	 *
	 * This method is used to get the value of the `source_site` property and
	 * populate it if needs be. For internal connections the post permalink is
	 * updated with live data.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( in_array( $name, array( 'source_site', 'original_post_url' ), true ) ) {
			$this->populate_source_site();
		}

		return $this->$name;
	}

	/**
	 * Magic isset method.
	 *
	 * This method is used to check if the `source_site` property is set and
	 * populate it if needs be.
	 *
	 * @param string $name Property name.
	 * @return bool
	 */
	public function __isset( $name ) {
		if ( 'source_site' === $name && empty( $this->source_site ) ) {
			$this->populate_source_site();
			return ! empty( $this->source_site );
		}

		return isset( $this->$name );
	}

	/**
	 * Determines whether the post has blocks.
	 *
	 * This test optimizes for performance rather than strict accuracy, detecting
	 * the pattern of a block but not validating its structure. For strict accuracy,
	 * you should use the block parser on post content.
	 *
	 * Wraps the WordPress function of the same name.
	 *
	 * @return bool Whether the post has blocks.
	 */
	public function has_blocks() {
		return has_blocks( $this->post->post_content );
	}

	/**
	 * Determines whether a $post or a string contains a specific block type.
	 *
	 * This test optimizes for performance rather than strict accuracy, detecting
	 * whether the block type exists but not validating its structure and not checking
	 * reusable blocks. For strict accuracy, you should use the block parser on post content.
	 *
	 * Wraps the WordPress function of the same name.
	 *
	 * @param string $block_name Full block type to look for.
	 * @return bool Whether the post content contains the specified block.
	 */
	public function has_block( $block_name ) {
		return has_block( $block_name, $this->post->post_content );
	}

	/**
	 * Get the post ID.
	 *
	 * @return int Post ID.
	 */
	public function get_the_id() {
		return $this->post->ID;
	}

	/**
	 * Get the post permalink.
	 *
	 * @return string Post permalink.
	 */
	public function get_permalink() {
		return get_permalink( $this->post );
	}

	/**
	 * Get the post type.
	 *
	 * @return string Post type.
	 */
	public function get_post_type() {
		return get_post_type( $this->post );
	}

	/**
	 * Get the post's thumbnail ID.
	 *
	 * @return int|false Post thumbnail ID or false if no thumbnail is set.
	 */
	public function get_post_thumbnail_id() {
		return get_post_thumbnail_id( $this->post );
	}

	/**
	 * Get the post's thumbnail URL.
	 *
	 * @param string $size Thumbnail size. Defaults to 'post-thumbnail'.
	 * @return string|false The post's thumbnail URL or false if no thumbnail is set.
	 */
	public function get_post_thumbnail_url( $size = 'post-thumbnail' ) {
		return get_the_post_thumbnail_url( $this->post, $size );
	}

	/**
	 * Get the post's thumbnail HTML.
	 *
	 * @param string $size Thumbnail size. Defaults to 'post-thumbnail'.
	 * @param array  $attr Optional. Attributes for the image markup. Default empty.
	 * @return string|false The post's thumbnail HTML or false if no thumbnail is set.
	 */
	public function get_the_post_thumbnail( $size = 'post-thumbnail', $attr = '' ) {
		return get_the_post_thumbnail( $this->post, $size, $attr );
	}

	/**
	 * Get the post's canonical URL.
	 *
	 * For distributed posts, this is the permalink of the original post. For
	 * the original post, this is the result of get_permalink().
	 *
	 * @param  string $canonical_url The post's canonical URL. If specified, this will be returned
	 *                               if the canonical URL does not need to be replaced by the
	 *                               original source URL.
	 * @return string The post's canonical URL.
	 */
	public function get_canonical_url( $canonical_url = '' ) {
		if (
			$this->is_source
			|| $this->original_deleted
			|| ! $this->is_linked
			|| ! $this->connection_id
			|| ! $this->original_post_url
		) {
			if ( empty( $canonical_url ) ) {
				return $this->get_permalink();
			}
			return $canonical_url;
		}

		return $this->original_post_url;
	}

	/**
	 * Get the post's author name.
	 *
	 * For distributed posts this is the name of the original site. For the
	 * original post, this is the result of get_the_author().
	 *
	 * @param  string $author_name The post's author name. If specified, this will be returned if the
	 *                             author name does not need to be replaced by the original source name.
	 * @return string The post's author name.
	 */
	public function get_author_name( $author_name = '' ) {
		$settings = Utils\get_settings();

		if (
			empty( $settings['override_author_byline'] )
			|| $this->is_source
			|| $this->original_deleted
			|| ! $this->is_linked
			|| ! $this->connection_id
			|| ! $this->original_post_url
		) {
			if ( empty( $author_name ) ) {
				return get_the_author_meta( 'display_name', $this->post->post_author );
			}
			return $author_name;
		}

		$this->populate_source_site();
		return $this->source_site['name'];
	}

	/**
	 * Get the post's author URL.
	 *
	 * For distributed posts this is a link to the original site. For the
	 * original post, this is the result of get_author_posts_url().
	 *
	 * @param  string $author_link The author's posts URL. If specified, this will be returned if the
	 *                             author link does not need to be replaced by the original source name.
	 * @return string The post's author link.
	 */
	public function get_author_link( $author_link = '' ) {
		$settings = Utils\get_settings();

		if (
			empty( $settings['override_author_byline'] )
			|| $this->is_source
			|| $this->original_deleted
			|| ! $this->is_linked
			|| ! $this->connection_id
			|| ! $this->original_post_url
		) {
			if ( empty( $author_link ) ) {
				return get_author_posts_url( $this->post->post_author );
			}
			return $author_link;
		}

		$this->populate_source_site();
		return $this->source_site['home_url'];
	}

	/**
	 * Get the post's distributable meta data.
	 *
	 * @return array Array of meta data.
	 */
	public function get_meta() {
		return Utils\prepare_meta( $this->post->ID );
	}

	/**
	 * Get the post's distributable terms.
	 *
	 * @var array[] {
	 *    @type WP_Term[] Post terms keyed by taxonomy.
	 * }
	 */
	public function get_terms() {
		return Utils\prepare_taxonomy_terms( $this->post->ID );
	}

	/**
	 * Format media items for consumption
	 *
	 * @return array
	 */
	public function get_media() {
		$post_id = $this->post->ID;
		if ( $this->has_blocks() ) {
			$raw_media = $this->parse_media_blocks();
		} else {
			$raw_media = get_attached_media( get_allowed_mime_types(), $post_id );
		}

		$featured_image_id = $this->get_post_thumbnail_id();
		$found_featured    = false;
		$media_array       = array();

		foreach ( $raw_media as $media_post ) {
			$media_item = Utils\format_media_post( $media_post );

			if ( $media_item['featured'] ) {
				$found_featured = true;
			}

			$media_array[] = $media_item;
		}

		if ( ! empty( $featured_image_id ) && ! $found_featured ) {
			$featured_image             = Utils\format_media_post( get_post( $featured_image_id ) );
			$featured_image['featured'] = true;

			$media_array[] = $featured_image;
		}

		return $media_array;
	}

	/**
	 * Parse the post's content to obtain media items.
	 *
	 * This uses the block parser to find media items within the post content
	 * regardless of whether the media is attached to the post or not.
	 *
	 * @return WP_Post[] Array of media posts.
	 */
	public function parse_media_blocks() {
		$found = false;

		// Note: changes to the cache key or group should be reflected in `includes/settings.php`
		$media = wp_cache_get( 'dt_media::{$post_id}', 'dt::post', false, $found );

		if ( ! $found ) {
			// Parse blocks to determine attached media.
			$media = array();

			$blocks = parse_blocks( $this->post->post_content );

			foreach ( $blocks as $block ) {
				$media = array_merge( $media, $this->parse_blocks_for_attachment_id( $block ) );
			}

			// Only the IDs are cached to keep the cache size down.
			wp_cache_set( 'dt_media::{$post_id}', $media, 'dt::post' );
		}

		/*
		 * Prime the cache for the individual media items in full.
		 *
		 * The post, term and meta caches are primed individually to work
		 * around a WordPress bug in which `_prime_post_caches()` will not
		 * warm the secondary caches if the post object is already cached.
		 *
		 * See https://core.trac.wordpress.org/ticket/57163
		 */
		_prime_post_caches( $media, false, false );
		update_object_term_cache( $media, 'attachment' );
		update_postmeta_cache( $media );
		$media = array_map( 'get_post', $media );
		$media = array_filter( $media );

		return $media;
	}

	/**
	 * Parse blocks to obtain each media item's attachment ID.
	 *
	 * @param array $block Block to parse.
	 * @return int[] Array of media attachment IDs.
	 */
	private function parse_blocks_for_attachment_id( $block ) {
		$media_blocks = array(
			'core/image' => 'id',
			'core/audio' => 'id',
			'core/video' => 'id',
		);
		$media        = array();

		/**
		 * Filters blocks to consider as media.
		 *
		 * The array keys are the block names and the values indicate the
		 * attribute containing the media's attachment ID.
		 *
		 * The Distributor defaults are {
		 *    'core/image' => 'id',
		 *    'core/audio' => 'id',
		 *    'core/video' => 'id',
		 * }
		 *
		 * @hook dt_parse_media_blocks
		 *
		 * @param {array} $media_blocks Array of media blocks.
		 *
		 * @return {array} Modified array of media blocks.
		 */
		$media_blocks = apply_filters( 'dt_parse_media_blocks', $media_blocks );

		$block_names = array_keys( $media_blocks );

		if ( ! empty( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$media = array_merge( $media, $this->parse_blocks_for_attachment_id( $inner_block ) );
			}
		}

		if ( in_array( $block['blockName'], $block_names, true ) ) {
			$media[] = $block['attrs'][ $media_blocks[ $block['blockName'] ] ];
		}

		return $media;
	}

	/**
	 * Get the post data for distribution.
	 *
	 * @return array {
	 *    Post data.
	 *
	 *    @type string $title             Post title.
	 *    @type string $slug              Post slug.
	 *    @type string $post_type         Post type.
	 *    @type string $content           Processed post content.
	 *    @type string $excerpt           Post excerpt.
	 *    @type array  $distributor_media Media data.
	 *    @type array  $distributor_terms Post terms.
	 *    @type array  $distributor_meta  Post meta.
	 * }
	 */
	public function post_data() {
		return [
			'title'             => html_entity_decode( get_the_title( $this->post->ID ), ENT_QUOTES, get_bloginfo( 'charset' ) ),
			'slug'              => $this->post->post_name,
			'post_type'         => $this->post->post_type,
			'content'           => Utils\get_processed_content( $this->post->post_content ),
			'excerpt'           => $this->post->post_excerpt,
			'distributor_media' => $this->get_media(),
			'distributor_terms' => $this->get_terms(),
			'distributor_meta'  => $this->get_meta(),
		];
	}

	/**
	 * Get the post data in a format suitable for wp_insert_post().
	 *
	 * @todo `distributor_media` needs work for unattached media items.
	 * @todo check if `distributor_raw_content` should be included here too.
	 *
	 * @return array {
	 *    Post data.
	 *
	 *    @type string $post_title   Post title.
	 *    @type string $post_name    Post slug.
	 *    @type string $post_type    Post type.
	 *    @type string $post_content Processed post content.
	 *    @type string $post_excerpt Post excerpt.
	 *    @type array  $tax_input    Post terms.
	 *    @type array  $meta_input   Post meta.
	 *
	 *    @type array  $distributor_media Media data.
	 * }
	 */
	public function to_insert() {
		$insert       = [];
		$post_data    = $this->post_data();
		$key_mappings = [
			'post_title'        => 'title',
			'post_name'         => 'slug',
			'post_type'         => 'post_type',
			'post_content'      => 'content',
			'post_excerpt'      => 'excerpt',
			'tax_input'         => 'distributor_terms',
			'meta_input'        => 'distributor_meta',

			// This needs to be figured out.
			'distributor_media' => 'distributor_media',
		];

		foreach ( $key_mappings as $key => $value ) {
			$insert[ $key ] = $post_data[ $value ];
		}

		return $insert;
	}

	/**
	 * Get the post data in a format suitable for the distributor REST API endpoint.
	 *
	 * @param int $options Optional. Options to be passed to json_encode(). Default 0.
	 * @param int $depth   Optional. Maximum depth to walk through $data. Default 512.
	 * @return string JSON encoded post data.
	 */
	public function to_json( $options = 0, $depth = 512 ) {
		$post_data = $this->post_data();

		/*
		 * Check if the post has block to determine whether to use the raw content or not.
		 *
		 * This is used instead of `use_block_editor_for_post()` as the latter may be
		 * filtered by the classic editor plugin to return false for posts do in fact
		 * contain blocks.
		 */
		if ( $this->has_blocks() ) {
			$post_data['distributor_raw_content'] = $this->post->post_content;
		}

		return wp_json_encode( $post_data, $options, $depth );
	}
}
