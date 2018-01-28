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

	const REST_BASE_URL = 'https://public-api.wordpress.com/rest/v1.1/sites/';
	const REQUEST_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token';
	const AUTHORIZE_URL = 'https://public-api.wordpress.com/oauth2/authorize';
	const VALIDATE_TOKEN_URL = 'https://public-api.wordpress.com/oauth2/token-info';
	const REST_URL_HOST = 'public-api.wordpress.com';
	const COOKIE_DOMAIN = '.vip.local';

	const access_token_key = 'rest_api_access_token';
	const api_domain = 'rest_api_domain';
	const api_client_id = 'rest_api_client_id';
	const api_client_secret = 'rest_api_client_secret';
	const api_redirect_uri = 'rest_api_redirect_uri';
	const api_xmlrpc_username = 'xmlrpc_username';
	const api_xmlrpc_password = 'xmlrpc_password';
	const show_form = 'show_data_import_form';

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

		// Check if we need to display the form, or request a token?
		$token_created = false;
		$code          = isset( $_GET['code'] ) ? sanitize_text_field( $_GET['code'] ) : false;
		if ( ! empty( $code ) ) {
			$token_created = self::fetch_access_token( $code );
		}
		$saved_access_token = self::get_authentication_option_by_key( self::access_token_key );
		$is_valid_token     = self::is_valid_token();

		$update_credentials = isset( $_GET['updatecredentials'] );
		$authorize_url  = '';

		$client_id = isset( $args[ self::api_client_id ] ) ? $args[ self::api_client_id ] : '';
		$client_secret = isset( $args[ self::api_client_secret ] ) ? $args[ self::api_client_secret ] : '';
		$redirect_uri =  esc_url( ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] .  $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'] );
		$screen = get_current_screen();
		$is_adding = isset( $screen->action ) && 'add' === $screen->action;
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
			<a href="https://developer.wordpress.com/apps/"><?php esc_html_e( 'Create an app to connect to WordPress.com', 'distributor' ); ?></a>
			</p>
			<?php
			/**
			 * On the new connection screen, only show a button to save the connection. Oauth requires a return redirect
			 * and we need to save to generate a post id we can redirect back to before continuing.
			 */
			if ( $is_adding ) {
			?>
			<p>
			<input name="save" type="submit" class="button button-primary button-large" id="create-connection" value="<?php esc_attr_e( 'Begin Oauth Authorization', 'distributor' ); ?>">
			<p>
			<?php } else { ?>
				<label for="dt_client_id"><?php esc_html_e( 'Client ID', 'distributor' ); ?></label><br>
				<input type="text" name="dt_external_connection_auth[client_id]" data-auth-field="client_id" value="<?php echo esc_attr( $client_id ); ?>" class="widefat auth-field" id="dt_client_id">
			</p>
			<p>
				<label for="dt_client_secret"><?php esc_html_e( 'Client Secret', 'distributor' ); ?></label><br>
				<input type="password" name="dt_external_connection_auth[client_secret]" data-auth-field="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="widefat auth-field" id="dt_client_secret">
			</p>
				<input type="hidden" name="dt_external_connection_auth[redirect_uri]" data-auth-field="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>" class="widefat  auth-field" id="dt_redirect_uri">
			<input name="save" type="submit" class="button button-primary button-large" id="create-connection" value="<?php esc_attr_e( 'Authenticate Connection', 'distributor' ); ?>">
		<?php
			}
		} else {
		?>
		<div id="message" class="oauth-connection-established"><p><span class="message-header">&#10003<?php esc_html_e( 'Connection Established', 'distributor' ); ?></span><br/><a href="<?php echo esc_url( $redirect_uri . '&updatecredentials=1' ); ?>"><?php esc_html_e( 'Update credentials', 'distributor' ); ?></a></p></div>
		<input type="hidden" name="dt_external_connection_auth[client_id]" data-auth-field="client_id" value="<?php echo esc_attr( $client_id ); ?>" class="widefat auth-field" id="dt_client_id">
		<input type="hidden" name="dt_external_connection_auth[client_secret]" data-auth-field="client_secret" value="<?php echo esc_attr( $client_secret ); ?>" class="widefat auth-field" id="dt_client_secret">
		<input type="hidden" name="dt_external_connection_auth[redirect_uri]" data-auth-field="redirect_uri" value="<?php echo esc_attr( $redirect_uri ); ?>" class="widefat  auth-field" id="dt_redirect_uri">

		<script type="text/javascript">
			// Remove the code credentials from the URL to prevent refresh from initiating a new flow.
			window.history.replaceState( {}, window.location.title, window.location.href.split( '&code=' )[0] ) ;
		</script>
		<?php
		}
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
			$current_values = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
			$current_values[ $key ] = $value;
			update_post_meta( $external_connection_id, 'dt_external_connection_auth', $current_values );
		}
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
			$auth[ self::api_client_id ] = sanitize_text_field( $args['client_id'] );
		}

		if ( ! empty( $args['client_secret'] ) ) {
			$auth[ self::api_client_secret ] = sanitize_text_field( $args['client_secret'] );
		}

		if ( ! empty( $args['redirect_uri'] ) ) {
			$auth[ self::api_redirect_uri ] = sanitize_text_field( $args['redirect_uri'] );
		}

		return apply_filters( 'dt_auth_prepare_credentials', $auth, $args, self::$slug );
	}

	/**
	 * Store pre-sanizited auth credentials in DB
	 *
	 * @param int   $external_connection_id
	 * @param array $args
	 * @since 0.8
	 */
	public static function store_credentials( $external_connection_id, $args ) {

		$current_values = get_post_meta( $external_connection_id, 'dt_external_connection_auth', true );
		$access_token = get_post_meta( $external_connection_id, 'dt_external_connection_auth_access_token', true );
		update_post_meta( $external_connection_id, 'dt_external_connection_auth', $args );

		if (
			empty( $access_token ) ||
			! empty( array_diff( $current_values,$args ) )
		)
		{
			self::get_authorization_code();
		}
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
		$saved_access_token = isset( $this->{self::access_token_key} ) ?
			$this->{self::access_token_key} :
			false;

		if ( $saved_access_token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $saved_access_token;
		}

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
		$saved_access_token = isset( $this->{self::access_token_key} ) ?
			$this->{self::access_token_key} :
			false;
		if ( $saved_access_token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $saved_access_token;
		}
		return parent::format_post_args( $args, $context );
	}

	/**
	 * Authorise the request using the secret key and save the access token
	 *
	 * @since 2015-07-06
	 *
	 * @version 2015-07-06 Archana Mandhare - PPT-5077
	 *
	 */
	public static function fetch_access_token( $code ) {
		global $post;

		$external_connection_id = $post ? $post->ID : false;

		$time = date( '[d/M/Y:H:i:s]' );

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id     = $options[ self::api_client_id ];
		$client_secret = $options[ self::api_client_secret ];
		$redirect_uri  = $options[ self::api_redirect_uri ];

		if ( empty( $client_id ) || empty( $client_secret ) || empty( $redirect_uri ) || empty( $code ) || ! $external_connection_id ) {

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
				'redirect_uri'  => self::get_authorization_redirect( $redirect_uri ),
			);

			$args = array(
				'timeout' => 500,
				'body'    => $params,
			);

			$response = wp_remote_post( esc_url_raw( self::REQUEST_TOKEN_URL ), $args );

			if ( is_wp_error( $response ) ) {

				self::log_authentication_error( ' fetch_access_token() Failed -- ' . $response->get_error_message() );

				return false;
			}

			$response_body = wp_remote_retrieve_body( $response );

			$auth = json_decode( $response_body );

			if ( empty( $auth->access_token ) ) {

				self::log_authentication_error( ' fetch_access_token() Failed -- ' . $response_body );

				return false;
			}

			self::set_authentication_option_by_key( self::access_token_key, $auth->access_token );

			return true;

		} catch ( \Exception $ex ) {

			self::log_authentication_error( ' fetch_access_token() Failed -- ' . $ex->getMessage() );

			return false;
		}

	}

	/**
	 * Generate the redirect url request for authorization
	 * @param  [type] $redirect_uri The redirect url
	 * @return string The redirect url
	 */
	public static function get_authorization_redirect( $redirect_uri ) {
		// to workaround wp security where cookie is useless due to wp oauth redirect trigger browser not passing cookie
		$url_parts = parse_url( $redirect_uri );
		if ( !empty( $url_parts['path'] ) && 'redirectme' === trim( $url_parts['path'], '/' ) ) {
			if ( !empty( $url_parts['query'] ) ) {
				$url_parts['query'] = $url_parts['query'] . '&';
			} else {
				$url_parts['query'] = '';
			}
			$url_parts['query'] = $url_parts['query'] . 'to=' . urlencode ( get_admin_url() . 'tools.php?page=data-import' );
			$redirect_uri = sprintf('%s://%s%s?%s', $url_parts['scheme'], $url_parts['host'], $url_parts['path'], $url_parts['query'] );
		}
		return $redirect_uri;
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

		$time = date( '[d/M/Y:H:i:s]' );

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id     = $options[ self::api_client_id ];
		$redirect_uri  = $options[ self::api_redirect_uri ];

		if ( empty( $client_id ) || empty( $redirect_uri ) ) {

			self::log_authentication_error( ' Admin Settings Form values not saved. Please try saving the credentials again. ' );

			return false;
		}
		try {

			$args = array(
				'response_type' => 'code',
				'scope'         => 'global',
				'client_id'     => $client_id,
				'redirect_uri'  => self::get_authorization_redirect( $redirect_uri ),
			);

			$query_param = http_build_query( $args );

			$authorize_url = self::AUTHORIZE_URL . '?' . $query_param;

			wp_redirect( esc_url_raw( $authorize_url ) );

			exit;

		} catch ( \Exception $ex ) {

			self::log_authentication_error( ' fetch_access_token() Failed -- ' . $ex->getMessage() );

			return false;
		}

	}

	/**
	 * Access the API end point to pull data based on the route being passed
	 * Always access the endpoint with request header
	 * containing Authorization Bearer access token
	 *
	 * @since 2015-07-14
	 *
	 * @version 2015-07-14 Archana Mandhare - PPT-5077
	 * @version 2015-11-30 Archana Mandhare - PMCVIP-177
	 *
	 * @param string $route - the endpoint name that needs to be called
	 * array $query_params array of query arguments that needs to be passed
	 *
	 * @return array The json data returned from the API end point
	 *
	 */
	public function access_endpoint( $route, $query_params = array(), $route_name = '' ) {

		$time = date( '[d/M/Y:H:i:s]' );

		$options = get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$domain     = $options[ self::api_domain];
		$saved_access_token = $options[ self::access_token_key];

		if ( empty( $domain ) ) {

			self::log_authentication_error( ' Domain is not set. Please try saving it from the settings form . -- ' . $route_name );

			return new \WP_Error( 'unauthorized_access', 'No Domain set. Please Try again. --' );
		}

		$route_name = ! empty( $route_name ) ? $route_name : $route;


		if ( empty( $saved_access_token ) ) {

			self::log_authentication_error( ' ERROR --  No saved access token. Access denied . -- ' . $route_name );

			return new \WP_Error( 'unauthorized_access', '  No access token. Please get access token. ' );

		}

		try {

			$headers = $this->_get_required_header();

			if ( ! empty( $query_params['post_id'] ) ) {
				$post_id = $query_params['post_id'];
				unset( $query_params['post_id'] );
			} else {
				$post_id = '';
			}

			if ( ! empty ( $query_params['slug'] ) ) {
				$slug = $query_params['slug'];
				unset( $query_params['slug'] );
			} else {
				$slug = '';
			}

			$query_params = $this->_get_query_params( $query_params );

			$api_url = $this->_get_api_url( $domain, $route, $query_params, $post_id, $slug );

			/**
			 * Do not remove the below comments @codingStandardsIgnoreStart and @codingStandardsIgnoreEnd
			 * Recommended function is vip_safe_wp_remote_get() but since it has a max timeout of 3 secs which
			 * is not feasible since the response time is way ahead 3 secs here and I am unable to fetch data
			 * if I use vip_safe_wp_remote_get()
			 */
			// @codingStandardsIgnoreStart
			$response = wp_remote_get( esc_url_raw( $api_url ), $headers );
			// @codingStandardsIgnoreEnd

			if ( empty( $response ) ) {
				self::log_authentication_error( $time . $api_url . ' $$$$  No Data returned. Please Try again. -- ' );

				return new \WP_Error( 'unauthorized_access', $api_url . '$$$$  No Data returned. Please Try again. --' );
			}

			$response = wp_remote_retrieve_body( $response );

			$data = json_decode( $response, true );

			if ( 200 !== $data['code'] ) {
				self::log_authentication_error( 'token ##### unauthorized_access for route ###### ' . $route_name . json_encode( $data ) . ' and api url = ' . $api_url );

				return new \WP_Error( 'unauthorized_access', $route_name . ' Failed with Exception - ' . $data['body']['message'] );
			}

			if ( array_key_exists( $route_name, $data['body'] ) ) {
				return $data['body'][ $route_name ];
			} else {
				$return_val = array( 0 => $data['body'] );

				return $return_val;
			}
		} catch ( \Exception $ex ) {
			self::log_authentication_error( 'API route Failed -- ' . $ex->getMessage() );
		}

		return false;

	}

	/**
	 * get the URL of the API based on the query params
	 *
	 * @since 2015-11-30
	 *
	 * @version 2015-11-30 Archana Mandhare - PMCVIP-177
	 *
	 * @param $domain string
	 * @param $route string
	 * @param $query_params string
	 * @param $post_id string
	 *
	 * @return string
	 */
	private function _get_api_url( $domain, $route, $query_params, $post_id = '', $slug = '' ) {

		// Look for special cases

		switch ( $route ) {
			case 'single-post':
				$url = trim( self::REST_BASE_URL, '/' ) . '/' . $domain . '/posts/' . $post_id . '/?' . $query_params;
				return $url;
			break;
			case 'post-slug':
				// /sites/$site/posts/slug:$post_slug
				$url = trim( self::REST_BASE_URL, '/' ) . '/' . $domain . '/posts/slug:' . $slug . '/?' . $query_params;
				return $url;
			break;
			default:
				if ( ! empty( $post_id ) && 'posts' === $route ) {
					return trim( self::REST_BASE_URL, '/' ) . '/' . $domain . '/' . trim( $route, '/' ) . '/' . $post_id . '/?' . $query_params;
				} else {
					return trim( self::REST_BASE_URL, '/' ) . '/' . $domain . '/' . trim( $route, '/' ) . '/?' . $query_params;
				}
		}
	}

	/**
	 * Return the header information that needs to be passed to the API endpoint
	 *
	 * @since 2015-07-14
	 *
	 * @version 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 * @return array The header data that needs to be passed to the API
	 *
	 */
	private function _get_required_header() {

		$args = array(
			'timeout' => 500,
		);

		$saved_access_token = get_authentication_option_by_key( self::access_token_key );

		if ( ! empty( $saved_access_token ) ) {
			$args = array(
				'timeout' => 500,
				'headers' => array(
					'authorization' => 'Bearer ' . $saved_access_token,
				),
			);
		}

		return $args;
	}

	/**
	 * Returns the query params the need to be passed to the API endpoint
	 *
	 * @since 2015-07-14
	 *
	 * @version 2015-07-14 Archana Mandhare - PPT-5077
	 *
	 * @param array $params array of query arguments that needs to be passed
	 *
	 * @return string The query string that should be passed to the API
	 *
	 */
	private function _get_query_params( $params = array() ) {

		$defaults = array( 'http_envelope' => 'true' );

		$query_params = wp_parse_args( $params, $defaults );

		return http_build_query( $query_params );

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

		$time = date( '[d/M/Y:H:i:s]' );

		$options = self::get_authentication_options();
		if ( ! $options ) {
			return false;
		}

		$client_id    = $options[ self::api_client_id ];
		$access_token = $options[ self::access_token_key ];

		if ( empty( $client_id ) || empty( $access_token ) ) {
			return false;
		}

		$query = array(
			'client_id' => (string) $client_id,
			'token'     => $access_token,
		);

		$params = http_build_query( $query );

		$args = array(
			'timeout' => 500,
		);

		/**
		 * Do not remove the below comments @codingStandardsIgnoreStart and @codingStandardsIgnoreEnd
		 * Recommended function is vip_safe_wp_remote_get() but since it has a max timeout of 3 secs which
		 * is not feasible since the response time is way ahead 3 secs here and I am unable to fetch data
		 * if I use vip_safe_wp_remote_get()
		 */
		// @codingStandardsIgnoreStart
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
