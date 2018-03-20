<?php

namespace Distributor\Settings;

/**
 * Setup settings
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );
			add_action( 'admin_init', __NAMESPACE__ . '\setup_fields_sections' );
			add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
		}
	);
}

/**
 * Register setting fields and sections
 *
 * @since  1.0
 */
function setup_fields_sections() {
	add_settings_section( 'dt-section-1', '', '', 'distributor' );

  	add_settings_field( 'replace_distributed_author', esc_html__( 'Replace Distributed Author', 'distributor' ), __NAMESPACE__ . '\replace_distributed_author_callback', 'distributor', 'dt-section-1' );

}

/**
 * Output replace distributed author settings field
 *
 * @since 1.0
 */
function replace_distributed_author_callback() {

	$settings = (array) get_option( 'dt_settings', [] );

	$value = true;
	if ( isset( $settings['replace_distributed_author'] ) && false === $settings['replace_distributed_author'] ) {
		$value = false;
	}

	?>
	<select value="1" name="dt_settings[replace_distributed_author]">
		<option value="1"><?php esc_html_e( 'Yes', 'distributor' ); ?></option>
		<option <?php selected( $value, false ); ?> value="0"><?php esc_html_e( 'No', 'distributor' ); ?></option>
	</select>

	<p class="description">
		<?php esc_html_e( 'For linked distributed posts, replace the author name and link with the original site name and link.', 'distributor' ); ?>
	</p>
	<?php
}

/**
 * Register settings for options table
 *
 * @since  1.0
 */
function register_settings() {
	register_setting( 'dt_settings', 'dt_settings', __NAMESPACE__ . '\sanitize_settings' );
}

/**
 * Output setting menu option
 *
 * @since  1.0
 */
function admin_menu() {
	add_submenu_page( 'distributor', esc_html__( 'Settings', 'distributor' ), esc_html__( 'Settings', 'distributor' ), 'manage_options', 'distributor-settings', __NAMESPACE__ . '\settings_screen' );
}

/**
 * Output setting screen
 *
 * @since  1.0
 */
function settings_screen() {
	?>
	<div class="wrap">
		<h2><?php esc_html_e( 'Distributor Settings', 'distributor' ); ?></h2>

		<form action="options.php" method="post">

		<?php settings_fields( 'dt_settings' ); ?>
		<?php do_settings_sections( 'distributor' ); ?>

		<?php submit_button(); ?>

		</form>
	</div>
	<?php
}

/**
 * Sanitize settings for DB
 *
 * @since  1.0
 */
function sanitize_settings( $settings ) {
	$new_settings = [];

	foreach ( $settings as $key => $value ) {

		if ( 'replace_distributed_author' === $key ) {
			$new_settings[ $key ] = (bool) $value;
		} else {
			$new_settings[ $key ] = sanitize_text_field( $value );
		}
	}

	return $new_settings;
}
