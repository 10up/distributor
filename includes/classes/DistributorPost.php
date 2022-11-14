<?php
/**
 * Distributor post object abstraction.
 *
 * @package  distributor
 */

namespace Distributor;

use Distributor\Utils;
use WP_Post;
use WP_Term;

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
	 * Distributable post meta.
	 *
	 * An array of
	 *
	 * @var array[] {
	 *    @type mixed[] Post meta keyed by post meta key.
	 * }
	 */
	public $meta = [];

	/**
	 * Distributable post terms.
	 *
	 * @var array[] {
	 *    @type WP_Term[] Post terms keyed by taxonomy.
	 * }
	 */
	public $terms = [];

	/**
	 * Distributable post media.
	 *
	 * @var array[] Array of media objects.
	 */
	public $media = [];

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
	 * The type of connection this post is distributed from.
	 *
	 * @var string internal|external|pushed|empty (for source)
	 */
	public $connection_type = '';

	/**
	 * The connection ID this post is distributed from.
	 *
	 * For internal connections this is the site ID. For external connections
	 * this refers to the connection ID.
	 *
	 * @var int
	 */
	public $connection_id = 0;

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

		$this->post = $post;

		// Set up the distributable data.
		$this->meta  = Utils\prepare_meta( $post->ID );
		$this->terms = Utils\prepare_taxonomy_terms( $post->ID );
		$this->media = Utils\prepare_media( $post->ID );

		/*
		 * The original post ID is listed as excluded post meta and therefore
		 * unavailable in the meta property. We need to get it using the post
		 * meta API.
		 */
		$original_post_id = get_post_meta( $post->ID, 'dt_original_post_id', true );
		if ( empty( $original_post_id ) ) {
			// This is the source post.
			$this->is_source        = true;
			$this->is_linked        = true;
			$this->original_post_id = $this->post->ID;
			return;
		}

		// Set up information for a distributed post.
		$this->is_source = false;
		// Reverse value of the `dt_unlinked` meta data.
		$this->is_linked        = ! get_post_meta( $post->ID, 'dt_unlinked', true );
		$this->original_post_id = $original_post_id;

		// Determine the connection type.
		if ( get_post_meta( $post->ID, 'dt_original_blog_id', true ) ) {
			/*
			 * Internal connections store the original blog's ID.
			 *
			 * Pushed and pulled posts are indistinguishable from each other.
			 */
			$this->connection_type = 'internal';
			$this->connection_id   = get_post_meta( $post->ID, 'dt_original_blog_id', true );
		} elseif ( get_post_meta( $post->ID, 'dt_full_connection', true ) ) {
			// This connection was pushed from an external connection.
			$this->connection_type = 'pushed';

			/*
			 * The connection ID stored in post meta is incorrect.
			 *
			 * The stored connection is the ID of this connection on the source server.
			 * Instead this lists the remote site's URL as the connection ID.
			 */
			$this->connection_id = get_post_meta( $post->ID, 'dt_original_site_url', true );
		} elseif ( get_post_meta( $post->ID, 'dt_original_source_id', true ) ) {
			// Post was pulled from an external connection.
			$this->connection_type = 'external';
			$this->connection_id   = get_post_meta( $post->ID, 'dt_original_source_id', true );
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
		if ( 'internal' !== $this->connection_type || empty( $this->connection_id ) ) {
			return;
		}

		$switch_to_site = false;
		if ( get_current_blog_id() !== $this->connection_id ) {
			switch_to_blog( $this->connection_id );
			$switch_to_site = true;
		}

		// Get the site data.
		$this->source_site = [
			'home_url'  => home_url(),
			'site_url'  => site_url(),
			'rest_url'  => get_rest_url(),
			'name'      => get_bloginfo( 'name' ),
		];

		// Restore the current site.
		if ( $switch_to_site ) {
			restore_current_blog();
		}
	}

	/**
	 * Magic getter method.
	 *
	 * This method is used to get the value of the `source_site` property and
	 * populate it if needs be.
	 *
	 * @param string $name Property name.
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( 'source_site' === $name && empty( $this->source_site ) ) {
			$this->populate_source_site();
			return $this->source_site;
		}

		return $this->$name;
	}
}
