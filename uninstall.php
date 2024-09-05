<?php
/**
 * Distributor uninstall script.
 *
 * @package distributor
 * @since   x.x.x
 */

// phpcs:disable

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/*
 * Only remove ALL data if DT_REMOVE_ALL_DATA constant is set to true in user's
 * wp-config.php. This is to prevent data loss when deleting the plugin from the backend
 * and to ensure only the site owner can perform this action.
 */
if ( defined( 'DT_REMOVE_ALL_DATA' ) && true === DT_REMOVE_ALL_DATA ) {
	global $wpdb;

	/**
	 * Function to delete all relevant data from the site (single or multisite).
	 */
	function dt_delete_data( $is_multisite = false ) {
		global $wpdb;

		// Delete post meta and posts of type 'dt_subscription'.
		$subscription_post_ids = $wpdb->get_col( "SELECT ID FROM $wpdb->posts WHERE post_type = 'dt_subscription';" );

		if ( ! empty( $subscription_post_ids ) ) {
			$ids_string = implode( ',', array_map( 'intval', $subscription_post_ids ) );

			// Delete subscription meta.
			$wpdb->query( "DELETE FROM $wpdb->postmeta WHERE post_id IN ($ids_string);" );

			// Delete subscription posts.
			$wpdb->query( "DELETE FROM $wpdb->posts WHERE ID IN ($ids_string);" );

			// Clear the post cache.
			wp_cache_set_posts_last_changed();
			wp_cache_delete_multiple( $subscription_post_ids, 'posts' );
			wp_cache_delete_multiple( $subscription_post_ids, 'post_meta' );
		}

		// Delete relevant options (single or multisite).
		delete_site_options( $is_multisite );
	}

	/**
	 * Delete all relevant options from a site (single or multisite).
	 */
	function delete_site_options( $is_multisite = false ) {
		global $wpdb;

		$option_prefixes = array(
			'dt_',
			'_transient_dt_',
			'_transient_timeout_dt_',
		);

		// Include multisite-specific prefixes if not a single site.
		if ( ! $is_multisite ) {
			$option_prefixes[] = '_site_transient_dt_';
			$option_prefixes[] = '_site_transient_timeout_dt_';
		}

		// Determine the appropriate table and column based on multisite or single site.
		if ( $is_multisite ) {
			$table = $wpdb->sitemeta;
			$id_column = 'meta_id';
			$key_column = 'meta_key';
			$site_column = 'site_id';
			$site_id = get_current_network_id();
		} else {
			$table = $wpdb->options;
			$id_column = 'option_id';
			$key_column = 'option_name';
		}

		// Construct the WHERE clause based on the environment.
		$where_clause = implode( ' OR ', array_fill( 0, count( $option_prefixes ), "$key_column LIKE %s" ) );

		// Prepare the query with proper escaping for both single and multisite.
		$query = $is_multisite
			? $wpdb->prepare(
				"SELECT $id_column FROM $table WHERE $site_column = %d AND ($where_clause);",
				array_merge( [ $site_id ], array_map( function( $prefix ) use ( $wpdb ) {
					return $wpdb->esc_like( $prefix ) . '%';
				}, $option_prefixes ) )
			)
			: $wpdb->prepare(
				"SELECT $id_column FROM $table WHERE $where_clause;",
				array_map( function( $prefix ) use ( $wpdb ) {
					return $wpdb->esc_like( $prefix ) . '%';
				}, $option_prefixes )
			);

		// Fetch the options to delete.
		$options_to_delete = $wpdb->get_col( $query );

		if ( ! empty( $options_to_delete ) ) {
			$ids_string = implode( ',', array_map( 'intval', $options_to_delete ) );

			// Delete the options using the retrieved IDs.
			$wpdb->query( "DELETE FROM $table WHERE $id_column IN ($ids_string);" );

			// Flush the relevant caches.
			$cache_group = $is_multisite ? 'site-options' : 'options';
			wp_cache_delete_multiple( $options_to_delete, $cache_group );

			if ( ! $is_multisite ) {
				// Flush the alloptions cache if it's a single site.
				wp_cache_delete( 'alloptions', 'options' );
			}
		}
	}

	// Check if it's a multisite installation.
	if ( is_multisite() ) {
		// Loop through each site in the network.
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			dt_delete_data( true );
			restore_current_blog();
		}
	} else {
		// Single site.
		dt_delete_data();
	}
}

// phpcs:enable
