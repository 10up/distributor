<?php
/**
 * Test the main application passwords class.
 *
 * @since 0.1-dev
 *
 * @package Two_Factor
 */
class Test_Application_Passwords extends WP_UnitTestCase {

	/**
	 * @see Application_Passwords::add_hooks()
	 */
	function test_add_hooks() {
		$this->assertEquals( 10, has_action( 'authenticate', array( 'Application_Passwords', 'authenticate' ) ) );
	}

	/**
	 * Regular front-end requests are not REST requests.
	 *
	 * @covers Application_Passwords::is_api_request()
	 */
	public function test_rest_api_request() {
		$this->assertFalse( Application_Passwords::is_api_request() );
	}

	/**
	 * HTTP Auth headers are used to determine the current user.
	 *
	 * @covers Application_Passwords::rest_api_auth_handler()
	 */
	public function test_can_login_user_through_http_auth_headers() {
		$user_id = $this->factory->user->create(
			array(
				'user_login' => 'http_auth_login',
				'user_pass'  => 'http_auth_pass', // Shouldn't be allowed for API login.
			)
		);

		// Create a new app-only password.
		$user_app_password = Application_Passwords::create_new_application_password( $user_id, 'phpunit' );

		// Fake a REST API request.
		add_filter( 'application_password_is_api_request', '__return_true' );

		// Fake an HTTP Auth request with the regular account password first.
		$_SERVER['PHP_AUTH_USER'] = 'http_auth_login';
		$_SERVER['PHP_AUTH_PW']   = 'http_auth_pass';

		$this->assertEquals(
			0,
			Application_Passwords::rest_api_auth_handler( null ),
			'Regular user account password should not be allowed for API authenticaiton'
		);

		// Not try with an App password instead.
		$_SERVER['PHP_AUTH_PW'] = $user_app_password[0];

		$this->assertEquals(
			$user_id,
			Application_Passwords::rest_api_auth_handler( null ),
			'Application passwords should be allowed for API authentication'
		);

		remove_filter( 'application_password_is_api_request', '__return_true' );

		// Cleanup all the global state.
		unset( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] );
	}
}
