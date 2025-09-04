<?php
/**
 * Auto-distribution functionality for Distributor.
 *
 * @package  distributor
 */

namespace Distributor\AutoDistribute;

/**
 * Setup actions and filters.
 *
 * Runs on the `plugins_loaded, 20` hook.
 *
 * @since 2.2.0
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	if ( ! enabled() ) {
		return;
	}

	add_action( 'publish_post', $n( 'schedule_post_for_auto_distribution' ), 10 );
	add_action( 'dt_auto_distribute', $n( 'distribute_post_to_all_connections' ), 10, 2 );
}

/**
 * Whether auto-distribution is enabled.
 *
 * Default is false, see the filter `dt_auto_distribution_enabled` to enable it.
 *
 * @since 2.2.0
 *
 * @return bool Whether the auto-distribution feature is enabled.
 */
function enabled() {
	/**
	 * Filter whether auto-distribution is enabled.
	 *
	 * To enable auto-distribution, you can use the code:
	 * ```php
	 *     add_filter( 'dt_auto_distribution_enabled', '__return_true' );
	 * ```
	 *
	 * Enabling auto-distribution will automatically distribute posts upon publication
	 * to all network and external connections that the post had not already been distributed
	 * to. These posts will be distributed as published posts, not drafts.
	 *
	 * @since 2.2.0
	 * @hook dt_auto_distribution_enabled
	 *
	 * @param {bool} $enabled Whether the auto-distribution feature is enabled. Default false.
	 *
	 * @return {bool} Whether the auto-distribution feature is enabled.
	 */
	return apply_filters( 'dt_auto_distribution_enabled', false );
}

/**
 * Return the default post status for auto-distributed posts.
 *
 * This is the default status that will be applied to auto-distributed posts
 * on the distribution site.
 *
 * @since 2.2.0
 *
 * @param int|\WP_Post $post Post ID or WP_Post object for the post being pushed.
 * @return string Default status for auto-distributed posts.
 *                Default is 'publish'.
 */
function default_post_status( $post ) {
	$post = get_post( $post );
	/**
	 * Filter the default status for auto-distributed posts.
	 *
	 * @since 2.2.0
	 * @hook dt_auto_distribution_default_status
	 *
	 * @param {string}  $status Default status for auto-distributed posts. Default 'publish'.
	 * @param {WP_Post} $post   Post object for the post being pushed.
	 *
	 * @return {string} Default status for auto-distributed posts.
	 */
	return apply_filters( 'dt_auto_distribution_default_status', 'publish', $post );
}

/**
 * Determine if a post should be auto-distributed.
 *
 * This function checks if the post should be auto-distributed based on filters.
 *
 * @since 2.2.0
 *
 * @param int|\WP_Post $post             Post ID or WP_Post object for the post being pushed.
 * @param int          $user_id          User ID of the user pushing the post.
 * @param string       $connection_type  Type of connection ('external' or 'internal').
 * @param int          $connection_id    Connection ID.
 *
 * @return bool Whether the post should be auto-distributed.
 */
function auto_distribute_post( $post, $user_id, $connection_type, $connection_id ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}

	/**
	 * Filter to determine if a post should be auto-distributed.
	 *
	 * @since 2.2.0
	 * @hook dt_auto_distribute_post
	 *
	 * @param {bool}    $should_distribute Whether the post should be auto-distributed.
	 * @param {WP_Post} $post              WP_Post object for the post being pushed.
	 * @param {int}     $user_id           User ID of the user pushing the post.
	 * @param {string}  $connection_type   Type of connection ('external' or 'internal').
	 * @param {int}     $connection_id     Connection ID.
	 *
	 * @return {bool} Whether the post should be auto-distributed.
	 */
	return apply_filters( 'dt_auto_distribute_post', true, $post, $user_id, $connection_type, $connection_id );
}

/**
 * Return supported post types.
 *
 * By default, this is post and page but that
 * value can be filtered.
 *
 * @since 2.2.0
 *
 * @return string[] Array of post types that can be auto-distributed.
 */
function auto_distribute_supported_post_types() {
	$post_types = array( 'post', 'page' );
	/**
	 * Filter the post types that are auto-distributable.
	 *
	 * @since 2.2.0
	 * @hook auto_distribute_supported_post_types
	 *
	 * @param {string[]} $post_types Array of post types that can be auto-distributed.
	 *                               Default is array( 'post', 'page' ).
	 * @return {string[]} Array of post types that can be auto-distributed.
	 */
	return apply_filters( 'auto_distribute_supported_post_types', $post_types );
}

/**
 * Schedule a post for auto distribution.
 *
 * Create a scheduled event to distribute the post to all connections,
 * including both internal and external connections.
 *
 * @param int|WP_Post $post     Post ID or WP_Post object.
 * @param int         $user_id User ID of the post author. Defaults to current user.
 */
function schedule_post_for_auto_distribution( $post = 0, $user_id = 0 ) {
	$post = get_post( $post );

	if ( ! $post ) {
		return;
	}

	if ( ! in_array( $post->post_type, auto_distribute_supported_post_types(), true ) ) {
		// If the post type is not supported, do not schedule for distribution.
		return;
	}

	if ( get_post_meta( $post->ID, 'dt_original_post_url', true ) ) {
		// Avoid re-distributing posts that are themselves distributed
		return;
	}

	$user_id = $user_id ? $user_id : get_current_user_id();

	if ( ! wp_next_scheduled( 'dt_auto_distribute', [ $post->ID, $user_id ] ) ) {
		wp_schedule_single_event( time(), 'dt_auto_distribute', [ $post->ID, $user_id ] );
	}
}

/**
 * Distribute a post to all connections.
 *
 * This function retrieves all connections for the given post and user,
 * and distributes the post to each connection that is not already syndicated.
 *
 * @param int|WP_Post $post     Post ID or WP_Post object.
 * @param int         $user_id User ID of the post author.
 */
function distribute_post_to_all_connections( $post = 0, $user_id = 0 ) {
	$post = get_post( $post );

	if ( ! $post || ! $user_id ) {
		return;
	}

	if ( ! in_array( $post->post_type, auto_distribute_supported_post_types(), true ) ) {
		// If the post type is not supported, do not auto distribute.
		return;
	}

	$connections = get_connections( $post->ID, $user_id );
	if ( empty( $connections ) ) {
		return;
	}

	foreach ( $connections as $connection ) {
		if ( $connection['syndicated'] ) {
			continue;
		}

		$connection_id   = $connection['id'];
		$connection_type = $connection['type'];

		distribute_post( $post->ID, $user_id, $connection_id, $connection_type );
	}
}

/**
 * Distribute a post to a specific connection.
 *
 * This function pushes the post to the specified connection and updates
 * the connection map in the post meta.
 *
 * @param int    $post_id         Post ID.
 * @param int    $user_id         User ID of the post author.
 * @param int    $connection_id   Connection ID.
 * @param string $connection_type Type of connection ('external' or 'internal').
 */
function distribute_post( $post_id = 0, $user_id = 0, $connection_id = 0, $connection_type = '' ) {
	$post = get_post( $post_id );

	if ( ! $post || ! $user_id || ! $connection_id || ! $connection_type ) {
		return;
	}

	if ( ! auto_distribute_post( $post, $user_id, $connection_type, $connection_id ) ) {
		// The post should not be auto-distributed, do not proceed.
		return;
	}

	// Make sure we have a proper user set up
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	wp_set_current_user( $user_id );

	if ( 'external' === $connection_type ) {
		$connection = \Distributor\ExternalConnection::instantiate( $connection_id );
	} elseif ( 'internal' === $connection_type ) {
		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( get_site( $connection_id ) );
	} else {
		return; // Invalid connection type
	}

	$post_id        = $post->ID;
	$connection_map = get_post_meta( intval( $post_id ), 'dt_connection_map', true );
	if ( ! is_array( $connection_map ) ) {
		$connection_map = [];
	}
	if ( empty( $connection_map['internal'] ) ) {
		$connection_map['internal'] = [];
	}
	if ( empty( $connection_map['external'] ) ) {
		$connection_map['external'] = [];
	}
	$args        = array(
		'post_status' => default_post_status( $post ),
	);
	$remote_post = $connection->push( $post_id, $args );
	if ( ! is_wp_error( $remote_post ) && ! empty( $remote_post['id'] ) ) {
		// Store the connection mapping
		$connection_map[ $connection_type ][ $connection_id ] = [
			'post_id' => (int) $remote_post['id'],
			'time'    => time(),
		];

		// Update the post meta with the new connection map
		update_post_meta( intval( $post_id ), 'dt_connection_map', $connection_map );
	}
}

/**
 * Get all connections
 *
 * @param integer $post_id Post ID.
 * @param integer $user_id User ID.
 * @return array
 */
function get_connections( $post_id = 0, $user_id = 0 ) {
	$external = get_external_connections( $post_id, $user_id );
	$internal = get_internal_connections( $post_id, $user_id );

	return array_merge( $external, $internal );
}

/**
 * Get all external connections
 *
 * @param integer $post_id Post ID.
 * @param integer $user_id User ID.
 * @return array
 */
function get_external_connections( $post_id = 0, $user_id = 0 ) {
	$connection_objects = [];
	$connections        = new \WP_Query(
		[
			'post_type'      => 'dt_ext_connection',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			/**
			 * Filter the maximum number of external connections to load.
			 *
			 * Modify the maximum number of external connection post types are
			 * queried with requesting the post type.
			 *
			 * @hook dt_external_connections_per_page
			 *
			 * @since 2.2.0
			 *
			 * @param {int} $max_connections The maximum number of external connections to load.
			 * @return {int} The maximum number of external connections to load.
			 */
			'posts_per_page' => apply_filters( 'dt_external_connections_per_page', 200 ), // @codingStandardsIgnoreLine This high pagination limit is purposeful
		]
	);
	// Prime posts and post meta caches. No terms for external connections.
	_prime_post_caches( $connections->posts, false, true );

	// Get our current connection mapping
	$connection_map = (array) get_post_meta( $post_id, 'dt_connection_map', true );
	if ( empty( $connection_map['external'] ) ) {
		$connection_map['external'] = [];
	}

	// Make sure we have a proper user set up
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	wp_set_current_user( $user_id );

	foreach ( $connections->posts as $connection_id ) {
		$connection_type = get_post_meta( $connection_id, 'dt_external_connection_type', true );

		if ( empty( \Distributor\Connections::factory()->get_registered()[ $connection_type ] ) ) {
			continue;
		}

		$connection_status = get_post_meta( $connection_id, 'dt_external_connections', true );
		$allowed_roles     = get_post_meta( $connection_id, 'dt_external_connection_allowed_roles', true );

		if ( empty( $allowed_roles ) ) {
			$allowed_roles = array( 'administrator', 'editor' );
		}

		// Make sure we have a proper connection status
		if ( empty( $connection_status ) || empty( $connection_status['can_get'] ) ) {
			continue;
		}

		// Make sure we have no connection errors
		if ( ! empty( $connection_status['errors'] ) && ! empty( $connection_status['errors']['no_distributor'] ) ) {
			continue;
		}

		// Make sure this post type is supported
		if ( ! in_array( get_post_type( $post_id ), $connection_status['can_post'], true ) ) {
			continue;
		}

		// If not admin lets make sure the current user can push to this connection
		if ( ! current_user_can( apply_filters( 'dt_push_capabilities', 'manage_options' ) ) ) {
			$current_user_roles = (array) wp_get_current_user()->roles;

			if ( count( array_intersect( $current_user_roles, $allowed_roles ) ) < 1 ) {
				continue;
			}
		}

		$connection = \Distributor\ExternalConnection::instantiate( $connection_id );

		if ( ! is_wp_error( $connection ) ) {
			$connection_objects[ 'external' . $connection->id ] = [
				'type'       => 'external',
				'id'         => $connection->id,
				'url'        => $connection->base_url,
				'name'       => $connection->name,
				'syndicated' => ( ! empty( $connection_map['external'][ (int) $connection_id ] ) ) ? true : false,
			];
		}
	}

	return $connection_objects;
}

/**
 * Get all internal connections
 *
 * @param integer $post_id Post ID.
 * @param integer $user_id User ID.
 * @return array
 */
function get_internal_connections( $post_id = 0, $user_id = 0 ) {
	$connection_objects = [];

	if ( ! is_multisite() || empty( \Distributor\Connections::factory()->get_registered()['networkblog'] ) ) {
		return $connection_objects;
	}

	// Get our current connection mapping
	$connection_map = (array) get_post_meta( $post_id, 'dt_connection_map', true );
	if ( empty( $connection_map['internal'] ) ) {
		$connection_map['internal'] = [];
	}

	// Make sure we have a proper user set up
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	wp_set_current_user( $user_id );

	$sites = \Distributor\InternalConnections\NetworkSiteConnection::build_available_authorized_sites( $user_id, 'push' );

	foreach ( $sites as $site_array ) {
		if ( ! in_array( get_post_type( $post_id ), $site_array['post_types'], true ) ) {
			continue;
		}

		$connection = new \Distributor\InternalConnections\NetworkSiteConnection( $site_array['site'] );

		$syndicated = false;
		if ( ! empty( $connection_map['internal'][ (int) $connection->site->blog_id ] ) ) {
			switch_to_blog( $connection->site->blog_id );
			$syndicated = get_permalink( $connection_map['internal'][ (int) $connection->site->blog_id ]['post_id'] );
			restore_current_blog();

			if ( empty( $syndicated ) ) {
				$syndicated = true; // In case it was deleted
			}
		}

		$connection_objects[ 'internal' . $connection->site->blog_id ] = [
			'type'       => 'internal',
			'id'         => $connection->site->blog_id,
			'url'        => untrailingslashit( preg_replace( '#(https?:\/\/|www\.)#i', '', get_site_url( $connection->site->blog_id ) ) ),
			'name'       => $connection->site->blogname,
			'syndicated' => $syndicated,
		];
	}

	return $connection_objects;
}
