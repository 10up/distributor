<?php
/**
 * Authentication base class
 *
 * @package  distributor
 */

namespace Distributor;

use \Distributor\ExternalConnection as ExternalConnection;

/**
 * Authentication types extend this base abstract class. Authentication types
 * are used to authenticate push and pull requests for an external connection. Note that static
 * methods are used for interacting with the type whereas class instances deal with
 * an actual external connection.
 */
abstract class Authentication {

	/**
	 * Error message
	 *
	 * @var string
	 */
	public static $error_message;

	/**
	 * Set associative arguments as instance variables
	 *
	 * @param array $args Array of arguments to set.
	 * @since       0.8
	 */
	public function __construct( $args ) {
		if ( ! empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				$this->$key = $value;
			}
		}
	}

	/**
	 * Format request args for a GET request so auth occurs
	 *
	 * @param  array $args Arguments to format.
	 * @param  array $context optional array of information about the request
	 * @since  0.8
	 * @return array
	 */
	public function format_get_args( $args = array(), $context = array() ) {
		if ( ! isset( $args['headers'] ) ) {
			$args['headers'] = array();
		}
		$args['headers']['X-Distributor-Version'] = DT_VERSION;

		/**
		 * Format request args for a GET request so auth occurs.
		 *
		 * @since 0.8
		 * @hook dt_auth_format_get_args
		 *
		 * @param  {array}  $args    Array of request arguments.
		 * @param  {array}  $context Optional array of information about the request.
		 * @param  {object} $this    The authentication class.
		 *
		 * @return {array} Array of request arguments.
		 */
		return apply_filters( 'dt_auth_format_get_args', $args, $context, $this );
	}

	/**
	 * Format request args for a POST request so auth occurs
	 *
	 * @param  array $args Arguments to format.
	 * @param  array $context optional array of information about the request
	 * @since  0.8
	 * @return array
	 */
	public function format_post_args( $args, $context = array() ) {
		if ( ! isset( $args['headers'] ) ) {
			$args['headers'] = array();
		}
		$args['headers']['X-Distributor-Version'] = DT_VERSION;

		/**
		 * Format request args for a POST request so auth occurs
		 *
		 * @since 0.8
		 * @hook dt_auth_format_post_args
		 *
		 * @param  {array}  $args    Array of request arguments.
		 * @param  {array}  $context Optional array of information about the request.
		 * @param  {object} $this    The authentication class.
		 *
		 * @return {array} Array of request arguments.
		 */
		return apply_filters( 'dt_auth_format_post_args', $args, $context, $this );
	}

	/**
	 * Output a credentials form in the external connection management screen.
	 *
	 * Child classes should implement - public static function credentials_form();
	 */

	/**
	 * Store an associate array as credentials for use with an external connection.
	 *
	 * Child classes should implement - public static function prepare_credentials( $args );
	 */

	/**
	 * Store pre-sanizited auth credentials in DB
	 *
	 * @param int   $external_connection_id External connection ID.
	 * @param array $args Array of credentials to store. Should be pre-sanitized.
	 * @since 0.8
	 */
	public static function store_credentials( $external_connection_id, $args ) {
		update_post_meta( $external_connection_id, 'dt_external_connection_auth', $args );
	}

	/**
	 * Oauth connection error logging facility for non production environments.
	 *
	 * @param string $error_message The error message to log.
	 */
	public static function log_authentication_error( $error_message ) {

		// Store the message for output at the top of the authorization form
		self::$error_message = $error_message;
		add_action(
			'auth_admin_notices',
			function() {
				?>
		<div class="notice notice-error is-dismissible">
			<p>
				<strong>
					<?php esc_html_e( 'Authorization error:', 'distributor' ); ?>
				</strong> <?php echo esc_html( self::$error_message ); ?>
			</p>
		</div>
				<?php
			}
		);
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$time = gmdate( '[d/M/Y:H:i:s]' );
			// @codingStandardsIgnoreLine - error_log is only used when WP_DEBUG is true.
			error_log( $time . ': ' . $error_message );
		}
	}
}
