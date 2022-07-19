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
		if ( file_exists( DT_PLUGIN_PATH . 'composer.lock' ) ) {
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
		wp_enqueue_style( 'dt-admin-settings', plugins_url( '/dist/css/admin-settings.min.css', __DIR__ ), array(), DT_VERSION );
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
		add_settings_field( 'registation_key', esc_html__( 'Registration Key', 'distributor' ), __NAMESPACE__ . '\license_key_callback', 'distributor', 'dt-section-1' );
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
	add_menu_page( 'Distributor', 'Distributor', 'manage_options', 'distributor-settings', __NAMESPACE__ . '\network_settings_screen', 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzYiIGhlaWdodD0iMzkiIHZpZXdCb3g9IjAgMCAzNiAzOSIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj48dGl0bGU+ZGlzdHJpYnV0b3ItaWNvbjwvdGl0bGU+PGRlc2M+Q3JlYXRlZCB1c2luZyBGaWdtYTwvZGVzYz48ZyBpZD0iQ2FudmFzIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtNDAyNCAzNjUpIj48ZyBpZD0iZGlzdHJpYnV0b3ItaWNvbiI+PHVzZSB4bGluazpocmVmPSIjcGF0aDBfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjx1c2UgeGxpbms6aHJlZj0iI3BhdGgxX2ZpbGwiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQwMjQuNzEgLTM2NSkiIGZpbGw9IiNBMEE1QUEiLz48dXNlIHhsaW5rOmhyZWY9IiNwYXRoMl9maWxsIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0MDI0LjcxIC0zNjUpIiBmaWxsPSIjQTBBNUFBIi8+PHVzZSB4bGluazpocmVmPSIjcGF0aDNfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjx1c2UgeGxpbms6aHJlZj0iI3BhdGg0X2ZpbGwiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQwMjQuNzEgLTM2NSkiIGZpbGw9IiNBMEE1QUEiLz48dXNlIHhsaW5rOmhyZWY9IiNwYXRoNV9maWxsIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSg0MDI0LjcxIC0zNjUpIiBmaWxsPSIjQTBBNUFBIi8+PHVzZSB4bGluazpocmVmPSIjcGF0aDZfZmlsbCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoNDAyNC43MSAtMzY1KSIgZmlsbD0iI0EwQTVBQSIvPjwvZz48L2c+PGRlZnM+PHBhdGggaWQ9InBhdGgwX2ZpbGwiIGQ9Ik0gNy45MzgxNCAyMy4xMTA4QyA3LjAwNzQzIDIzLjExMDggNi4yOTE1IDIzLjkwNDUgNi4yOTE1IDI0LjkzNjJDIDYuMjkxNSAyNS44ODg3IDcuMDA3NDMgMjYuNzYxNyA3Ljg2NjU0IDI2Ljc2MTdDIDguOTQwNDMgMjguNDI4NCAxMC4zNzIzIDI5Ljc3NzcgMTIuMDE4OSAzMC43MzAxQyAxMy41OTQgMzEuNjAzMiAxNS4zMTIyIDMyIDE2Ljk1ODggMzJDIDIxLjExMTIgMzIgMjUuMTIwNCAyOS40NjAyIDI3LjEyNSAyNS4wMTU2QyAyOC40ODUyIDIxLjk5OTYgMjguNjI4NCAxOC42NjYyIDI3LjY5NzcgMTUuNDkxNUMgMjYuNjk1NCAxMi4zMTY3IDI0LjY5MDggOS43NzY5NiAyMS45NzAzIDguMjY4OTZDIDE2LjM4NjEgNS4yNTI5OCA5LjY1NjM2IDcuNzkyNzYgNi44NjQyNSAxMy45ODM1TCA2LjY0OTQ3IDE0LjM4MDNMIDYuNzIxMDYgMTQuMzgwM0MgNi40MzQ2OSAxNS4wMTUyIDYuMjE5OTEgMTUuODg4MyA2Ljc5MjY1IDE2LjIwNThMIDEyLjczNDggMTguNzQ1NUMgMTIuNzM0OCAxOC45ODM2IDEyLjY2MzMgMTkuMjIxOCAxMi42NjMzIDE5LjQ1OTlDIDEyLjY2MzMgMjIuMDc5IDE0LjU5NjMgMjQuMjIxOSAxNi45NTg4IDI0LjIyMTlDIDE5LjMyMTQgMjQuMjIxOSAyMS4yNTQ0IDIyLjA3OSAyMS4yNTQ0IDE5LjQ1OTlDIDIxLjI1NDQgMTYuODQwNyAxOS4zMjE0IDE0LjY5NzggMTYuOTU4OCAxNC42OTc4QyAxNS41MjcgMTQuNjk3OCAxNC4yMzgzIDE1LjQ5MTUgMTMuNDUwOCAxNi42ODJMIDguNTgyNDcgMTQuNjE4NEMgOS43Mjc5NSAxMi4yMzc0IDExLjU4OTQgMTAuNDExOSAxMy44ODAzIDkuNTM4ODVDIDE2LjI0MjkgOC42NjU4IDE4LjgyMDIgOC44MjQ1NCAyMS4xMTEyIDEwLjAxNTFDIDI1Ljc2NDcgMTIuNTU0OCAyNy42OTc3IDE4LjgyNDkgMjUuNDA2OCAyMy45ODM4QyAyNC4zMzI5IDI2LjUyMzYgMjIuMzk5OSAyOC4zNDkxIDE5Ljk2NTcgMjkuMzAxNUMgMTcuNjAzMSAzMC4xNzQ1IDE1LjAyNTggMzAuMDE1OCAxMi43MzQ4IDI4LjgyNTNDIDExLjM3NDYgMjguMTExIDEwLjIyOTEgMjYuOTk5OCA5LjI5ODQgMjUuNjUwNkMgOS4zNjk5OSAyNS40MTI1IDkuNDQxNTggMjUuMTc0NCA5LjQ0MTU4IDI0LjkzNjJDIDkuNTg0NzcgMjMuOTgzOCA4Ljc5NzI1IDIzLjExMDggNy45MzgxNCAyMy4xMTA4WiIvPjxwYXRoIGlkPSJwYXRoMV9maWxsIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0gMTIuMDE4OSAzMC43MzAxQyAxMC4zNzIzIDI5Ljc3NzcgOC45NDA0MyAyOC40Mjg0IDcuODY2NTQgMjYuNzYxN0MgNy4wMDc0MyAyNi43NjE3IDYuMjkxNSAyNS44ODg3IDYuMjkxNSAyNC45MzYyQyA2LjI5MTUgMjMuOTA0NSA3LjAwNzQzIDIzLjExMDggNy45MzgxNCAyMy4xMTA4QyA4Ljc5NzI1IDIzLjExMDggOS41ODQ3NyAyMy45ODM4IDkuNDQxNTggMjQuOTM2MkMgOS40NDE1OCAyNS4xNzQ0IDkuMzY5OTkgMjUuNDEyNSA5LjI5ODQgMjUuNjUwNkMgMTAuMjI5MSAyNi45OTk4IDExLjM3NDYgMjguMTExIDEyLjczNDggMjguODI1M0MgMTUuMDI1OCAzMC4wMTU4IDE3LjYwMzEgMzAuMTc0NSAxOS45NjU3IDI5LjMwMTVDIDIyLjM5OTkgMjguMzQ5MSAyNC4zMzI5IDI2LjUyMzYgMjUuNDA2OCAyMy45ODM4QyAyNy42OTc3IDE4LjgyNDkgMjUuNzY0NyAxMi41NTQ4IDIxLjExMTIgMTAuMDE1MUMgMTguODIwMiA4LjgyNDU0IDE2LjI0MjkgOC42NjU4IDEzLjg4MDMgOS41Mzg4NUMgMTEuNjc2NSAxMC4zNzg3IDkuODcwMDcgMTIuMDk5OSA4LjcxNjE2IDE0LjM0OTNDIDguNjcwNTYgMTQuNDM4MiA4LjYyNjAyIDE0LjUyNzkgOC41ODI0NyAxNC42MTg0TCAxMy40NTA4IDE2LjY4MkMgMTQuMjM4MyAxNS40OTE1IDE1LjUyNyAxNC42OTc4IDE2Ljk1ODggMTQuNjk3OEMgMTkuMzIxNCAxNC42OTc4IDIxLjI1NDQgMTYuODQwNyAyMS4yNTQ0IDE5LjQ1OTlDIDIxLjI1NDQgMjIuMDc5IDE5LjMyMTQgMjQuMjIxOSAxNi45NTg4IDI0LjIyMTlDIDE0LjU5NjMgMjQuMjIxOSAxMi42NjMzIDIyLjA3OSAxMi42NjMzIDE5LjQ1OTlDIDEyLjY2MzMgMTkuMzQwOCAxMi42ODExIDE5LjIyMTggMTIuNjk5IDE5LjEwMjdDIDEyLjcxNjkgMTguOTgzNyAxMi43MzQ4IDE4Ljg2NDYgMTIuNzM0OCAxOC43NDU1TCA2Ljc5MjY1IDE2LjIwNThDIDYuMzA2MDcgMTUuOTM2IDYuMzg3ODQgMTUuMjY1MyA2LjU5OTA3IDE0LjY4MDNDIDYuNjE1MTcgMTQuNjM1NyA2LjYzMjAyIDE0LjU5MTcgNi42NDk0MiAxNC41NDgzQyA2LjY2ODA4IDE0LjUwMTggNi42ODczOCAxNC40NTYxIDYuNzA3MDggMTQuNDExNUMgNi43MTE3IDE0LjQwMTEgNi43MTY0IDE0LjM5MDYgNi43MjEwNiAxNC4zODAzTCA2LjY0OTQ3IDE0LjM4MDNMIDYuODY0MjUgMTMuOTgzNUMgOS42NTYzNiA3Ljc5Mjc2IDE2LjM4NjEgNS4yNTI5OCAyMS45NzAzIDguMjY4OTZDIDI0LjY5MDggOS43NzY5NiAyNi42OTU0IDEyLjMxNjcgMjcuNjk3NyAxNS40OTE1QyAyOC42Mjg0IDE4LjY2NjIgMjguNDg1MiAyMS45OTk2IDI3LjEyNSAyNS4wMTU2QyAyNS4xMjA0IDI5LjQ2MDIgMjEuMTExMiAzMiAxNi45NTg4IDMyQyAxNS4zMTIyIDMyIDEzLjU5NCAzMS42MDMyIDEyLjAxODkgMzAuNzMwMVpNIDYuNTk1MjIgMTMuODUwMkMgOS40NTI5OCA3LjUyNzIzIDE2LjM2MDQgNC44OTgxOSAyMi4xMTI4IDguMDA1MDJMIDIyLjExNTcgOC4wMDY1OEMgMjQuOTA5MSA5LjU1NDk1IDI2Ljk2MDQgMTIuMTU5NiAyNy45ODM4IDE1LjQwMTFMIDI3Ljk4NTYgMTUuNDA3MUMgMjguOTI2MiAxOC42Mjk5IDI4Ljc3NzEgMjIuMDY5IDI3LjM5ODQgMjUuMTM5QyAyNS4zNDg5IDI5LjY4MzIgMjEuMjM3NiAzMi4zIDE2Ljk1ODggMzIuM0MgMTUuMjc4MiAzMi4yOTgxIDEzLjQ4NjQgMzEuODgzMiAxMS44NzM0IDMwLjk5MjVMIDExLjg2ODYgMzAuOTg5OUMgMTAuMjM1NiAzMC4wNDMxIDguNzg1MjYgMjguNjkxMSA3LjY5ODU5IDI3LjA1M0MgNi43MTQyMyAyNi45NDk3IDUuOTkxNDYgMjUuOTQ5NiA1Ljk5MTQ2IDI0LjkzNjNDIDUuOTkxNDYgMjMuNzY4NiA2LjgxMzM1IDIyLjgxMDggNy45MzgwOSAyMi44MTA4QyA4Ljk3ODc4IDIyLjgxMDggOS44OTcyOSAyMy44MzkyIDkuNzQxMDUgMjQuOTYxMkMgOS43MzUwMyAyNS4xODExIDkuNjgzMDYgMjUuMzk5OCA5LjYyNjc5IDI1LjU5NzJDIDEwLjUyMjcgMjYuODY4MiAxMS41ODkxIDI3Ljg4NzkgMTIuODczMiAyOC41NTkxQyAxNS4wODkgMjkuNzEwNiAxNy41NzczIDI5Ljg2MzQgMTkuODU5IDI5LjAyMTFDIDIyLjIxNzYgMjguMDk3NSAyNC4wODg4IDI2LjMzMDQgMjUuMTMwNCAyMy44NjdMIDI1LjEzMjUgMjMuODYyMUMgMjcuMzY1OSAxOC44MzI3IDI1LjQ3MTQgMTIuNzM4NiAyMC45NzAxIDEwLjI3OTlDIDE4Ljc1NDUgOS4xMjk0NiAxNi4yNjY4IDguOTc3MjQgMTMuOTg1NyA5LjgxOTc0QyAxMS44NjY4IDEwLjYyNzcgMTAuMTE3OCAxMi4yODQ3IDguOTkzMTMgMTQuNDY2N0wgMTMuMzQzOSAxNi4zMTA5QyAxNC4xODgyIDE1LjE2MjIgMTUuNDk3OCAxNC4zOTc4IDE2Ljk1ODggMTQuMzk3OEMgMTkuNTE1NCAxNC4zOTc4IDIxLjU1NDMgMTYuNzA0OSAyMS41NTQzIDE5LjQ1OTlDIDIxLjU1NDMgMjIuMjE0OSAxOS41MTU0IDI0LjUyMTkgMTYuOTU4OCAyNC41MjE5QyAxNC40MDIyIDI0LjUyMTkgMTIuMzYzMiAyMi4yMTQ5IDEyLjM2MzIgMTkuNDU5OUMgMTIuMzY0IDE5LjMxODUgMTIuMzg0NCAxOS4xNzcyIDEyLjQwMjMgMTkuMDU4MUwgMTIuNDA1IDE5LjA0MDVDIDEyLjQxMDQgMTkuMDA0MyAxMi40MTU2IDE4Ljk3MDEgMTIuNDIgMTguOTM3M0wgNi42NjA1OSAxNi40NzU2TCA2LjY0NzE2IDE2LjQ2ODJDIDYuMjE2ODUgMTYuMjI5NiA2LjExNDI3IDE1Ljc4ODEgNi4xMzUwNiAxNS4zOTYyQyA2LjE0ODkzIDE1LjE2NTkgNi4yMDY3NiAxNC45MTU3IDYuMjgzNjUgMTQuNjgwM0wgNi4xNDU5MyAxNC42ODAzTCA2LjU5NTIyIDEzLjg1MDJaIi8+PHBhdGggaWQ9InBhdGgyX2ZpbGwiIGQ9Ik0gMzIuMTI4MSAxMC4wMTY4QyAzMi4xMjgxIDkuMDA2MyAzMS4zOTU1IDguMjI4OTkgMzAuNDQzMiA4LjIyODk5QyAyOS40OTA4IDguMjI4OTkgMjguNzU4MiA5LjAwNjMgMjguNzU4MiAxMC4wMTY4QyAyOC43NTgyIDEwLjk0OTYgMjkuNDkwOCAxMS43MjY5IDMwLjM2OTkgMTEuODA0NkMgMzIuODYwNyAxNi4zOTA4IDMzLjA4MDUgMjIuMDY1MSAzMC44ODI3IDI2Ljg4NDVDIDI5LjA1MTIgMzAuNzcxIDI1Ljk3NDMgMzMuNjQ3MSAyMi4xNjQ4IDM0Ljk2ODVDIDE4LjM1NTMgMzYuMzY3NyAxNC4yNTI4IDM2LjA1NjcgMTAuNjYzMSAzNC4xOTEyQyAzLjE5MDYyIDMwLjMwNDYgMC4xMTM3MjQgMjAuNjY2IDMuNzc2NyAxMi43Mzc0QyA1Ljk3NDQ4IDcuOTk1OCAxMC4yOTY4IDQuNzMxMDkgMTUuMTMxOSAzLjk1Mzc4QyAxNS40MjUgNC4zNDI0NCAxNS44NjQ1IDQuNTc1NjMgMTYuMzc3MyA0LjU3NTYzQyAxNy4zMjk3IDQuNTc1NjMgMTguMDYyMyAzLjc5ODMyIDE4LjA2MjMgMi43ODc4MkMgMTguMDYyMyAxLjc3NzMxIDE3LjMyOTcgMSAxNi4zNzczIDFDIDE1LjcxOCAxIDE1LjIwNTIgMS4zODg2NiAxNC45MTIxIDEuOTMyNzdDIDkuNDE3NjggMi43ODc4MiA0LjUwOTMgNi41MTg5MSAyLjAxODQ3IDExLjg4MjRDIDAuMDQwNDY1NiAxNi4yMzUzIC0wLjI1MjU3MyAyMS4xMzI0IDEuMjEyNjIgMjUuNzE4NUMgMi42Nzc4MSAzMC4zMDQ2IDUuNzU0NzEgMzMuOTU4IDkuNzgzOTggMzYuMTM0NUMgMTIuMjAxNSAzNy4zNzgyIDE0Ljc2NTYgMzggMTcuMjU2NCAzOEMgMjMuNTU2OCAzOCAyOS42MzczIDM0LjI2ODkgMzIuNTY3NyAyNy44OTVDIDM1LjA1ODUgMjIuNDUzOCAzNC44Mzg3IDE2LjAwMjEgMzEuOTgxNiAxMC43OTQxQyAzMi4wNTQ5IDEwLjU2MDkgMzIuMTI4MSAxMC4zMjc3IDMyLjEyODEgMTAuMDE2OFoiLz48cGF0aCBpZD0icGF0aDNfZmlsbCIgZmlsbC1ydWxlPSJldmVub2RkIiBkPSJNIDIuMDE4NDcgMTEuODgyNEMgNC41MDkzIDYuNTE4OTEgOS40MTc2OCAyLjc4NzgyIDE0LjkxMjEgMS45MzI3N0MgMTUuMjA1MiAxLjM4ODY2IDE1LjcxOCAxIDE2LjM3NzMgMUMgMTcuMzI5NyAxIDE4LjA2MjMgMS43NzczMSAxOC4wNjIzIDIuNzg3ODJDIDE4LjA2MjMgMy43OTgzMiAxNy4zMjk3IDQuNTc1NjMgMTYuMzc3MyA0LjU3NTYzQyAxNS44NjQ1IDQuNTc1NjMgMTUuNDI1IDQuMzQyNDQgMTUuMTMxOSAzLjk1Mzc4QyAxMC4yOTY4IDQuNzMxMDkgNS45NzQ0OCA3Ljk5NTggMy43NzY3IDEyLjczNzRDIDAuMTEzNzI0IDIwLjY2NiAzLjE5MDYyIDMwLjMwNDYgMTAuNjYzMSAzNC4xOTEyQyAxNC4yNTI4IDM2LjA1NjcgMTguMzU1MyAzNi4zNjc3IDIyLjE2NDggMzQuOTY4NUMgMjUuOTc0MyAzMy42NDcxIDI5LjA1MTIgMzAuNzcxIDMwLjg4MjcgMjYuODg0NUMgMzMuMDgwNSAyMi4wNjUxIDMyLjg2MDcgMTYuMzkwOCAzMC4zNjk5IDExLjgwNDZDIDI5LjQ5MDggMTEuNzI2OSAyOC43NTgyIDEwLjk0OTYgMjguNzU4MiAxMC4wMTY4QyAyOC43NTgyIDkuMDA2MyAyOS40OTA4IDguMjI4OTkgMzAuNDQzMiA4LjIyODk5QyAzMS4zOTU1IDguMjI4OTkgMzIuMTI4MSA5LjAwNjMgMzIuMTI4MSAxMC4wMTY4QyAzMi4xMjgxIDEwLjMyNzcgMzIuMDU0OSAxMC41NjA5IDMxLjk4MTYgMTAuNzk0MUMgMzQuODM4NyAxNi4wMDIxIDM1LjA1ODUgMjIuNDUzOCAzMi41Njc3IDI3Ljg5NUMgMjkuNjM3MyAzNC4yNjg5IDIzLjU1NjggMzggMTcuMjU2NCAzOEMgMTQuNzY1NiAzOCAxMi4yMDE1IDM3LjM3ODIgOS43ODM5OCAzNi4xMzQ1QyA1Ljc1NDcxIDMzLjk1OCAyLjY3NzgxIDMwLjMwNDYgMS4yMTI2MiAyNS43MTg1QyAtMC4yNTI1NzMgMjEuMTMyNCAwLjA0MDQ2NTYgMTYuMjM1MyAyLjAxODQ3IDExLjg4MjRaTSAzMi4zMDU1IDEwLjc2MTZDIDMyLjM2NzEgMTAuNTU0NiAzMi40MjUzIDEwLjMxMjkgMzIuNDI4MiAxMC4wMTY4QyAzMi40MjgyIDguODU3NjIgMzEuNTc3NyA3LjkyOSAzMC40NDMyIDcuOTI5QyAyOS4zMDg2IDcuOTI5IDI4LjQ1ODIgOC44NTc2MiAyOC40NTgyIDEwLjAxNjhDIDI4LjQ1ODIgMTEuMDQ2IDI5LjIyIDExLjkxMzggMzAuMTc4MiAxMi4wODE3QyAzMi41NTYgMTYuNTYwOSAzMi43NDcgMjIuMDcyIDMwLjYxMDYgMjYuNzU4MkMgMjguODEwMyAzMC41Nzc2IDI1Ljc5MjYgMzMuMzkyNiAyMi4wNjY1IDM0LjY4NTFMIDIyLjA2MTQgMzQuNjg2OUMgMTguMzMzNiAzNi4wNTYxIDE0LjMxODcgMzUuNzUyOSAxMC44MDE2IDMzLjkyNUMgMy40ODA1NiAzMC4xMTczIDAuNDQ4ODY2IDIwLjY1NjIgNC4wNDkgMTIuODYzNEMgNi4xODA3MiA4LjI2NDQ2IDEwLjM0NjEgNS4wODY4MiAxNS4wMTE4IDQuMjc4MDFDIDE1LjM1NTUgNC42NTMxMSAxNS44MzM0IDQuODc1NjQgMTYuMzc3NCA0Ljg3NTY0QyAxNy41MTE5IDQuODc1NjQgMTguMzYyMyAzLjk0NzAyIDE4LjM2MjMgMi43ODc4M0MgMTguMzYyMyAxLjYyODYzIDE3LjUxMTkgMC43MDAwMTIgMTYuMzc3NCAwLjcwMDAxMkMgMTUuNjQyMiAwLjcwMDAxMiAxNS4wNjk3IDEuMTA1NDggMTQuNzI0MyAxLjY1OTA1QyA5LjE4NDAxIDIuNTcxNzcgNC4yNTYxMiA2LjM1MTg5IDEuNzQ2NDEgMTEuNzU2TCAxLjc0NTM4IDExLjc1ODNDIC0wLjI1MDY3OCAxNi4xNjMxIC0wLjU1MDIxIDIxLjE2OTcgMC45MjY4NzkgMjUuODA5OEMgMi40MTU1NyAzMC40Njk1IDUuNTQzNTggMzQuMTg0OSA5LjY0MTQzIDM2LjM5ODRMIDkuNjQ2NzcgMzYuNDAxMkMgMTIuMDk2OSAzNy42NTkzIDE0LjczMDEgMzguMjk3OSAxNy4yNTY1IDM4LjNDIDIzLjY3NiAzOC4zIDI5Ljg2MTggMzQuNDk4OSAzMi44NDAzIDI4LjAyMDNDIDM1LjM1MjQgMjIuNTMyNiAzNS4xNTA4IDE2LjAzMzggMzIuMzA1NSAxMC43NjE2WiIvPjxwYXRoIGlkPSJwYXRoNF9maWxsIiBkPSJNIDE5LjI5MTUgMi41QyAxOS4yOTE1IDMuODgwNzEgMTguMTcyMiA1IDE2Ljc5MTUgNUMgMTUuNDEwOCA1IDE0LjI5MTUgMy44ODA3MSAxNC4yOTE1IDIuNUMgMTQuMjkxNSAxLjExOTI5IDE1LjQxMDggMCAxNi43OTE1IDBDIDE4LjE3MjIgMCAxOS4yOTE1IDEuMTE5MjkgMTkuMjkxNSAyLjVaIi8+PHBhdGggaWQ9InBhdGg1X2ZpbGwiIGQ9Ik0gMzMuMjkxNSAxMC41QyAzMy4yOTE1IDExLjg4MDcgMzIuMTcyMiAxMyAzMC43OTE1IDEzQyAyOS40MTA4IDEzIDI4LjI5MTUgMTEuODgwNyAyOC4yOTE1IDEwLjVDIDI4LjI5MTUgOS4xMTkyOSAyOS40MTA4IDggMzAuNzkxNSA4QyAzMi4xNzIyIDggMzMuMjkxNSA5LjExOTI5IDMzLjI5MTUgMTAuNVoiLz48cGF0aCBpZD0icGF0aDZfZmlsbCIgZD0iTSAxMC4yOTE1IDI1LjVDIDEwLjI5MTUgMjYuODgwNyA5LjE3MjIyIDI4IDcuNzkxNSAyOEMgNi40MTA3OSAyOCA1LjI5MTUgMjYuODgwNyA1LjI5MTUgMjUuNUMgNS4yOTE1IDI0LjExOTMgNi40MTA3OSAyMyA3Ljc5MTUgMjNDIDkuMTcyMjIgMjMgMTAuMjkxNSAyNC4xMTkzIDEwLjI5MTUgMjUuNVoiLz48L2RlZnM+PC9zdmc+' );
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
