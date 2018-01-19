<?php

namespace Distributor\Authentications;

use \Distributor\Authentication as Authentication;

/**
 * This auth type is simple username/password WP style
 */
class WordPressDotcomOauth2Authentication extends Authentication {
	static $slug                 = 'secret-key';
	static $requires_credentials = true;
	static $label                = 'Client ID/Secret';

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
			<input type="text" name="dt_external_connection_auth[client_secret]" data-auth-field="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="widefat auth-field" id="dt_client_secret">
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
