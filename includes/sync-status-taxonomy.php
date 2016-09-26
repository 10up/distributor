<?php

namespace Syndicate\SyncStatusTaxonomy;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', function() {
    add_action( 'init', __NAMESPACE__ . '\setup_taxonomy' );
} );

/**
 * Setup hidden sync status taxonomy
 *
 * @since  1.0
 */
function setup_taxonomy() {
	$args = array(
		'public'    => false,
		'query_var' => false,
		'rewrite'   => false,
		'update_count_callback' => '_update_generic_term_count',
	);

	$post_types = get_post_types();

	register_taxonomy( 'sy-sync-status', $post_types, $args );
}
