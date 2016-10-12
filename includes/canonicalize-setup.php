<?php

namespace Syndicate\CanonicalizeSetup;

/**
 * Canonicalize the current post if we can
 *
 * @since  0.8
 */
add_action( 'template_redirect', function() {
	if ( is_single() ) {
		global $post;

		$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
		$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

		if ( empty( $original_post_id ) || empty( $original_blog_id ) ) {
			return;
		}

		$site = get_site( $original_blog_id );

		$connection = new \Syndicate\InternalConnections\NetworkSiteConnection( $site );
		$connection->canonicalizer_handler->setup();
	}
} );
