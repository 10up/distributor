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
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );
		}
	);
}

/**
 * Enqueue admin scripts for settings
 *
 * @param  string $hook
 * @since  0.8
 */
function admin_enqueue_scripts( $hook ) {
	if ( empty( $_GET['page'] ) || 'distributor-settings' !== $_GET['page'] ) {
		return;
	}

	wp_enqueue_style( 'dt-admin-settings', plugins_url( '/dist/css/admin-settings.min.css', __DIR__ ), array(), DT_VERSION );
}

/**
 * Register setting fields and sections
 *
 * @since  1.0
 */
function setup_fields_sections() {
	add_settings_section( 'dt-section-1', '', '', 'distributor' );

  	add_settings_field( 'override_author_byline', esc_html__( 'Override Author Byline', 'distributor' ), __NAMESPACE__ . '\override_author_byline_callback', 'distributor', 'dt-section-1' );
  	add_settings_field( 'automatic_updates', esc_html__( 'Enable Automatic Updates', 'distributor' ), __NAMESPACE__ . '\license_key_callback', 'distributor', 'dt-section-1' );
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
 * Output license key field and check current key
 *
 * @since 1.2
 */
function license_key_callback() {

	$settings = Utils\get_settings();

	$license_key = ( ! empty( $settings['license_key'] ) ) ? $settings['license_key'] : '';
	$email = ( ! empty( $settings['email'] ) ) ? $settings['email'] : '';
	?>
	<div class="license-wrap <?php if ( true === $settings['valid_license'] ) : ?>valid<?php elseif ( false === $settings['valid_license'] ) : ?>invalid<?php endif; ?>">
		<input name="dt_settings[email]" type="email" placeholder="Email" value="<?php echo esc_attr( $email ); ?>"> <input name="dt_settings[license_key]" type="text" placeholder="License Key" value="<?php echo esc_attr( $license_key ); ?>">
	</div>

	<p class="description">
		<?php echo wp_kses_post( __( 'Distributor requires a license to enable automatic updates. Get one for free <a href="https://distributorplugin.com">here</a>.', 'distributor' ) ); ?>
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
	$new_settings = Utils\get_settings();

	if ( ! isset( $settings['override_author_byline'] ) ) {
		$new_settings['override_author_byline'] = false;
	}

	if ( ! empty( $settings['license_key'] ) ) {
		$new_settings['license_key'] = sanitize_text_field( $settings['license_key'] );
	}

	if ( ! empty( $settings['email'] ) ) {
		$new_settings['email'] = sanitize_text_field( $settings['email'] );
	}

	if ( ! empty( $settings['email'] ) && ! empty( $settings['license_key'] ) ) {
		$new_settings['valid_license'] = (bool) Utils\check_license_key( $settings['email'], $settings['license_key'] );
	} else {
		$new_settings['valid_license'] = null;
	}

	return $new_settings;
}
