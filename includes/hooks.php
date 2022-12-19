<?php
/**
 * Default actions and filters.
 *
 * @package distributor
 */

namespace Distributor\Hooks;

use Distributor\DistributorPost;
use WP_Post;

/**
 * Setup actions and filters
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action( 'get_canonical_url', $n( 'get_canonical_url' ), 10, 2 );
	add_action( 'wpseo_canonical', $n( 'wpseo_canonical' ), 10, 2 );
	add_filter( 'wpseo_opengraph_url', $n( 'wpseo_opengraph_url' ), 10, 2 );
}

/**
 * Filter the canonical URL for a distributed post.
 *
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
