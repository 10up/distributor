<?php
/**
 * Debug information displayed in Site Health screen.
 *
 * @package  distributor
 */

namespace Distributor\DebugInfo;

use Distributor\Connections;
use Distributor\ExternalConnection;
use Distributor\InternalConnections\NetworkSiteConnection;

/**
 * Setup actions and filters
 *
 * @since 2.0.0
 */
function setup() {
	add_action(
		'admin_init',
		function() {
			/**
			 * Filter whether the debug info is enabled. Enabled by default, return false to disable.
			 *
			 * @since 2.0.0
			 * @hook dt_debug_info_enabled
			 *
			 * @param {bool} true Whether the debug info should be enabled.
			 *
			 * @return {bool} Whether the debug info should be enabled.
			 */
			if ( ! apply_filters( 'dt_debug_info_enabled', true ) ) {
				return;
			}
			add_filter( 'debug_information', __NAMESPACE__ . '\add_debug_info' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_scripts' );
		}
	);
}

/**
 * Enqueue scripts/styles for site health.
 *
 * @since  2.0.0
 *
 * @param int $hook Hook suffix for the current admin page.
 */
function enqueue_scripts( $hook ) {
	if ( 'site-health.php' !== $hook ) {
		return;
	}
	wp_enqueue_style( 'dt-site-health', plugins_url( '/dist/css/admin-site-health.min.css', __DIR__ ), [], DT_VERSION );
}

/**
 * Add distributor debug information to Site Health screen.
 *
 * @see WP_Debug_Data::debug_data
 * @filter debug_information
 *
 * @since 2.0.0
 *
 * @param array $info The full array of site debug information.
 *
 * @return array Filtered debug information.
 */
function add_debug_info( $info ) {

	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . DT_PLUGIN_FILE );
	$text_domain = $plugin_data['TextDomain'];
	$defaults    = [
		'email'                  => '',
		'valid_license'          => false,
		'license_key'            => '',
		'override_author_byline' => true,
		'media_handling'         => 'featured',
	];

	$all_settings = wp_parse_args(
		(array) get_option( 'dt_settings' ),
		$defaults
	);

	$fields = [
		[
			'label' => __( 'Version', 'distributor' ),
			'value' => $plugin_data['Version'],
		],
	];

	if ( false === DT_IS_NETWORK || is_network_admin() ) {
		$fields[] = [
			'label' => __( 'Valid registration', 'distributor' ),
			'value' => $all_settings['valid_license'] ? __( 'Yes', 'distributor' ) : __( 'No', 'distributor' ),
		];
		$fields[] = [
			'label' => __( 'Registration email', 'distributor' ),
			'value' => $all_settings['email'] ? $all_settings['email'] : __( 'N/A', 'distributor' ),
		];
	}

	$fields = array_merge(
		$fields,
		[
			[
				'label' => __( 'Settings', 'distributor' ),
				'value' => [
					__( 'Override Author Byline', 'distributor' ) => $all_settings['override_author_byline'] ? __( 'Yes', 'distributor' ) : __( 'No', 'distributor' ),
					__( 'Media Handling', 'distributor' ) => 'attached' === $all_settings['media_handling'] ? __( 'Featured image and attached images', 'distributor' ) : __( 'Featured image only', 'distributor' ),
				],
			],
			[
				'label' => __( 'Internal Connections', 'distributor' ),
				'value' => get_formatted_internal_connnections(),
			],
			[
				'label' => __( 'External Connections', 'distributor' ),
				'value' => get_formatted_external_connnections(),
			],
		]
	);

	$info[ $text_domain ] = [
		'label'  => $plugin_data['Name'],
		'fields' => $fields,
	];

	return $info;
}

/**
 * Get and format internal connections.
 *
 * @return array
 */
function get_formatted_internal_connnections() {
	if ( empty( Connections::factory()->get_registered()['networkblog'] ) ) {
		return __( 'N/A', 'distributor' );
	}

	$sites  = NetworkSiteConnection::get_available_authorized_sites( 'pull' );
	$output = [];

	foreach ( $sites as $site_array ) {
		$internal_connection  = new NetworkSiteConnection( $site_array['site'] );
		$site_name            = get_blog_option( $internal_connection->site->blog_id, 'blogname' );
		$data                 = [
			__( 'Blog ID', 'distributor' )      => $internal_connection->site->blog_id,
			__( 'URL', 'distributor' )          => get_blog_option( $internal_connection->site->blog_id, 'home' ),
			__( 'Registered', 'distributor' )   => $internal_connection->site->registered,
			__( 'Last updated', 'distributor' ) => $internal_connection->site->last_updated,
		];
		$output[ $site_name ] = format_connection_data( $data );
	}

	if ( empty( $output ) ) {
		return __( 'N/A', 'distributor' );
	}

	return $output;
}

/**
 * Get and format external connections.
 *
 * @return array
 */
function get_formatted_external_connnections() {

	$output = [];

	$external_connections = new \WP_Query(
		array(
			'post_type'      => 'dt_ext_connection',
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'posts_per_page' => 100,
		)
	);

	if ( empty( $external_connections->posts ) ) {
		return __( 'N/A', 'distributor' );
	}

	foreach ( $external_connections->posts as $external_connection_id ) {
		$external_connection_type          = get_post_meta( $external_connection_id, 'dt_external_connection_type', true );
		$external_connection_status        = get_post_meta( $external_connection_id, 'dt_external_connections', true );
		$external_connection_allowed_roles = get_post_meta( $external_connection_id, 'dt_external_connection_allowed_roles', true );

		if ( empty( Connections::factory()->get_registered()[ $external_connection_type ] ) ) {
			continue;
		}

		if ( empty( $external_connection_status ) || empty( $external_connection_status['can_get'] ) ) {
			continue;
		}

		$external_connection = ExternalConnection::instantiate( $external_connection_id );

		if ( is_wp_error( $external_connection ) ) {
			continue;
		}

		$data = [
			__( 'URL', 'distributor' )                   => $external_connection->base_url,
			__( 'Version', 'distributor' )               => get_external_connection_version( $external_connection->base_url ),
			__( 'Status', 'distributor' )                => get_external_connection_status( $external_connection_status ),
			__( 'Auth method', 'distributor' )           => 'wp' === $external_connection_type ? __( 'Username / Password', 'distributor' ) : __( 'WordPress.com Application', 'distributor' ),
			__( 'Username', 'distributor' )              => $external_connection->auth_handler->username,
			__( 'Roles Allowed to Push', 'distributor' ) => implode( ', ', $external_connection_allowed_roles ),
			__( 'Additional data', 'distributor' )       => preg_replace( '/,"/', ', "', wp_json_encode( $external_connection_status ) ),
		];

		$output[ $external_connection->name ] = format_connection_data( $data );
	}

	return $output;
}

/**
 * Get external connection version.
 *
 * @param string $url Remote site REST base URL.
 *
 * @return string Remote Distributor version.
 */
function get_external_connection_version( $url ) {
	$route = trailingslashit( $url ) . 'wp/v2/dt_meta';

	if ( function_exists( 'vip_safe_wp_remote_get' ) && \Distributor\Utils\is_vip_com() ) {
		$response = vip_safe_wp_remote_get( $route, false, 3, 3, 10 );
	} else {
		$response = wp_remote_get( $route, [ 'timeout' => 5 ] );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['version'] ) ) {
		return __( 'N/A', 'distributor' );
	}

	return $body['version'];
}

/**
 * Get external connection status.
 *
 * @param array $external_connection_status External connection status meta.
 */
function get_external_connection_status( $external_connection_status ) {
	$status = __( 'valid', 'distributor' );

	if ( empty( $external_connection_status ) ) {
		$status = __( 'error', 'distributor' );
	} else {
		if ( ! empty( $external_connection_status['errors'] ) && ! empty( $external_connection_status['errors']['no_distributor'] ) ) {
			$status = __( 'error', 'distributor' );
		}

		if ( empty( $external_connection_status['can_post'] ) ) {
			$status = __( 'warning', 'distributor' );
		}
	}

	return $status;
}

/**
 * Format connection data for displaying.
 *
 * @param array $data Assoc array of connection data.
 */
function format_connection_data( $data ) {
	$formatted = array_map(
		function( $key, $value ) {
			return sprintf( '- %1$s: %2$s', $key, $value );
		},
		array_keys( $data ),
		$data
	);

	return "\n" . implode( "\n", $formatted );
}
