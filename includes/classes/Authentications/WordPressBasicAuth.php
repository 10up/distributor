<?php

namespace Syndicate\Authentications;
use \Syndicate\Authentication as Authentication;

/**
 * This auth type is simple username/password WP style
 */
class WordPressBasicAuth extends Authentication {
	static $slug = 'user-pass';
	static $requires_credentials = true;
	static $label = 'Username/Password';

	/**
	 * Output credentials form for this auth type
	 * 
	 * @param  array  $args
	 * @since  1.0
	 */
	static function credentials_form( $args = array() ) {
		if ( empty( $args['username'] ) ) {
			$args['username'] = '';
		}

		if ( empty( $args['password'] ) ) {
			$args['password'] = '';
		}
		?>
		<p>
			<label for="sy_username"><?php esc_html_e( 'Username', 'syndicate' ); ?></label><br>
			<input type="text" name="sy_external_connection_auth[username]" data-auth-field="username" value="<?php echo esc_attr( $args['username'] ); ?>" class="auth-field" id="sy_username">
			<span class="description"><?php esc_html_e( 'We need a username (preferrably with an Administrator role) to the WordPress site with the API.', 'syndicate' ); ?>
		</p>
		<p>
			<label for="sy_username"><?php esc_html_e( 'Password', 'syndicate' ); ?></label><br>
			<input type="password" name="sy_external_connection_auth[password]" value="<?php echo esc_attr( $args['password'] ); ?>" data-auth-field="password" class="auth-field" id="sy_password">
		</p>
		<?php
	}

	/**
	 * Prepare credentials for this auth type
	 * 
	 * @param  array $args
	 * @since  1.0
	 * @return array
	 */
	static function prepare_credentials( $args ) {
		$auth = array();

		if ( ! empty( $args['username'] ) ) {
			$auth['username'] = sanitize_text_field( $args['username'] );
		}

		if ( ! empty( $args['password'] ) ) {
			$auth['password'] = sanitize_text_field( $args['password'] );
		}

		return apply_filters( 'sy_auth_prepare_credentials', $args, $this );
	}

	/**
	 * Add basic auth headers to get args
	 * 
	 * @param  array $args
	 * @param  array  $context
	 * @since  1.0
	 * @return array
	 */
	public function format_get_args( $args, $context = array() ) {
		if ( ! empty( $this->username ) && ! empty( $this->password ) ) {
			if ( empty( $args['headers'] ) ) {
				$args['headers'] = array();
			}
			
			$args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->username . ':' . $this->password );
		}

		return parent::format_get_args( $args, $context );
	}

	/**
	 * Add basic auth headers to post args
	 * 
	 * @param  array $args
	 * @param  array  $context
	 * @since  1.0
	 * @return array
	 */
	public function format_post_args( $args, $context = array() ) {
		if ( ! empty( $this->username ) && ! empty( $this->password ) ) {
			if ( empty( $args['headers'] ) ) {
				$args['headers'] = array();
			}

			$args['headers']['Authorization'] = 'Basic ' . base64_encode( $this->username . ':' . $this->password );
		}

		return parent::format_post_args( $args, $context );
	}
}
