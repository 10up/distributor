<?php
/**
 * Debug information displayed in Site Health screen.
 *
 * @package  distributor
 */

namespace Distributor\DebugInfo;

/**
 * Setup actions and filters
 *
 * @since 2.0.0
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_filter( 'debug_information', __NAMESPACE__ . '\add_debug_info' );
		}
	);
}

/**
 * Setup actions and filters
 *
 * @since 2.0.0
 */
/**
 * Add distributor debug information to Site Health screen.
 *
 * @see WP_Debug_Data::debug_data
 * @filter debug_information
 *
 * @param array $info The full array of site debug information.
 * @return array Filtered debug information.
 *
 * @since 2.4.0
 */
function add_debug_info( $info ) {
	$plugin_data  = get_plugin_data( WP_PLUGIN_DIR . '/' . DT_PLUGIN_FILE );
	$text_domain  = $plugin_data['TextDomain'];
	$defaults     = [
		'email'         => '',
		'valid_license' => false,
		'license_key'   => '',
	];
	$all_settings = wp_parse_args(
		(array) get_option( 'dt_settings' ),
		$defaults
	);

	// Get settings without license data.
	$settings = array_diff_key( $all_settings, $defaults );

	$fields = [
		[
			'label' => __( 'Version', 'distributor' ),
			'value' => $plugin_data['Version'],
		],
		[
			'label' => __( 'Valid license', 'distributor' ),
			'value' => $all_settings['valid_license'] ? __( 'Yes', 'distributor' ) : __( 'No', 'distributor' ),
		],
		[
			'label' => __( 'Email', 'distributor' ),
			'value' => $all_settings['email'] ? $all_settings['email'] : __( 'N/A', 'distributor' ),
		],
		[
			'label' => __( 'Settings', 'distributor' ),
			'value' => preg_replace( '/,"/', ', "', wp_json_encode( $settings ) ),
		],
	];

	$info[ $text_domain ] = [
		'label'  => $plugin_data['Name'],
		'fields' => $fields,
	];

	return $info;
}

