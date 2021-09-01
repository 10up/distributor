<?php
/**
 * These functions are intended to be used within themes and plugins
 *
 * @package  distributor
 */

/**
 * Determine if post is unlinked
 *
 * @param  int $post_id Post ID.
 * @since  1.0
 * @return bool
 */
function distributor_is_unlinked( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;

		$post_id = $post->ID;
	}

	$unlinked = get_post_meta( $post_id, 'dt_unlinked' );

	return (bool) $unlinked;
}

/**
 * Get original post link as a string.
 *
 * @param  int $post_id Leave null to use current post
 * @since  1.0
 * @return string|bool
 */
function distributor_get_original_post_link( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;

		$post_id = $post->ID;
	}

	$original_blog_id   = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_post_id   = get_post_meta( $post_id, 'dt_original_post_id', true );
	$original_site_name = get_post_meta( $post_id, 'dt_original_site_name', true );
	$original_post_url  = get_post_meta( $post_id, 'dt_original_post_url', true );

	if ( ! empty( $original_blog_id ) && ! empty( $original_post_id ) && is_multisite() ) {
		switch_to_blog( $original_blog_id );

		$link = get_permalink( $original_post_id );

		restore_current_blog();

		return $link;
	} elseif ( ! empty( $original_site_name ) && ! empty( $original_post_url ) ) {
		return $original_post_url;
	} else {
		return false;
	}
}

/**
 * See docblock for distributor_get_original_post_link
 *
 * @param  int $post_id Post ID.
 * @since 1.0
 */
function distributor_the_original_post_link( $post_id = null ) {
	echo esc_url( distributor_get_original_post_link( $post_id ) );
}

/**
 * Get original site name
 *
 * @param  int $post_id Leave null to use current post
 * @since  1.0
 * @return string|bool
 */
function distributor_get_original_site_name( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;

		$post_id = $post->ID;
	}

	$original_blog_id   = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_site_name = get_post_meta( $post_id, 'dt_original_site_name', true );

	if ( ! empty( $original_blog_id ) && is_multisite() ) {
		switch_to_blog( $original_blog_id );

		$text = get_bloginfo( 'name' );

		restore_current_blog();

		return $text;
	} elseif ( ! empty( $original_site_name ) ) {
		return $original_site_name;
	} else {
		return false;
	}
}

/**
 * See docblock for distributor_get_original_site_name
 *
 * @param  int $post_id Post ID.
 * @since 1.0
 */
function distributor_the_original_site_name( $post_id = null ) {
	echo esc_html( distributor_get_original_site_name( $post_id ) );
}

/**
 * Get original site link
 *
 * @param  int $post_id Leave null to use current post
 * @since  1.0
 * @return string|bool
 */
function distributor_get_original_site_url( $post_id = null ) {
	if ( null === $post_id ) {
		global $post;

		$post_id = $post->ID;
	}

	$original_blog_id  = get_post_meta( $post_id, 'dt_original_blog_id', true );
	$original_site_url = get_post_meta( $post_id, 'dt_original_site_url', true );

	if ( ! empty( $original_blog_id ) && is_multisite() ) {
		switch_to_blog( $original_blog_id );

		$url = home_url();

		restore_current_blog();

		return $url;
	} elseif ( ! empty( $original_site_url ) ) {
		return $original_site_url;
	} else {
		return false;
	}
}

/**
 * See docblock for distributor_get_original_site_url
 *
 * @param  int $post_id Post ID.
 * @since 1.0
 */
function distributor_the_original_site_url( $post_id = null ) {
	echo esc_url( distributor_get_original_site_url( $post_id ) );
}

/**
 * Get pretty link for outputting original distributor site link
 *
 * @param  int $post_id Post ID.
 * @since  1.0
 * @return string
 */
function distributor_get_original_site_link( $post_id = null ) {
	$site_name = distributor_get_original_site_name( $post_id );
	$site_url  = distributor_get_original_site_url( $post_id );

	if ( empty( $site_name ) || empty( $site_url ) ) {
		return '';
	}

	/**
	 * Filter the original site link for a distributed post.
	 *
	 * @since 1.0.0
	 * @hook distributor_get_original_site_link
	 *
	 * @param {string} $link A formatted version of the original site link.
	 *
	 * @return {string} A formatted version of the original site link.
	 */
	/* translators: %1$s: site url, %2$s; site name*/
	return apply_filters( 'distributor_get_original_site_link', sprintf( __( 'By <a href="%1$s">%2$s</a>', 'distributor' ), esc_url( $site_url ), esc_html( $site_name ) ) );
}


/**
 * See docblock for distributor_get_original_site_link
 *
 * @param  int $post_id Post ID
 * @since 1.0
 */
function distributor_the_original_site_link( $post_id = null ) {
	echo esc_url( distributor_get_original_site_link( $post_id ) );
}


/**
 * Generate a list of information about a given post.
 *
 * @param int $post_id The Post ID.
 * @return array
 */
function distributor_get_the_connection_source( $post_id = null ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
	}
	return array(
		'url'               => distributor_get_original_site_url( $post_id ),
		'site_name'         => distributor_get_original_site_name( $post_id ),
		'original_post_url' => distributor_get_original_post_link( $post_id ),
		'post_is_unlinked'  => distributor_is_unlinked( $post_id ),
	);
}

/**
 * Display information about where a post was distributed from.
 *
 * @param int|null|mixed     $post_id Post ID.
 * @param string |null|mixed $preface The string that will preceed the link.
 */
function distributor_the_connection_source( $post_id = null, $preface = null ) {
	if ( ! $post_id ) {
		global $post;
		$post_id = $post->ID;
	}

	$connection_data = distributor_get_the_connection_source( $post_id );

	// If the post is unlinked, don't output anything.
	if ( isset( $connection_data['post_is_unlinked'] ) && 1 === $connection_data['post_is_unlinked'] ) {
		return;
	}

	$preface   = ( $preface ) ? $preface : __( 'Distributed from', 'distributor' );
	$url       = isset( $connection_data['original_post_url'] ) ? $connection_data['original_post_url'] : false;
	$site_name = isset( $connection_data['site_name'] ) ? $connection_data['site_name'] : false;

	if ( $url && $site_name ) {
		printf( '%s <a href="%s" target="_blank" rel="noopener noreferrer">%s</a>', esc_html( $preface ), esc_url( $url ), esc_html( $site_name ) );
	}
}

