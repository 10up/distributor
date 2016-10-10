<?php

namespace Syndicate\AdminBarUI;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', function() {
	add_action( 'admin_bar_menu', __NAMESPACE__ . '\menu_button', 999 );
} );

/**
 * Let's setup our syndicate menu in the toolbar
 *
 * @param object $wp_admin_bar
 * @since  1.0
 */
function menu_button( $wp_admin_bar ) {
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		$wp_admin_bar->add_node( array(
			'id' => 'syndicate',
			'title' => 'Syndicate',
			'href' => '#',
		) );
	}
}
