<?php

namespace Syndicate\Canonicalizers;

/**
 * Setup a syndicated network site post to be canonical
 */
class NetworkSitePost extends \Syndicate\Canonicalizer {

	/**
	 * Setup actions and filters
	 * 
	 * @since 0.8
	 */
	public function setup() {
		add_filter( 'the_title', array( $this, 'the_title' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'the_content' ), 10, 1 );
		add_filter( 'the_date', array( $this, 'the_date' ), 10, 1 );
		add_filter( 'get_the_excerpt', array( $this, 'get_the_excerpt' ), 10, 1 );
		add_filter( 'get_canonical_url', array( $this, 'canonical_url' ), 10, 2 );
	}

	/**
	 * Make sure canonical url header is outputted
	 * 
	 * @param  string $canonical_url
	 * @param  object $post
	 * @since  0.8
	 * @return string
	 */
	public function canonical_url( $canonical_url, $post ) {
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
	public function the_title( $title, $id ) {
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
	public function the_content( $content ) {
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
	public function the_date( $date ) {
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
	public function get_the_excerpt( $excerpt ) {
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
