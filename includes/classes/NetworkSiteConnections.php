<?php

namespace Syndicate;

/**
 * This is a singleton-ish class for dealing with all network site connections
 */
class NetworkSiteConnections {
	/**
	 * This will act as a singleton
	 *
	 * @since  1.0
	 */
	public function __construct() { }

	/**
	 * Setup hooks
	 *
	 * @since  1.0
	 */
	public function setup() {
		add_action( 'wp_ajax_sy_auth_check', array( $this, 'auth_check' ) );
	}

	/**
	 * Check if current user can create a post type with ajax
	 *
	 * @since  1.0
	 */
	public function auth_check() {
		if ( ! check_ajax_referer( 'sy-auth-check', 'nonce', false ) ) {
			wp_send_json_error();
			exit;
		}

		if ( empty( $_POST['username'] ) ) {
			wp_send_json_error();
			exit;
		}

		$post_types = get_post_types();
		$authorized_post_types = array();

		foreach ( $post_types as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( current_user_can( $post_type_object->cap->create_posts ) ) {
				$authorized_post_types[] = $post_type;
			}
		}


		wp_send_json_success( $authorized_post_types );
		exit;
	}

	/**
	 * Find out which sites user can create post type on
	 * 
	 * @since  1.0
	 * @return array
	 */
	public function get_available_authorized_sites() {
		if ( ! is_multisite() ) {
			return array();
		}
		
		$sites = get_sites();
		$authorized_sites = array();

		$current_blog_id = get_current_blog_id();

		foreach ( $sites as $site ) {
			$blog_id = $site->blog_id;

			if ( $blog_id == $current_blog_id ) {
				continue;
			}

			$base_url = get_site_url( $blog_id );

			if ( empty( $base_url ) ) {
				continue;
			}

			global $current_user;
			get_currentuserinfo();

			$response = wp_remote_post( untrailingslashit( $base_url ) . '/wp-admin/admin-ajax.php', array(
				'body' => array(
					'nonce'     => wp_create_nonce( 'sy-auth-check' ),
					'username'  => $current_user->user_login,
					'action'    => 'sy_auth_check',
				),
				'cookies' => $_COOKIE
			) );

			if ( ! is_wp_error( $response ) ) {

				$body = wp_remote_retrieve_body( $response );

				if ( ! is_wp_error( $body ) ) {
					try {
						$body_array = json_decode( $body, true );
						
						if ( ! empty( $body_array['success'] ) ) {
							$authorized_sites[] = array(
								'site'       => $site,
								'post_types' => $body_array['data'],
							);
						}
					} catch ( \Exception $e ) {
						continue;
					}
				}
			}
		}

		return $authorized_sites;
	}

	/**
	 * Singleton-ish class
	 *
	 * @since  1.0
	 * @return object
	 */
	public static function factory() {
		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}
