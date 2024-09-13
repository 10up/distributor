<?php
/**
 * Distributor uninstall script.
 *
 * @package distributor
 * @since   x.x.x
 */

 // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber

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
	 * Function to delete all relevant data from the site.
	 */
	function dt_delete_data() {
		global $wpdb;

		// Delete post meta and posts of type 'dt_subscription'.
		$subscription_post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = %s",
				'dt_subscription'
			)
		);

		if ( ! empty( $subscription_post_ids ) ) {
			$ids_string = implode( ',', array_map( 'intval', $subscription_post_ids ) );

			// Delete subscription meta.
			$wpdb->query(
				"DELETE FROM $wpdb->postmeta WHERE post_id IN ($ids_string)"
			);

			// Delete subscription posts.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->posts WHERE ID IN (%s)",
					$ids_string
				)
			);

			// Clear the post cache.
			wp_cache_set_posts_last_changed();
			wp_cache_delete_multiple( $subscription_post_ids, 'posts' );
			wp_cache_delete_multiple( $subscription_post_ids, 'post_meta' );
		}

		// Delete relevant options from the options table.
		delete_site_options();
	}

	/**
	 * Delete all relevant options from the options table.
	 */
	function delete_site_options() {
		global $wpdb;

		$option_prefixes = array(
			'dt_',
			'_transient_dt_',
			'_transient_timeout_dt_',
		);

		if ( is_multisite() ) {
			$option_prefixes = array_merge(
				$option_prefixes,
				array(
					'_site_transient_dt_',
					'_site_transient_timeout_dt_',
				)
			);
		}

		// Prepare the WHERE clause for the options table.
		$where_clause = implode( ' OR ', array_fill( 0, count( $option_prefixes ), 'option_name LIKE %s' ) );

		// Prepare the query.
		$query = $wpdb->prepare(
			sprintf(
				"SELECT option_id, option_name FROM $wpdb->options WHERE %s;",
				$where_clause
			),
			array_map(
				function( $prefix ) use ( $wpdb ) {
					return $wpdb->esc_like( $prefix ) . '%';
				},
				$option_prefixes 
			)
		);

		// Fetch the options to delete.
		$options_to_delete = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $options_to_delete ) ) {
			// Collect IDs from fetched options.
			$ids        = array_column( $options_to_delete, 'option_id' );
			$ids_string = implode( ',', array_map( 'intval', $ids ) );

			// Delete the options using the retrieved IDs.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->options WHERE option_id IN ($ids_string)"
				)
			);

			// Flush the options cache.
			$option_names = array_column( $options_to_delete, 'option_name' );
			wp_cache_delete_multiple( $option_names, 'options' );

			// Flush the alloptions cache.
			wp_cache_delete( 'alloptions', 'options' );
		}
	}

	/**
	 * Delete all relevant options from the sitemeta table (multisite only).
	 */
	function delete_sitemeta_options() {
		global $wpdb;

		$option_prefixes = array(
			'dt_',
			'_site_transient_dt_',
			'_site_transient_timeout_dt_',
		);

		// Prepare the WHERE clause for the sitemeta table.
		$where_clause = implode( ' OR ', array_fill( 0, count( $option_prefixes ), 'meta_key LIKE %s' ) );

		$site_id = get_current_network_id();

		$query = $wpdb->prepare(
			sprintf(
				"SELECT meta_id, meta_key FROM $wpdb->sitemeta WHERE site_id = %%d AND (%s);",
				$where_clause
			),
			array_merge(
				[ $site_id ],
				array_map(
					function( $prefix ) use ( $wpdb ) {
						return $wpdb->esc_like( $prefix ) . '%';
					},
					$option_prefixes 
				) 
			)
		);

		// Fetch the sitemeta to delete.
		$sitemeta_to_delete = $wpdb->get_results( $query, ARRAY_A );

		if ( ! empty( $sitemeta_to_delete ) ) {
			// Collect IDs from fetched options.
			$ids        = array_column( $sitemeta_to_delete, 'meta_id' );
			$ids_string = implode( ',', array_map( 'intval', $ids ) );

			// Delete the sitemeta using the retrieved IDs.
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM $wpdb->sitemeta WHERE meta_id IN ($ids_string)"
				)
			);

			// Flush the site options cache.
			$key_names = array_column( $sitemeta_to_delete, 'meta_key' );
			$key_names = array_map(
				function( $key ) use ( $site_id ) {
					return $site_id . ':' . $key;
				},
				$key_names 
			);
			wp_cache_delete_multiple( $key_names, 'site-options' );
		}
	}

	// Check if it's a multisite installation.
	if ( is_multisite() ) {
		// Loop through each site in the network.
		$sites = get_sites();
		foreach ( $sites as $site ) {
			switch_to_blog( $site->blog_id );
			dt_delete_data();
			restore_current_blog();
		}

		// Delete network-wide sitemeta options.
		delete_sitemeta_options();
	} else {
		// Single site.
		dt_delete_data();
	}
}

