<?php
/**
 * Admin settings screen
 *
 * @package  distributor
 */

namespace Distributor\Settings;

use Distributor\Utils;

/**
 * Setup settings
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu', 20 );

			if ( DT_IS_NETWORK ) {
				add_action( 'network_admin_menu', __NAMESPACE__ . '\network_admin_menu' );
				add_action( 'admin_init', __NAMESPACE__ . '\handle_network_settings' );
			}

			add_action( 'admin_init', __NAMESPACE__ . '\setup_fields_sections' );
			add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
			add_action( 'admin_notices', __NAMESPACE__ . '\maybe_notice' );
			add_action( 'network_admin_notices', __NAMESPACE__ . '\maybe_notice' );
			add_action( 'after_plugin_row', __NAMESPACE__ . '\update_notice', 10, 3 );
			add_action( 'admin_print_styles', __NAMESPACE__ . '\plugin_update_styles' );
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\admin_enqueue_scripts' );

			add_action( 'clean_post_cache', __NAMESPACE__ . '\clean_dt_post_cache' );
		}
	);
}

/**
 * Properly style plugin update row for Distributor
 *
 * @since 1.2
 */
function plugin_update_styles() {
	global $pagenow;

	if ( 'plugins.php' !== $pagenow ) {
		return;
	}

	if ( DT_IS_NETWORK ) {
		$settings = Utils\get_network_settings();
	} else {
		$settings = Utils\get_settings();
	}

	if ( true === $settings['valid_license'] ) {
		return;
	}
	?>
	<style type="text/css">
		#wpbody tr[data-slug="distributor"] td,
		#wpbody tr[data-slug="distributor"] th {
			box-shadow: none;
			border-bottom: 0;
		}

		#distributor-update .update-message {
			margin-top: 0;
		}
	</style>
	<?php
}

/**
 * Under plugin row update notice
 *
 * @param  string $plugin_file Plugin file path.
 * @param  string $plugin_data Plugin data.
 * @param  string $status Plugin status.
 * @since  1.2
 */
function update_notice( $plugin_file, $plugin_data, $status ) {
	if ( DT_PLUGIN_FILE !== $plugin_file ) {
		return;
	}

	if ( DT_IS_NETWORK ) {
		$settings   = Utils\get_network_settings();
		$notice_url = network_admin_url( 'admin.php?page=distributor-settings' );
	} else {
		$notice_url = admin_url( 'admin.php?page=distributor-settings' );
		$settings   = Utils\get_settings();
	}

	if ( true === $settings['valid_license'] ) {
		return;
	}

	if ( is_network_admin() ) {
		$active = DT_IS_NETWORK;
	} else {
		$active = true;
	}
	?>

	<tr class="plugin-update-tr <?php if ( $active ) : ?>active<?php endif; ?>" id="distributor-update" >
		<td colspan="4" class="plugin-update colspanchange">
			<div class="update-message notice inline notice-warning notice-alt">
				<p>
					<?php /* translators: %s: distributor notice url */ ?>
					<?php echo wp_kses_post( sprintf( __( '<a href="%s">Register</a> for a free Distributor key to receive updates.', 'distributor' ), esc_url( $notice_url ) ) ); ?>
				</p>
			</div>
		</td>
	</tr>
	<?php
}

/**
 * Maybe show license or dev version notice
 *
 * @since 1.2
 */
function maybe_notice() {
	if ( 0 === strpos( get_current_screen()->parent_base, 'distributor' ) ) {
		if ( Utils\is_development_version() ) {
			?>
			<div class="notice notice-warning">
			<?php /* translators: %1$s: npm commands, %2$s: distributor url */ ?>
			<p><?php echo wp_kses_post( sprintf( __( 'You appear to be running a development version of Distributor. Certain features may not work correctly without regularly running %1$s. If you&rsquo;re not sure what this means, you may want to <a href="%2$s">download and install</a> the stable version of Distributor instead.', 'distributor' ), '<code>npm install && npm run build</code>', 'https://distributorplugin.com/' ) ); ?></p>
			</div>
			<?php
		} else {
			// Don't bother with the registration notice if this is a dev version
			if ( DT_IS_NETWORK ) {
				$settings = Utils\get_network_settings();
			} else {
				$settings = Utils\get_settings();
			}

			if ( true === $settings['valid_license'] ) {
				return;
			}

			if ( DT_IS_NETWORK ) {
				$notice_url = network_admin_url( 'admin.php?page=distributor-settings' );
			} else {
				$notice_url = admin_url( 'admin.php?page=distributor-settings' );
			}
			?>
			<div data-notice="auto-upgrade-disabled" class="notice notice-warning">
				<?php /* translators: %s: distributor url */ ?>
				<p><?php echo wp_kses_post( sprintf( __( '<a href="%s">Register Distributor</a> to receive important plugin update notices and other Distributor news.', 'distributor' ), esc_url( $notice_url ) ) ); ?></p>
			</div>
			<?php
		}
	}
}

/**
 * Enqueue admin scripts/styles for settings
 *
 * @param  string $hook WP hook.
 * @since  1.2
 */
function admin_enqueue_scripts( $hook ) {
	if ( ! empty( $_GET['page'] ) && 'distributor-settings' === $_GET['page'] ) { // @codingStandardsIgnoreLine Nonce not required.
		$asset_file = DT_PLUGIN_PATH . '/dist/js/admin-settings-css.min.asset.php';
		// Fallback asset data.
		$asset_data = array(
			'version'      => DT_VERSION,
			'dependencies' => array(),
		);
		if ( file_exists( $asset_file ) ) {
			$asset_data = require $asset_file;
		}

		wp_enqueue_style( 'dt-admin-settings', plugins_url( '/dist/css/admin-settings.min.css', __DIR__ ), array(), $asset_data['version'] );
	}
}

/**
 * Register setting fields and sections
 *
 * @since  1.0
 */
function setup_fields_sections() {
	add_settings_section( 'dt-section-1', '', '', 'distributor' );

	add_settings_field( 'override_author_byline', esc_html__( 'Override Author Byline', 'distributor' ), __NAMESPACE__ . '\override_author_byline_callback', 'distributor', 'dt-section-1' );

	add_settings_field( 'media_handling', esc_html__( 'Media Handling', 'distributor' ), __NAMESPACE__ . '\media_handling_callback', 'distributor', 'dt-section-1' );

	if ( false === DT_IS_NETWORK ) {
		add_settings_field( 'registration_key', esc_html__( 'Registration Key', 'distributor' ), __NAMESPACE__ . '\license_key_callback', 'distributor', 'dt-section-1' );
	}
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
	<label><input <?php checked( $value, true ); ?> type="checkbox" value="1" name="dt_settings[override_author_byline]">
	<?php esc_html_e( 'For linked distributed posts, replace the author name and link with the original site name and link.', 'distributor' ); ?>
	</label>
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
	$email       = ( ! empty( $settings['email'] ) ) ? $settings['email'] : '';
	?>

	<?php if ( true === $settings['valid_license'] ) : ?>
		<div class="registered">
			<?php /* translators: %s is registered email. */ ?>
			<p><?php echo esc_html( sprintf( __( 'Distributor is registered to %s.', 'distributor' ), $email ) ); ?></p>
			<a href="#" onclick="this.parentNode.remove(); return false;"><?php esc_html_e( 'Update registration', 'distributor' ); ?></a>
		</div>
	<?php endif; ?>

	<div class="license-wrap <?php if ( true === $settings['valid_license'] ) : ?>valid<?php elseif ( false === $settings['valid_license'] ) : ?>invalid<?php endif; ?>">
		<label class="screen-reader-text" for="dt_settings_email"><?php esc_html_e( 'Email', 'distributor' ); ?></label>
		<input name="dt_settings[email]" type="email" placeholder="<?php esc_attr_e( 'Email', 'distributor' ); ?>" value="<?php echo esc_attr( $email ); ?>" id="dt_settings_email">

		<label class="screen-reader-text" for="dt_settings_license_key"><?php esc_html_e( 'Registration Key', 'distributor' ); ?></label>
		<input name="dt_settings[license_key]" type="text" placeholder="<?php esc_attr_e( 'Registration Key', 'distributor' ); ?>" value="<?php echo esc_attr( $license_key ); ?>" id="dt_settings_license_key">
	</div>

	<?php if ( true !== $settings['valid_license'] ) : ?>
		<p class="description">
			<?php echo wp_kses_post( __( 'Registration is 100% free and provides update notifications and upgrades inside the dashboard. <a href="https://distributorplugin.com/#cta">Register for your key</a>.', 'distributor' ) ); ?>
		</p>
		<?php
	endif;
}

/**
 * Output media handling options.
 *
 * @since 1.3.0
 */
function media_handling_callback() {
	$settings = Utils\get_settings();
	?>

	<ul class="media-handling">
		<li>
			<label><input <?php checked( $settings['media_handling'], 'featured' ); ?> type="radio" value="featured" name="dt_settings[media_handling]">
			<?php esc_html_e( 'Process the featured image only (default).', 'distributor' ); ?>
			</label>
		</li>
		<li>
			<label><input <?php checked( $settings['media_handling'], 'attached' ); ?> type="radio" value="attached" name="dt_settings[media_handling]">
			<?php esc_html_e( 'Process the featured image and any attached images.', 'distributor' ); ?>
			</label>
		</li>
	</ul>

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
 * Output network setting menu option
 *
 * @since  1.2
 */
function network_admin_menu() {
	add_menu_page( 'Distributor', 'Distributor', 'manage_options', 'distributor-settings', __NAMESPACE__ . '\network_settings_screen', Utils\get_admin_icon() );
}

/**
 * Output setting screen
 *
 * @since  1.0
 */
function settings_screen() {
	?>
	<div class="wrap">
		<h1 class="distributor-title distributor-title--settings">
			<span class="distributor-title__text">
				<?php esc_html_e( 'Distributor Settings', 'distributor' ); ?>
			</span>
			<a class="distributor-help-link" target="_blank" href="https://github.com/10up/distributor#installation">
				<span class="dashicons dashicons-info"></span>
				<span class="distributor-help-link__text"><?php esc_html_e( 'Help', 'distributor' ); ?></span>
			</a>
		</h1>

		<form action="options.php" method="post">

		<?php settings_fields( 'dt_settings' ); ?>
		<?php do_settings_sections( 'distributor' ); ?>

		<?php submit_button(); ?>

		</form>
	</div>
	<?php
}

/**
 * Output network settings
 *
 * @since  1.2
 */
function network_settings_screen() {
	$settings = Utils\get_network_settings();

	$license_key = ( ! empty( $settings['license_key'] ) ) ? $settings['license_key'] : '';
	$email       = ( ! empty( $settings['email'] ) ) ? $settings['email'] : '';
	?>

	<div class="wrap">
		<h1 class="distributor-title distributor-title--settings">
			<span class="distributor-title__text">
				<?php esc_html_e( 'Distributor Network Settings', 'distributor' ); ?>
			</span>
			<a class="distributor-help-link" target="_blank" href="https://github.com/10up/distributor#installation">
				<span class="dashicons dashicons-info"></span>
				<span class="distributor-help-link__text"><?php esc_html_e( 'Help', 'distributor' ); ?></span>
			</a>
		</h1>

		<form action="" method="post">
		<?php settings_fields( 'dt-settings' ); ?>
		<?php settings_errors(); ?>

		<input type="hidden" name="dt_network_settings" value="1">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Registration Key', 'distributor' ); ?></th>
					<td>
						<?php if ( true === $settings['valid_license'] ) : ?>
							<div class="registered">
								<?php /* translators: %s is registered email. */ ?>
								<p><?php echo esc_html( sprintf( __( 'Distributor is registered to %s.', 'distributor' ), $email ) ); ?></p>
								<a href="#" onclick="this.parentNode.remove(); return false;"><?php esc_html_e( 'Update registration', 'distributor' ); ?></a>
							</div>
						<?php endif; ?>

						<div class="license-wrap <?php if ( true === $settings['valid_license'] ) : ?>valid<?php elseif ( false === $settings['valid_license'] ) : ?>invalid<?php endif; ?>">
							<input name="dt_settings[email]" type="email" placeholder="<?php esc_attr_e( 'Email', 'distributor' ); ?>" value="<?php echo esc_attr( $email ); ?>"> <input name="dt_settings[license_key]" type="text" placeholder="<?php esc_attr_e( 'Registration Key', 'distributor' ); ?>" value="<?php echo esc_attr( $license_key ); ?>">
						</div>

						<?php if ( true !== $settings['valid_license'] ) : ?>
							<p class="description">
								<?php echo wp_kses_post( __( 'Registration is 100% free and provides update notifications and upgrades inside the dashboard. <a href="https://distributorplugin.com/#cta">Register for your key</a>.', 'distributor' ) ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button(); ?>

		</form>
	</div>

	<?php
}

/**
 * Save network settings
 *
 * @since 1.2
 */
function handle_network_settings() {
	if ( empty( $_POST['dt_network_settings'] ) ) {
		return;
	}

	if ( ! check_admin_referer( 'dt-settings-options' ) ) {
		die( esc_html__( 'Security error!', 'distributor' ) );
	}

	$new_settings = Utils\get_network_settings();

	if ( isset( $_POST['dt_settings']['license_key'] ) ) {
		$new_settings['license_key'] = sanitize_text_field( $_POST['dt_settings']['license_key'] );
	}

	if ( isset( $_POST['dt_settings']['email'] ) ) {
		$new_settings['email'] = sanitize_text_field( $_POST['dt_settings']['email'] );
	}

	if ( ! empty( $_POST['dt_settings']['email'] ) && ! empty( $_POST['dt_settings']['license_key'] ) ) {
		$email_address                 = sanitize_email( wp_unslash( $_POST['dt_settings']['email'] ) );
		$license_key                   = sanitize_text_field( wp_unslash( $_POST['dt_settings']['license_key'] ) );
		$new_settings['valid_license'] = (bool) Utils\check_license_key( $email_address, $license_key );
	} else {
		$new_settings['valid_license'] = null;
	}

	update_site_option( 'dt_settings', $new_settings );
}

/**
 * Clean distributor post caches.
 *
 * Distributor caches a number of post related items to improve performance. This
 * ensures they are cleared when a post is updated.
 *
 * Runs on the hook `clean_post_cache`.
 *
 * @since 2.0.0
 *
 * @param int $post_id Post ID.
 */
function clean_dt_post_cache( int $post_id ) {
	$cache_keys = array(
		"dt_media::{$post_id}",
	);

	if ( function_exists( 'wp_cache_delete_multiple' ) ) {
		wp_cache_delete_multiple( $cache_keys, 'dt::post' );
	} else {
		foreach ( $cache_keys as $cache_key ) {
			wp_cache_delete( $cache_key, 'dt::post' );
		}
	}
}


/**
 * Sanitize settings for DB
 *
 * @param  array $settings Array of settings.
 * @since  1.0
 */
function sanitize_settings( $settings ) {
	$new_settings = Utils\get_settings();

	if ( ! isset( $settings['override_author_byline'] ) ) {
		$new_settings['override_author_byline'] = false;
	} else {
		$new_settings['override_author_byline'] = true;
	}

	if ( ! isset( $settings['media_handling'] ) || ! in_array( $settings['media_handling'], array( 'featured', 'attached' ), true ) ) {
		$new_settings['media_handling'] = 'featured';
	} else {
		$new_settings['media_handling'] = sanitize_text_field( $settings['media_handling'] );
	}

	if ( isset( $settings['license_key'] ) ) {
		$new_settings['license_key'] = sanitize_text_field( $settings['license_key'] );
	}

	if ( isset( $settings['email'] ) ) {
		$new_settings['email'] = sanitize_text_field( $settings['email'] );
	}

	if ( ! DT_IS_NETWORK && ! empty( $settings['email'] ) && ! empty( $settings['license_key'] ) ) {
		$new_settings['valid_license'] = (bool) Utils\check_license_key( $settings['email'], $settings['license_key'] );
	} else {
		$new_settings['valid_license'] = null;
	}

	return $new_settings;
}
