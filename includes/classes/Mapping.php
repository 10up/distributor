<?php

namespace Syndicate;

/**
 * Mapping types extend this base abstract class. Mapping types are used to convert remote 
 * content objects to WP_Posts.
 */
abstract class Mapping {

	/**
	 * Convert object to WP_Post
	 * 
	 * @param  array|object $post
	 * @since  1.0
	 * @return WP_Post
	 */
	public abstract function to_wp_post( $post );
}