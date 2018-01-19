<?php

namespace Distributor\Authentications;

use \Distributor\Authentication as Authentication;

/**
 * This auth type is simple username/password WP style
 */
class WordPressDotcomOauth2Authentication extends Authentication {
	static $slug                 = 'dotcom-oauth2';
	static $requires_credentials = true;
	static $label                = 'WordPress.com Oauth2';

	public function __construct( $args ) {
		parent::__construct( $args );
	}

	/**
	 * Output credentials form for this auth type
	 *
	 * @param  array $args
	 * @since  0.8
	 */
	static function credentials_form( $args = array() ) {
		global $wp;
		$client_id = isset( $args['client_id'] ) ? $args['client_id'] : '';
		?>
		<p>
			<label for="dt_client_id"><?php esc_html_e( 'Client ID', 'distributor' ); ?></label><br>
			<input type="text" name="dt_external_connection_auth[client_id]" data-auth-field="client_id" value="<?php echo esc_attr( $client_id ); ?>" class="widefat auth-field" id="dt_client_id">
		</p>
		<?php
		$client_secret = isset( $args['client_secret'] ) ? $args['client_secret'] : '';
		?>
		<p>
			<label for="dt_client_secret"><?php esc_html_e( 'Client Secret', 'distributor' ); ?></label><br>
			<input type="password" name="dt_external_connection_auth[client_secret]" data-auth-field="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="widefat auth-field" id="dt_client_secret">
		</p>
		<?php
				error_log(json_encode(admin_url( 'post-new.php?post_type=dt_ext_connection' ), JSON_PRETTY_PRINT));

		$redirect_url = admin_url( 'post-new.php?post_type=dt_ext_connection' );
		?>
		<p>
			<input type="hidden" name="dt_external_connection_auth[redirect_url]" data-auth-field="redirect_url" value="<?php echo esc_attr( $redirect_url ); ?>" class="widefat  auth-field" id="dt_redirect_url">
		</p>

		<?php
	}

	/**
	 * Prepare credentials for this auth type
	 *
	 * @param  array $args
	 * @since  0.8
	 * @return array
	 */
	static function prepare_credentials( $args ) {
		$auth = array();

		if ( ! empty( $args['client_id'] ) ) {
			$auth['client_id'] = sanitize_text_field( $args['client_id'] );
		}

		if ( ! empty( $args['client_secret'] ) ) {
			$auth['client_secret'] = sanitize_text_field( $args['client_secret'] );
		}

		if ( ! empty( $args['password'] ) ) {
			$auth['redirect_url'] = sanitize_text_field( $args['redirect_url'] );
		}

		return apply_filters( 'dt_auth_prepare_credentials', $auth, $args, self::$slug );
	}


	/**
	 * Add basic auth headers to get args
	 *
	 * @param  array $args
	 * @param  array $context
	 * @since  0.8
	 * @return array
	 */
	public function format_get_args( $args, $context = array() ) {
		return parent::format_get_args( $args, $context );
	}

	/**
	 * Add basic auth headers to post args
	 *
	 * @param  array $args
	 * @param  array $context
	 * @since  0.8
	 * @return array
	 */
	public function format_post_args( $args, $context = array() ) {
		return parent::format_post_args( $args, $context );
	}
}

/**
 * The first thing you need to do is create a new WordPress.com Application, this will give you a chance to describe your application and how we should communicate with it. You should give your app the same title as your website as that information is used in the login form users see. Once configured you will receive your CLIENT ID and CLIENT SECRET to identify your app.

https://developer.wordpress.com/apps/

Production Domain Name: e.g http://exampledomain.com/ Just enter exampledomain.com
espnfivethirtyeight.wordpress.com
REST API Client ID :
52828
REST API Client Secret :
uyp8IZkCNubX8QTNJiCeN3l0xZPrKH5zoyyVYvdJqakcnRAdDxhv1gZoT60qtO7f
REST API Redirect URI :


 */
