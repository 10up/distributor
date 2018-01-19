<?php

namespace Distributor\Authentications;

use \Distributor\Authentication as Authentication;

/**
 * This auth type is simple username/password WP style
 */
class WordPressOath2Authentication extends Authentication {

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
