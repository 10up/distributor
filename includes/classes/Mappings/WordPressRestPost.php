<?php

namespace Syndicate\Mappings;
use \Syndicate\Mapping as Mapping;

/**
 * This mapping handles objects returned from the official JSON REST API for WP
 */
class WordPressRestPost extends Mapping {

	/**
	 * Convert object to WP_Post
	 * 
	 * @param  array|object $post_array
	 * @since  1.0
	 * @return WP_Post
	 */
	public function to_wp_post( $post ) {
		$obj = new \stdClass();

		$obj->ID = $post['id'];
		$obj->post_title = $post['title']['rendered'];
		$obj->post_content = $post['content']['rendered'];
		$obj->post_date = $post['date'];
		$obj->post_date_gmt = $post['date_gmt'];
		$obj->guid = $post['guid']['rendered'];
		$obj->post_modified = $post['modified'];
		$obj->post_modified_gmt = $post['modified_gmt'];
		$obj->post_type = $post['type'];
		$obj->link = $post['link'];
		$obj->post_author = get_current_user_id();
		
		return apply_filters( 'sy_item_mapping', new \WP_Post( $obj ), $post, $this );
	}
}