<?php
/**
 * Default actions and filters.
 *
 * @package distributor
 */

namespace Distributor\Hooks;

use Distributor\DistributorPost;
use Distributor\Utils;
use WP_Post;

/**
 * Setup actions and filters
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_filter( 'get_canonical_url', $n( 'get_canonical_url' ), 10, 2 );
	add_filter( 'wpseo_canonical', $n( 'wpseo_canonical' ), 10, 2 );
	add_filter( 'wpseo_opengraph_url', $n( 'wpseo_opengraph_url' ), 10, 2 );
	add_filter( 'the_author', $n( 'filter_the_author' ) );
	add_filter( 'get_the_author_display_name', $n( 'get_the_author_display_name' ), 10, 3 );
	add_filter( 'author_link', $n( 'filter_author_link' ) );
	add_filter( 'get_the_author_user_url', $n( 'get_the_author_user_url' ), 10, 3 );

}

/**
 * Filter the canonical URL for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string  $canonical_url Canonical URL.
 * @param WP_Post $post          Post object.
 * @return string Modified canonical URL.
 */
function get_canonical_url( $canonical_url, $post ) {
	$dt_post = new DistributorPost( $post );

	return $dt_post->get_canonical_url( $canonical_url );
}

/**
 * Filter the Yoast SEO canonical URL for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string                                                  $canonical_url Canonical URL.
 * @param Yoast\WP\SEO\Presentations\Indexable_Presentation|false $presentation  Yoast SEO meta tag presenter. False: filter applied in legacy format.
 * @return string Modified canonical URL.
 */
function wpseo_canonical( $canonical_url, $presentation = false ) {
	if ( false !== $presentation ) {
		if ( ! $presentation->source instanceof WP_Post ) {
			return $canonical_url;
		}

		return get_canonical_url( $canonical_url, $presentation->source );
	}

	// Filter was called in legacy format.
	if ( ! is_singular() ) {
		return $canonical_url;
	}

	return get_canonical_url( $canonical_url, get_post() );
}

/**
 * Filter the Yoast SEO OpenGraph URL for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string                                                  $og_url        OpenGraph URL.
 * @param Yoast\WP\SEO\Presentations\Indexable_Presentation|false $presentation  Yoast SEO meta tag presenter. False: filter applied in legacy format.
 * @return string Modified OpenGraph URL.
 */
function wpseo_opengraph_url( $og_url, $presentation = false ) {
	if ( false !== $presentation ) {
		if ( ! $presentation->source instanceof WP_Post ) {
			return $og_url;
		}

		$dt_post = new DistributorPost( $presentation->source );
		return $dt_post->get_permalink();
	}

	// Filter was called in legacy format.
	if ( ! is_singular() ) {
		return $og_url;
	}

	$dt_post = new DistributorPost( get_post() );
	return $dt_post->get_permalink();
}

/**
 * Filter the author name via the_author() for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string $display_name Author display name.
 * @return string Modified author display name.
 */
function filter_the_author( $display_name ) {
	// Ensure there is a global post object.
	if ( ! get_post() ) {
		return $display_name;
	}

	$dt_post = new DistributorPost( get_post() );
	return $dt_post->get_author_name( $display_name );
}

/**
 * Filter the author display name via get_the_author() for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string $display_name     Author display name.
 * @param int    $user_id          User ID.
 * @param int    $original_user_id Original user ID for calling get_the_author(). False: get_the_author()
 *                                 was retrieve the author of the current post object.
 * @return string Modified author display name.
 */
function get_the_author_display_name( $display_name, $user_id, $original_user_id ) {
	$current_post   = get_post();
	$is_distributed = empty( $current_post ) ? false : Utils\is_distributed_post( $current_post );

	if ( ! $is_distributed && false !== $original_user_id ) {
		// get_the_author() was called for a specific user.
		return $display_name;
	}

	return filter_the_author( $display_name );
}

/**
 * Filter the author link for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string $link Author link.
 * @return string Modified author link.
 */
function filter_author_link( $link ) {
	// Ensure there is a global post object.
	if ( ! get_post() ) {
		return $link;
	}

	$dt_post = new DistributorPost( get_post() );
	return $dt_post->get_author_link( $link );
}

/**
 * Filter the author page URL via get_the_author() for a distributed post.
 *
 * @since 2.0.0
 *
 * @param string $author_url       Author page URL.
 * @param int    $user_id          User ID.
 * @param int    $original_user_id Original user ID for calling get_the_author(). False: get_the_author()
 *                                 was retrieve the author of the current post object.
 * @return string Modified author page URL.
 */
function get_the_author_user_url( $author_url, $user_id, $original_user_id ) {
	$current_post   = get_post();
	$is_distributed = empty( $current_post ) ? false : Utils\is_distributed_post( $current_post );

	if ( ! $is_distributed && false !== $original_user_id ) {
		// get_the_author() was called for a specific user.
		return $author_url;
	}

	return filter_author_link( $author_url );
}
