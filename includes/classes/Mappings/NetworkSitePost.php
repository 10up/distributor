<?php

namespace Syndicate\Mappings;
use \Syndicate\Mapping as Mapping;

/**
 * This mapping handles objects returned from the official JSON REST API for WP
 */
class NetworkSitePost extends Mapping {

	/**
	 * Convert object to WP_Post
	 * 
	 * @param  object $post
	 * @since  1.0
	 * @return WP_Post
	 */
	public function to_wp_post( $post ) {
		$obj = new \stdClass();

		$vars = get_object_vars( $post );

		foreach ( $vars as $key => $value ) {
			$obj->$key = $value;
		}

		$obj->link = get_permalink( $post->ID );

		return apply_filters( 'sy_item_mapping', new \WP_Post( $obj ), $post, $this );
	}
}