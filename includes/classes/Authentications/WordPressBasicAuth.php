<?php
/**
 * WP basic auth functionality
 *
 * @package  distributor
 */

namespace Distributor\Authentications;

use \Distributor\Authentication as Authentication;

/**
 * This auth type is simple username/password WP style
 */
class WordPressBasicAuth extends Authentication {
	/**
	 * Auth handler slug
	 *
	 * @var string
	 */
	public static $slug = 'user-pass';

	/**
	 * Does auth require creds or not
	 *
	 * @var boolean
	 */
	public static $requires_credentials = true;

	/**
	 * Pretty auth label to use
	 *
	 * @var string
	 */
	public static $label = 'Username/Password';

	/**
	 * Setup class
	 *
	 * @param array $args Assoc array of args.
	 */
	public function __construct( $args ) {
		parent::__construct( $args );

		if ( isset( $this->password ) && isset( $this->username ) ) {
			$this->base64_encoded = base64_encode( $this->username . ':' . $this->password ); // @codingStandardsIgnoreLine valid use of base64_encode
		}

		if ( empty( $this->base64_encoded ) ) {
			$this->base64_encoded = false;
		}
	}

	/**
	 * Output credentials form for this auth type
	 *
	 * @param  array $args Array of args.
	 * @since  0.8
	 */
	public static function credentials_form( $args = array() ) {
		if ( empty( $args['username'] ) ) {
			$args['username'] = '';
		}
		?>
		<div class="external-connection-wizard card">
			<h3><?php esc_html_e( 'Remote Connection Wizard', 'distributor' ); ?></h3>
			<span class="description">
				<?php esc_html_e( 'Auto-connect to an external site by authorizing Distributor with your login.', 'distributor' ); ?><br />
				<?php esc_html_e( 'Note: the remote site must also be running Distributor verion 1.5 or higher.', 'distributor' ); ?>
			</span><br />
			<label for="dt_external_site_url"><?php esc_html_e( 'External Site Url', 'distributor' ); ?></label><br>
			<input type="text" name="dt_external_connection_auth[site_url]" data-auth-field="dt_external_site_url" value="" class="widefat" id="dt_external_site_url" placeholder="https://remotesite.com" autocomplete="off" value="">
			<p>
				<button class="button button-large establish-connection-button button-primary">
					<?php esc_html_e( 'Authorize Connection', 'distributor' ); ?>
				</button>
			</p>
		</div>
		<div class="external-connection-setup">

			<h3><?php esc_html_e( 'Edit configuration', 'distributor' ); ?></h3>

			<label for="dt_username"><?php esc_html_e( 'Username', 'distributor' ); ?></label><br>
			<input type="text" name="dt_external_connection_auth[username]" data-auth-field="username" value="<?php echo esc_attr( $args['username'] ); ?>" class="auth-field" id="dt_username" autocomplete="off" >

			<span class="description"><?php esc_html_e( 'A username from the external WordPress site to connect with. For full functionality, this needs to be a user with an administrator role.', 'distributor' ); ?></span>

			<p>
				<label for="dt_username"><?php esc_html_e( 'Password', 'distributor' ); ?> <?php
				if ( ! empty( $args['base64_encoded'] ) ) :
					?>
					<a class="change-password" href="#"><?php esc_html_e( '(Change)', 'distributor' ); ?></a><?php endif; ?></label><br>

				<?php if ( ! empty( $args['base64_encoded'] ) ) : ?>
				<input disabled type="password" name="dt_external_connection_auth[password]" value="ertdfweewefewwe" data-auth-field="password" class="auth-field" id="dt_password">
				<?php else : ?>
					<input type="password" name="dt_external_connection_auth[password]" data-auth-field="password" class="auth-field" id="dt_password" autocomplete="off" >
				<?php endif; ?>

				<span class="description">
					<?php
					$plugin_link = 'https://wordpress.org/plugins/application-passwords/';

					/* translators: %s: Application Passwords plugin URL */
					printf( wp_kses_post( __( '<strong>Important:</strong> We strongly recommend using the <a href="%s">Application Passwords</a> plugin on the site you are connecting to in order to create a unique password for this connection. This helps limit the use of your primary password and will allow you to revoke access in the future if needed.', 'distributor' ) ), esc_url( $plugin_link ) );
					?>
			</p>
		</div>

		<?php
	}

	/**
	 * Prepare credentials for this auth type
	 *
	 * @param  array $args Creds to prepare.
	 * @since  0.8
	 * @return array
	 */
	public static function prepare_credentials( $args ) {
		$auth = array();

		if ( ! empty( $args['username'] ) ) {
			$auth['username'] = sanitize_text_field( $args['username'] );
		}

		if ( ! empty( $args['base64_encoded'] ) ) {
			$auth['base64_encoded'] = sanitize_text_field( $args['base64_encoded'] );
		}

		if ( ! empty( $args['password'] ) ) {
			$auth['base64_encoded'] = base64_encode( $args['username'] . ':' . $args['password'] ); // @codingStandardsIgnoreLine valid use of base64_encode
		}

		/**
		 * Filter the authorization credentials prepared before saving.
		 *
		 * @since 1.0
		 *
		 * @param array  $auth The credentials to be saved.
		 * @param array  $args The arguments originally passed to .prepare_credentials'.
		 * @param string $slug The authorization handler type slug.
		 */
		return apply_filters( 'dt_auth_prepare_credentials', $auth, $args, self::$slug );
	}

	/**
	 * Add basic auth headers to get args
	 *
	 * @param  array $args Args to format.
	 * @param  array $context Current context.
	 * @since  0.8
	 * @return array
	 */
	public function format_get_args( $args = array(), $context = array() ) {
		if ( ! empty( $this->username ) && ! empty( $this->base64_encoded ) ) {
			if ( empty( $args['headers'] ) ) {
				$args['headers'] = array();
			}

			$args['headers']['Authorization'] = 'Basic ' . $this->base64_encoded;
		}

		return parent::format_get_args( $args, $context );
	}

	/**
	 * Add basic auth headers to post args
	 *
	 * @param  array $args Args to format.
	 * @param  array $context Current context.
	 * @since  0.8
	 * @return array
	 */
	public function format_post_args( $args, $context = array() ) {
		if ( ! empty( $this->username ) && ! empty( $this->base64_encoded ) ) {
			if ( empty( $args['headers'] ) ) {
				$args['headers'] = array();
			}

			$args['headers']['Authorization'] = 'Basic ' . $this->base64_encoded;
		}

		return parent::format_post_args( $args, $context );
	}
}
