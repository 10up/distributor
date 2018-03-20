<?php

namespace Distributor\Settings;
use Distributor\Utils;

/**
 * Setup settings
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'plugins_loaded', function() {
			add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu', 20 );
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

  	add_settings_field( 'override_author_byline', esc_html__( 'Override Author Byline', 'distributor' ), __NAMESPACE__ . '\override_author_byline_callback', 'distributor', 'dt-section-1' );

}

/**
 * Output replace distributed author settings field
 *
 * @since 1.0
 */
function override_author_byline_callback() {

	$settings = Utils\get_settings();

	$value = true;
	if ( isset( $settings['override_author_byline'] ) && false === $settings['override_author_byline'] ) {
		$value = false;
	}

	?>
	<input <?php checked( $value, true ); ?> type="checkbox" value="1" name="dt_settings[override_author_byline]">

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

	$new_settings['override_author_byline'] = true;
	if ( ! isset( $settings['override_author_byline'] ) ) {
		$new_settings['override_author_byline'] = false;
	}

	return $new_settings;
}
