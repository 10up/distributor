<?php

namespace Distributor\Authentications;

use \Distributor\Authentication as Authentication;

/**
 * Enables WordPress.com Oauth2 authentication.
 */
class WordPressDotcomOauth2Authentication extends Authentication {
	static $slug                 = 'dotcom-oauth2';
	static $requires_credentials = true;
	static $label                = 'WordPress.com Oauth2';

	const REQUEST_TOKEN_URL  = 'https://public-api.wordpress.com/oauth2/token';
	const AUTHORIZE_URL      = 'https://public-api.wordpress.com/oauth2/authorize';
	const VALIDATE_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token-info';
	const ACCESS_TOKEN_KEY   = 'rest_api_access_token';
	const API_CLIENT_ID      = 'rest_api_client_id';
	const API_CLIENT_SECRET  = 'rest_api_client_secret';
	const API_REDIRECT_URI   = 'rest_api_redirect_uri';

	public function __construct( $args ) {
		parent::__construct( $args );
	}

	/**
	 * Output the credentials form.
	 *
	 * @param  array $args
	 * @since  1.1.0
	 */
	static function credentials_form( $args = array() ) {

		// Check if we need to display the form, or request a token?
		$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : false; // Input var okay. WPCS: CSRF ok.
		if ( ! empty( $code ) ) {
			self::fetch_access_token( $code );
		}
		$saved_access_token = self::get_authentication_option_by_key( self::ACCESS_TOKEN_KEY );
		$is_valid_token     = self::is_valid_token();
		$update_credentials = isset( $_GET['updatecredentials'] ); // Input var okay. WPCS: CSRF ok.
		$client_id          = isset( $args[ self::API_CLIENT_ID ] ) ? $args[ self::API_CLIENT_ID ] : '';
		$client_secret      = isset( $args[ self::API_CLIENT_SECRET ] ) ? $args[ self::API_CLIENT_SECRET ] : '';
		$screen             = get_current_screen();
		$is_adding          = isset( $screen->action ) && 'add' === $screen->action;
		$redirect_uri       = esc_url(
			( is_ssl() ? 'https://' : 'http://' ) .
			sanitize_text_field( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' ) . // Input var okay. WPCS: CSRF ok.
			sanitize_text_field( isset( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : '' ) . // WPCS: input var ok.
			'?' .
			sanitize_text_field( isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '' ) // WPCS: input var ok.
		);
		$args[ self::API_REDIRECT_URI ] = $redirect_uri;
		if (
			$update_credentials ||
			! $is_valid_token && (
				empty( $saved_access_token ) ||
				empty( $client_id ) ||
				empty( $client_secret ) ||
				empty( $redirect_uri ) ||
				empty( $code )
			)
		) {
		?>
			<p>
			<?php esc_html_e( 'To connect, first ', 'distributor' ); ?>
			<a href="https://developer.wordpress.com/apps/"><?php esc_html_e( 'create an application with the WordPress.com applications manager', 'distributor' ); ?></a>.<br />
			<?php esc_html_e( 'Use the following redirect URL when creating your application: ', 'distributor' ); ?>
			<strong><?php echo esc_url( admin_url( 'post.php' ) ); ?></strong>
			</p>
			<?php
			/**
			 * On the new connection screen, at first only show a button to save the connection. Oauth requires a return redirect
			 * and we need to save to generate a post id we can redirect back to before continuing.
			 */
			?>
			<p class='oauth_begin_authentication_wrapper<?php echo ( ! $is_adding ? ' hidden' : '' ); ?>'>
			<button name="save" type="button" class="button button-primary button-large" id="begin-authorization"><?php esc_attr_e( 'Authorize Connection', 'distributor' ); ?></button>
			</p>
			<?php
			self::credentials_partial( $args, $is_adding );

		} else {
		?>
		<div id="message" class="oauth-connection-established"><p><span class="message-header">&#10003<?php esc_html_e( 'Connection Authorized', 'distributor' ); ?></span><br/><a href="<?php echo esc_url( $redirect_uri . '&updatecredentials=1' ); ?>"><?php esc_html_e( 'Update/change credentials.', 'distributor' ); ?></a></p></div>
		<?php
			self::credentials_partial( $args, true );
		?>
		<script type="text/javascript">
			// Remove the code credentials from the URL to prevent refresh from initiating a new flow.
			window.history.pushState( {}, window.location.title, window.location.href.split( '&code=' )[0] ) ;
		</script>
		<?php
		}
	}

	/**
	 * Helper function to output the credentials section of the authorization form.
	 *
	 * @param array   $args        The authentication arguments.
	 * @param boolean $hidden      Should the entire section be hidden?
	 */
	static function credentials_partial( $args, $hidden = false ) {

		$client_id     = isset( $args[ self::API_CLIENT_ID ] ) ? $args[ self::API_CLIENT_ID ] : '';
		$client_secret = isset( $args[ self::API_CLIENT_SECRET ] ) ? $args[ self::API_CLIENT_SECRET ] : '';
		$redirect_uri  = isset( $args[ self::API_REDIRECT_URI ] ) ? $args[ self::API_REDIRECT_URI ] : '';
	?>
			<div class="oauth_authentication_details_wrapper<?php echo ( $hidden ? ' hidden' : '' ); ?>">
				<h3 >
					<?php esc_html_e( 'WordPress.com Application Client ID and Client Secret: ', 'distributor' ); ?>
				</h3>
				<p>
					<label for="dt_client_id"><?php esc_html_e( 'Client ID', 'distributor' ); ?></label><br />
					<input type="text" name="dt_external_connection_auth[client_id]" data-auth-field="client_id" value="<?php echo esc_attr( $client_id ); ?>" class="regular-text auth-field" id="dt_client_id"><br />

					<label for="dt_client_secret"><?php esc_html_e( 'Client Secret', 'distributor' ); ?></label><br />
					<input type="password" name="dt_external_connection_auth[client_secret]" data-auth-field="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="regular-text auth-field" id="dt_client_secret">
				</p>
					<input type="hidden" name="dt_external_connection_auth[redirect_uri]" data-auth-field="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>" class="regular-text  auth-field" id="dt_redirect_uri">
				<input name="save" type="submit" class="button button-primary button-large" id="create-connection" value="<?php esc_attr_e( 'Authorize Connection with WordPress.com', 'distributor' ); ?>">
			</div>
	<?php
	}

	/**
	 * Helper function extract a single option by key.
	 */
	static function get_authentication_option_by_key( $key ) {
		global $post;
		$external_connection_id = $post ? $post->ID : false;

		if ( $external_connection_id ) {
			$current_values = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
			if ( isset( $current_values[ $key ] ) ) {
				return $current_values[ $key ];
			}
		}
		return false;

	}

	/**
	 * Helper function extract a single option by key.
	 */
	static function get_authentication_options() {
		global $post;
		$external_connection_id = $post ? $post->ID : false;
		if ( $external_connection_id ) {
			return get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
		}
		return false;
	}

	/**
	 * Helper function to set a single option by key.
	 */
	static function set_authentication_option_by_key( $key, $value ) {
		global $post;
		$external_connection_id = $post ? $post->ID : false;

		if ( $external_connection_id ) {
			$current_values         = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
			$current_values[ $key ] = $value;
			update_post_meta( $external_connection_id, 'dt_external_connection_auth', $current_values );
		}
	}

	/**
	 * Prepare credentials for this auth type
	 *
	 * @param  array $args
	 * @since  1.1.0
	 * @return array
	 */
	static function prepare_credentials( $args ) {
		$auth = array();

		if ( ! empty( $args['client_id'] ) ) {
			$auth[ self::API_CLIENT_ID ] = sanitize_text_field( $args['client_id'] );
		}

		if ( ! empty( $args['client_secret'] ) ) {
			$auth[ self::API_CLIENT_SECRET ] = sanitize_text_field( $args['client_secret'] );
		}

		if ( ! empty( $args['redirect_uri'] ) ) {
			$auth[ self::API_REDIRECT_URI ] = sanitize_text_field( $args['redirect_uri'] );
		}

		if ( ! empty( $args['redirect_uri'] ) ) {
			$auth[ self::API_REDIRECT_URI ] = sanitize_text_field( $args['redirect_uri'] );
		}

		return apply_filters( 'dt_auth_prepare_credentials', $auth, $args, self::$slug );
	}

	/**
	 * Store pre-sanizited auth credentials in DB.
	 *
	 * If we don't have an access token, proceed with the auth flow.
	 *
	 * @param int   $external_connection_id
	 * @param array $args
	 * @since 0.8
	 */
	public static function store_credentials( $external_connection_id, $args ) {

		$current_values = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );

		// Don't attempt to authorize blank values.
		if (
			! isset( $args[ self::API_CLIENT_ID ] ) ||
			'' === $args[ self::API_CLIENT_ID ] ||
			! isset( $args[ self::API_CLIENT_SECRET ] ) ||
			'' === $args[ self::API_CLIENT_SECRET ]
		) {
			return;
		}

		if (
			empty( $access_token ) ||
			$current_values[ self::API_CLIENT_ID ] !== $args[ self::API_CLIENT_ID ] ||
			$current_values[ self::API_CLIENT_SECRET ] !== $args[ self::API_CLIENT_SECRET ]
		) {
			update_post_meta( $external_connection_id, 'dt_external_connection_auth', $args );
			self::get_authorization_code();
		} else {
		$access_token = isset( $current_values[ self::ACCESS_TOKEN_KEY ] ) ? $current_values[ self::ACCESS_TOKEN_KEY ] : '';
			$args[ self::ACCESS_TOKEN_KEY ] = $access_token;
			update_post_meta( $external_connection_id, 'dt_external_connection_auth', $args );
		}
	}


	/**
	 * Add basic auth headers to get args.
	 *
	 * @param  array $args
	 * @param  array $context
	 * @since  1.1.0
	 * @return array
	 */
	public function format_get_args( $args, $context = array() ) {
		$saved_access_token = isset( $this->{self::ACCESS_TOKEN_KEY} ) ?
			$this->{self::ACCESS_TOKEN_KEY} :
			false;

		if ( $saved_access_token ) {
			$args['headers'] = array(
				'Authorization' => 'Bearer ' . $saved_access_token,
			);
		}

		return parent::format_get_args( $args, $context );
	}

	/**
	 * Add basic auth headers to post args.
	 *
	 * @param  array $args
	 * @param  array $context
	 * @since  1.1.0
	 * @return array
	 */
	public function format_post_args( $args, $context = array() ) {
		$saved_access_token = isset( $this->{self::ACCESS_TOKEN_KEY} ) ?
			$this->{self::ACCESS_TOKEN_KEY} :
			false;

		if ( $saved_access_token ) {
			$args['headers'] = array(
				'Authorization' => 'Bearer ' . $saved_access_token,
			);
		}
		return parent::format_post_args( $args, $context );
	}

	/**
	 * Authorize the request using the secret key and save the access token.
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public static function fetch_access_token( $code ) {
		global $post;

		$external_connection_id = $post ? $post->ID : false;

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id     = $options[ self::API_CLIENT_ID ];
		$client_secret = $options[ self::API_CLIENT_SECRET ];
		$redirect_uri  = $options[ self::API_REDIRECT_URI ];

		if (
			empty( $client_id ) ||
			empty( $client_secret ) ||
			empty( $redirect_uri ) ||
			empty( $code ) ||
			! $external_connection_id
		) {
			self::log_authentication_error( ' Admin Settings form input date not saved. Please try saving the credentials again. ' );
			return false;
		}

		try {
			$params = array(
				'client_id'     => $client_id,
				'client_secret' => $client_secret,
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				// Note: this redirect must match request request request data
				'redirect_uri'  => $redirect_uri,
			);

			$args = array(
				'body' => $params,
			);

			$response = wp_remote_post( esc_url_raw( self::REQUEST_TOKEN_URL ), $args );

			if ( is_wp_error( $response ) ) {
				self::log_authentication_error( ' fetch_access_token() Failed -- ' . $response->get_error_message() );
				return false;
			}

			$response_body = wp_remote_retrieve_body( $response );
			$auth          = json_decode( $response_body );

			if ( empty( $auth->access_token ) ) {
				self::log_authentication_error( ' fetch_access_token() Failed -- ' . $response_body );
				return false;
			}

			self::set_authentication_option_by_key( self::ACCESS_TOKEN_KEY, $auth->access_token );
			return true;

		} catch ( \Exception $ex ) {
			self::log_authentication_error( ' fetch_access_token() Failed -- ' . $ex->getMessage() );
			return false;
		}
	}

	/**
	 * Authorise the request using the secret key and save the access token
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public static function get_authorization_code() {

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id    = $options[ self::API_CLIENT_ID ];
		$redirect_uri = $options[ self::API_REDIRECT_URI ];

		if ( empty( $client_id ) || empty( $redirect_uri ) ) {
			self::log_authentication_error( ' Admin Settings Form values not saved. Please try saving the credentials again. ' );
			return false;
		}
		try {

			$args = array(
				'response_type' => 'code',
				'scope'         => 'global',
				'client_id'     => $client_id,
				'redirect_uri'  => $redirect_uri,
			);

			$query_param   = http_build_query( $args );
			$authorize_url = self::AUTHORIZE_URL . '?' . $query_param;

			// Allow wp_safe_redirect to redirect to the .com authorization endpoint.
			add_filter( 'allowed_redirect_hosts' , function( $content ){
				$content[] = 'public-api.wordpress.com';
				return $content;
			} );

			wp_safe_redirect( esc_url_raw( $authorize_url ) );
			exit;

		} catch ( \Exception $ex ) {

			self::log_authentication_error( ' fetch_access_token() Failed -- ' . $ex->getMessage() );
			return false;
		}

	}

	/**
	 * Returns if the saved token is valid or not
	 *
	 * @since 2015-08-14
	 *
	 * @version 2015-08-14 Archana Mandhare - PPT-5077
	 *
	 * @return bool - true if token is valid else false
	 *
	 */
	public static function is_valid_token( $count = 1 ) {

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id    = isset( $options[ self::API_CLIENT_ID ] ) ? $options[ self::API_CLIENT_ID ] : '';
		$access_token = isset( $options[ self::ACCESS_TOKEN_KEY ] ) ? $options[ self::ACCESS_TOKEN_KEY ] : '';

		if ( empty( $client_id ) || empty( $access_token ) ) {
			return false;
		}

		$query = array(
			'client_id' => (string) $client_id,
			'token'     => $access_token,
		);

		$params = http_build_query( $query );

		/**
		 * Recommended function is vip_safe_wp_remote_get() but since it has a max timeout of 3 secs which
		 * is not feasible since the response time is way ahead 3 secs.
		 */
		// @codingStandardsIgnoreStart
		$args = array(
			'timeout' => 500,
		);
		$response = wp_remote_get( esc_url_raw( self::VALIDATE_TOKEN_URL ) . '?' . $params, $args );
		// @codingStandardsIgnoreEnd

		if ( is_wp_error( $response ) ) {

			self::log_authentication_error( 'Failed to validate token giving error ' . $response->get_error_message() );
			$count ++;
			if ( $count <= 3 ) {
				$this->is_valid_token( $count );
			}

			return false;
		}
		$response_body = wp_remote_retrieve_body( $response );

		if ( ! empty( $response_body ) ) {
			$token_info = json_decode( $response_body, true );

			if ( ! empty( $token_info['client_id'] ) && $token_info['client_id'] === $client_id ) {
				return true;
			}
		}

		return false;

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
