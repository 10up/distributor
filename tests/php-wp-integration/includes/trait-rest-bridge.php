<?php
/**
 * Bridge HTTP requests between network sites to emulate external sites.
 *
 * @package distributor
 */

// phpcs:disable WordPressVIPMinimum.Variables.ServerVariables.BasicAuthentication -- Emulates the web server populating basic auth credentials.

namespace Distributor\IntegrationTests;

use WP_Application_Passwords;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;
use Distributor\ExternalConnection;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * Route outgoing HTTP requests to the REST API of a network site.
 *
 * External connections talk to remote sites over HTTP. The test suite runs
 * each test inside an uncommitted database transaction, so a separate web
 * server could not see any of the test's data. Instead, requests are
 * intercepted via `pre_http_request` and dispatched in-process to the REST
 * API of the network site the URL belongs to, mirroring what
 * `WP_REST_Server::serve_request()` does for a real request.
 *
 * Requests for files in a site's uploads directory are served from disk, so
 * media can be downloaded. Any other request fails the test.
 *
 * Both "sites" share the network's users, site options, object cache and
 * PHP globals, and run the same copy of Distributor.
 */
trait REST_Bridge {
	/**
	 * Requests made via the bridge, each with the method and URL.
	 *
	 * @var array[]
	 */
	protected $bridged_requests = array();

	/**
	 * Start bridging HTTP requests to network sites.
	 *
	 * The filter is removed automatically when the test's hooks are reset.
	 */
	protected function start_rest_bridge() {
		$this->bridged_requests = array();
		add_filter( 'pre_http_request', array( $this, 'bridge_http_request' ), 10, 3 );
	}

	/**
	 * Create an application password for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string Application password.
	 */
	protected function create_application_password( $user_id ) {
		list( $password ) = WP_Application_Passwords::create_new_application_password( $user_id, array( 'name' => 'Distributor tests' ) );
		return $password;
	}

	/**
	 * Create an external connection to a network site.
	 *
	 * @param int    $blog_id  Site to connect to.
	 * @param string $username Username to authenticate with.
	 * @param string $password Application password to authenticate with.
	 * @return ExternalConnection External connection.
	 */
	protected function create_bridged_connection( $blog_id, $username, $password ) {
		$connection_id = self::factory()->post->create(
			array(
				'post_type'   => 'dt_ext_connection',
				'post_title'  => "Bridged connection to site {$blog_id}",
				'post_status' => 'publish',
				'meta_input'  => array(
					'dt_external_connection_type' => 'wp',
					'dt_external_connection_url'  => get_rest_url( $blog_id ),
					'dt_external_connection_auth' => array(
						'username' => $username,
						'password' => $password,
					),
				),
			)
		);

		return ExternalConnection::instantiate( $connection_id );
	}

	/**
	 * Intercept an HTTP request and route it to a network site.
	 *
	 * @param false|array|\WP_Error $preempt Preempted response.
	 * @param array                 $args    HTTP request arguments.
	 * @param string                $url     Request URL.
	 * @return array|\WP_Error Response.
	 */
	public function bridge_http_request( $preempt, $args, $url ) {
		if ( false !== $preempt ) {
			return $preempt;
		}

		$this->bridged_requests[] = array(
			'method' => strtoupper( $args['method'] ?? 'GET' ),
			'url'    => $url,
		);

		$target = $this->parse_bridged_url( $url );

		if ( ! $target ) {
			$this->fail( "Unexpected HTTP request to {$url}." );
		}

		switch_to_blog( $target['blog_id'] );

		try {
			if ( null === $target['route'] ) {
				$response = $this->serve_bridged_upload( $args, $url );
			} else {
				$response = $this->serve_bridged_rest_request( $args, $target['route'], $target['query'] );
			}
		} finally {
			restore_current_blog();
		}

		if ( null === $response ) {
			$this->fail( "Unexpected HTTP request to {$url}." );
		}

		return $response;
	}

	/**
	 * Determine the network site, REST route and query a URL is for.
	 *
	 * @param string $url Request URL.
	 * @return array|false Target details, false if the URL is not for a network site.
	 */
	protected function parse_bridged_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['host'] ) ) {
			return false;
		}

		$path = $parts['path'] ?? '/';
		$site = get_site_by_path( $parts['host'], $path );

		if ( ! $site ) {
			return false;
		}

		$query = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		$route         = null;
		$relative_path = ltrim( substr( $path, strlen( $site->path ) ), '/' );
		$rest_prefix   = rest_get_url_prefix() . '/';

		if ( isset( $query['rest_route'] ) ) {
			// Plain permalinks.
			$route = $query['rest_route'];
			unset( $query['rest_route'] );
		} elseif ( str_starts_with( trailingslashit( $relative_path ), $rest_prefix ) ) {
			// Pretty permalinks.
			$route = '/' . substr( $relative_path, strlen( $rest_prefix ) );
		}

		return array(
			'blog_id' => (int) $site->blog_id,
			'route'   => null === $route ? null : '/' . ltrim( $route, '/' ),
			'query'   => $query,
		);
	}

	/**
	 * Dispatch a request to the current site's REST API.
	 *
	 * Mirrors `WP_REST_Server::serve_request()`.
	 *
	 * @param array  $args  HTTP request arguments.
	 * @param string $route REST route.
	 * @param array  $query Query string parameters.
	 * @return array HTTP response.
	 */
	protected function serve_bridged_rest_request( $args, $route, $query ) {
		global $wp_rest_server, $wp_rest_application_password_status, $wp_rest_application_password_uuid;

		$method  = strtoupper( $args['method'] ?? 'GET' );
		$headers = $args['headers'] ?? array();
		$body    = $args['body'] ?? null;

		$request = new WP_REST_Request( $method, $route );
		$request->set_headers( $headers );

		if ( is_array( $body ) ) {
			// Form encode the body as WP_Http does, so values arrive as strings.
			parse_str( http_build_query( $body ), $body );

			if ( 'GET' === $method || 'HEAD' === $method ) {
				$query = array_merge( $query, $body );
			} else {
				$request->set_body_params( $body );
			}
		} elseif ( is_string( $body ) ) {
			$request->set_body( $body );
		}

		$request->set_query_params( $query );

		// Preserve the state of the "local" site.
		$previous_user            = get_current_user_id();
		$previous_server          = $wp_rest_server;
		$previous_auth_status     = $wp_rest_application_password_status;
		$previous_auth_uuid       = $wp_rest_application_password_uuid;
		$previous_server_globals  = array_intersect_key( $_SERVER, array_flip( array( 'PHP_AUTH_USER', 'PHP_AUTH_PW' ) ) );
		$application_passwords_on = static function () {
			// Not otherwise available without SSL.
			return true;
		};

		add_filter( 'wp_is_application_passwords_available', $application_passwords_on );
		add_filter( 'application_password_is_api_request', $application_passwords_on );

		try {
			// Authenticate as a real request would.
			unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			$wp_rest_application_password_status = null;
			$wp_rest_application_password_uuid   = null;

			$authorization = $request->get_header( 'authorization' );
			if ( $authorization && 0 === stripos( $authorization, 'basic ' ) ) {
				$credentials = explode( ':', base64_decode( substr( $authorization, 6 ) ), 2 ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

				$_SERVER['PHP_AUTH_USER'] = $credentials[0];
				$_SERVER['PHP_AUTH_PW']   = $credentials[1] ?? '';
			}

			wp_set_current_user( 0 );
			$user_id = apply_filters( 'determine_current_user', false ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
			wp_set_current_user( $user_id ? (int) $user_id : 0 );

			// Initialize a fresh REST server for this site.
			$wp_rest_server = null;
			$server         = rest_get_server();

			$result = $server->check_authentication();
			if ( ! is_wp_error( $result ) ) {
				$result = $server->dispatch( $request );
			}

			$result = rest_ensure_response( $result );
			if ( is_wp_error( $result ) ) {
				$result = rest_convert_error_to_response( $result );
			}

			$result = apply_filters( 'rest_post_dispatch', rest_ensure_response( $result ), $server, $request ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.

			$embed = isset( $query['_embed'] ) ? rest_parse_embed_param( $query['_embed'] ) : false;
			$data  = $server->response_to_data( $result, $embed );

			$response_headers = array(
				'Content-Type' => 'application/json; charset=' . get_option( 'blog_charset' ),
				'Link'         => '<' . sanitize_url( get_rest_url() ) . '>; rel="https://api.w.org/"',
			);
			foreach ( $result->get_headers() as $name => $value ) {
				$response_headers[ $name ] = (string) $value;
			}

			return $this->bridged_response( $result->get_status(), wp_json_encode( $data ), $response_headers );
		} finally {
			$wp_rest_server                      = $previous_server;
			$wp_rest_application_password_status = $previous_auth_status;
			$wp_rest_application_password_uuid   = $previous_auth_uuid;

			unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
			$_SERVER = array_merge( $_SERVER, $previous_server_globals );

			remove_filter( 'wp_is_application_passwords_available', $application_passwords_on );
			remove_filter( 'application_password_is_api_request', $application_passwords_on );

			wp_set_current_user( $previous_user );
		}
	}

	/**
	 * Serve a file from the current site's uploads directory.
	 *
	 * @param array  $args HTTP request arguments.
	 * @param string $url  Request URL.
	 * @return array|null HTTP response, null if the URL is not for an upload.
	 */
	protected function serve_bridged_upload( $args, $url ) {
		$uploads  = wp_upload_dir();
		$base_url = trailingslashit( $uploads['baseurl'] );

		if ( ! str_starts_with( $url, $base_url ) ) {
			return null;
		}

		$file = trailingslashit( $uploads['basedir'] ) . rawurldecode( strtok( substr( $url, strlen( $base_url ) ), '?' ) );

		if ( ! is_file( $file ) ) {
			return $this->bridged_response( 404, '' );
		}

		if ( ! empty( $args['stream'] ) && ! empty( $args['filename'] ) ) {
			copy( $file, $args['filename'] );
			return $this->bridged_response( 200, '', array(), $args['filename'] );
		}

		return $this->bridged_response( 200, file_get_contents( $file ) ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
	}

	/**
	 * Build an HTTP response in the format returned by WP_Http.
	 *
	 * @param int         $status   HTTP status code.
	 * @param string      $body     Response body.
	 * @param array       $headers  Response headers.
	 * @param string|null $filename Downloaded file name, if streamed.
	 * @return array HTTP response.
	 */
	protected function bridged_response( $status, $body, $headers = array(), $filename = null ) {
		return array(
			'headers'  => new CaseInsensitiveDictionary( $headers ),
			'body'     => $body,
			'response' => array(
				'code'    => $status,
				'message' => get_status_header_desc( $status ),
			),
			'cookies'  => array(),
			'filename' => $filename,
		);
	}
}
