<?php
/**
 * Admin list table for external connections
 *
 * @package  distributor
 */

namespace Distributor;

require_once ABSPATH . '/wp-admin/includes/class-wp-posts-list-table.php';

/**
 * External connection table shows all external connections
 */
class ExternalConnectionListTable extends \WP_Posts_List_Table {
	/**
	 * Create table
	 *
	 * @since  0.8
	 */
	public function __construct() {
		parent::__construct(
			array(
				'ajax'   => false,
				'screen' => \WP_Screen::get( 'dt_ext_connection' ),
			)
		);
	}

	/**
	 * Only allow delete bulk action
	 *
	 * @since  0.8
	 */
	public function get_bulk_actions() {
		$actions = [
			'bulk-delete' => 'Delete',
		];

		return $actions;
	}

	/**
	 * We don't want to show extra table actions
	 *
	 * @param string $which Above or below nav.
	 * @since 0.8
	 */
	public function extra_tablenav( $which ) { }

	/**
	 * Generates and displays row action links.
	 *
	 * @param object $post        Post being acted upon.
	 * @param string $column_name Current column name.
	 * @param string $primary     Primary column name.
	 * @since  0.8
	 * @return string Row actions output for posts.
	 */
	protected function handle_row_actions( $post, $column_name, $primary ) {
		if ( $primary !== $column_name ) {
			return '';
		}

		$post_type_object = get_post_type_object( $post->post_type );
		$actions          = array();
		$title            = _draft_or_post_title();

		$actions['edit'] = sprintf(
			'<a href="%s" aria-label="%s">%s</a>',
			get_edit_post_link( $post->ID ),
			/* translators: %s: post title */
			esc_attr( sprintf( esc_html__( 'Edit "%s"', 'distributor' ), $title ) ),
			esc_html__( 'Edit', 'distributor' )
		);

		if ( current_user_can( 'delete_post', $post->ID ) ) {
			if ( 'trash' === $post->post_status ) {
				$actions['untrash'] = sprintf(
					'<a href="%s" aria-label="%s">%s</a>',
					wp_nonce_url( admin_url( sprintf( $post_type_object->_edit_link . '&amp;action=untrash', $post->ID ) ), 'untrash-post_' . $post->ID ),
					/* translators: %s: post title */
					esc_attr( sprintf( esc_html__( 'Restore "%s" from the Trash', 'distributor' ), $title ) ),
					esc_html__( 'Restore', 'distributor' )
				);
			} elseif ( EMPTY_TRASH_DAYS ) {
				$actions['trash'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID ),
					/* translators: %s: post title */
					esc_attr( sprintf( esc_html__( 'Move "%s" to the Trash', 'distributor' ), $title ) ),
					esc_html_x( 'Trash', 'verb', 'distributor' )
				);
			}
			if ( 'trash' === $post->post_status || ! EMPTY_TRASH_DAYS ) {
				$actions['delete'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID, '', true ),
					/* translators: %s: post title */
					esc_attr( sprintf( esc_html__( 'Delete "%s" permanently', 'distributor' ), $title ) ),
					esc_html__( 'Delete Permanently', 'distributor' )
				);
			}
		}

		return $this->row_actions( $actions );
	}
}
