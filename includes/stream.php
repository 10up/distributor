<?php
namespace Distributor\Stream;

class Connector extends \WP_Stream\Connector {
	/**
	 * Connector slug
	 *
	 * @var string
	 */
	public $name = 'distributor';

	/**
	 * Holds tracked plugin minimum version required
	 *
	 * @const string
	 */
	const PLUGIN_MIN_VERSION = '1.1.0';

	/**
	 * Actions registered for this connector
	 *
	 * @var array
	 */
	public $actions = array(
		'dt_unlink_post',
		'dt_link_post',
		'dt_pull_post',
		'dt_push_post'
	);

	/**
	 * Check if plugin dependencies are satisfied and add an admin notice if not
	 *
	 * @return bool
	 */
	public function is_dependency_satisfied() {
		if ( defined('DT_VERSION') && version_compare( DT_VERSION, self::PLUGIN_MIN_VERSION, '>=' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return translated connector label
	 *
	 * @return string Translated connector label
	 */
	public function get_label() {
		return esc_html_x( 'Distributor', 'distributor', 'stream' );
	}

	/**
	 * Return translated action labels
	 *
	 * @return array Action label translations
	 */
	public function get_action_labels() {
		return array(
			'unlinked'     => esc_html_x( 'Unlinked', 'distributor', 'stream' ),
			'linked'     => esc_html_x( 'Linked', 'distributor', 'stream' ),
			'pulled'   => esc_html_x( 'Pulled', 'distributor', 'stream' ),
			'pushed' => esc_html_x( 'Pushed', 'distributor', 'stream' ),
		);
	}

	/**
	 * Return translated context labels
	 *
	 * @return array Context label translations
	 */
	public function get_context_labels() {
		return array(
			'linking' => esc_html_x( 'Linking', 'distributor', 'stream' ),
			'transfer' => esc_html_x( 'Transfer', 'distributor', 'stream' ),
		);
	}

	/**
	 * Add action links to Stream drop row in admin list screen
	 *
	 * @filter wp_stream_action_links_{connector}
	 *
	 * @param  array $links      Previous links registered
	 * @param  object $record    Stream record
	 *
	 * @return array             Action links
	 */
	public function action_links( $links, $record ) {

		$post = get_post( $record->object_id );

		$post_type_name = $this->get_post_type_name( get_post_type( $post->ID ) );

		$links[ sprintf( esc_html_x( 'Edit %s', 'Post type singular name', 'stream' ), $post_type_name ) ] = get_edit_post_link( $post->ID );

		$view_link = get_permalink( $post->ID );
		if ( $view_link ) {
			$links[ esc_html__( 'View', 'stream' ) ] = $view_link;
		}

		return $links;
	}

	public function callback_dt_unlink_post($post_id) {
		$post = get_post($post_id);
		$post_type_name = strtolower( $this->get_post_type_name( $post->post_type ) );
		$this->log(
			_x( '%1$s %2$s (from %3$s) unlinked', '1: Post title, 2: Post type singular name, 3: Original site name', 'stream' ),
			array(
				'post_title'    => $post->post_title,
				'singular_name' => $post_type_name,
				'site_name'     => distributor_get_original_site_name($post_id)
			),
			$post->ID,
			'linking',
			'unlinked'
		);
	}

	public function callback_dt_link_post($post_id) {
		$post = get_post($post_id);
		$post_type_name = strtolower( $this->get_post_type_name( $post->post_type ) );
		$this->log(
			_x( '%1$s %2$s (from %3$s) linked', '1: Post title, 2: Post type singular name, 3: Original site name', 'stream' ),
			array(
				'post_title'    => $post->post_title,
				'singular_name' => $post_type_name,
				'site_name'     => distributor_get_original_site_name($post_id)
			),
			$post->ID,
			'linking',
			'linked'
		);
	}

	public function callback_dt_pull_post($post_id, $connection, $post_data) {
		$post = get_post($post_id);
		$post_type_name = strtolower( $this->get_post_type_name( $post->post_type ) );
		$this->log(
			_x( '%1$s %2$s pulled from %3$s', '1: Post title, 2: Post type singular name, 3: Original site name', 'stream' ),
			array(
				'post_title'    => $post->post_title,
				'singular_name' => $post_type_name,
				'site_name'     => distributor_get_original_site_name($post_id)
			),
			$post->ID,
			'transfer',
			'pulled'
		);
	}

	public function callback_dt_push_post($http_resp, $posted_body, $url, $post_id, $args, $connection) {
		$post = get_post($post_id);
		$post_type_name = strtolower( $this->get_post_type_name( $post->post_type ) );
		$this->log(
			_x( '%1$s %2$s pushed to %3$s', '1: Post title, 2: Post type singular name, 3: Original site name', 'stream' ),
			array(
				'post_title'    => $post->post_title,
				'singular_name' => $post_type_name,
				'site_name'     => $connection->name
			),
			$post->ID,
			'transfer',
			'pushed'
		);
	}

	/**
	 * Gets the singular post type label
	 *
	 * @param string $post_type_slug
	 *
	 * @return string Post type label
	 */
	public function get_post_type_name( $post_type_slug ) {
		$name = esc_html__( 'Post', 'stream' ); // Default

		if ( post_type_exists( $post_type_slug ) ) {
			$post_type = get_post_type_object( $post_type_slug );
			$name      = $post_type->labels->singular_name;
		}

		return $name;
	}

}
