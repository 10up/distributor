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
	}
}
