<?php

namespace Syndicate;

require_once( ABSPATH . '/wp-admin/includes/class-wp-posts-list-table.php' );

class PullListTable extends \WP_List_Table {

	/**
	 * @var array
	 */
	public $connection_objects = array();

	/**
	 * Initialize pull table
	 *
	 * @since  1.0
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );
	}

	/**
	 * Get pull tables columns
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'name'    => esc_html__( 'Name', 'syndicate' ),
			'content_type' => esc_html__( 'Content Type', 'syndicate' ),
			'date' => esc_html__( 'Date', 'syndicate' ),
		];

		return $columns;
	}

	/**
	 * Get sortable table columns
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name' => 'name',
			'date' => array( 'date', true ),
		);

		return $sortable_columns;
	}

	/**
	 * Get table views
	 *
	 * @since  1.0
	 * @return array
	 */
	protected function get_views() {

		$current_status = ( empty( $_GET['status'] ) ) ? 'new' : $_GET['status'];

		$status_links = [
			'new' => sprintf( __( '<a href="%s" class="%s">New</a>', 'syndicate' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=new' ), ( 'new' === $current_status ) ? 'current' : '' ),
			'syndicated' => sprintf( __( '<a href="%s" class="%s">Syndicated</a>', 'syndicate' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=sync' ), ( 'sync' === $current_status ) ? 'current' : '' ),
			'skipped' => sprintf( __( '<a href="%s" class="%s">Skipped</a>', 'syndicate' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=skip' ), ( 'skip' === $current_status ) ? 'current' : '' ),
		];

		return $status_links;
	}

	/**
	 * Handles the post date column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @global string $mode
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_date( $post ) {
		global $mode;

		if ( '0000-00-00 00:00:00' === $post->post_date ) {
			$t_time = $h_time = __( 'Unpublished' );
			$time_diff = 0;
		} else {
			$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );
			$m_time = $post->post_date;
			$time = get_post_time( 'G', true, $post );

			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$h_time = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			} else {
				$h_time = mysql2date( __( 'Y/m/d' ), $m_time );
			}
		}

		if ( 'publish' === $post->post_status ) {
			_e( 'Published' );
		} elseif ( 'future' === $post->post_status ) {
			if ( $time_diff > 0 ) {
				echo '<strong class="error-message">' . __( 'Missed schedule' ) . '</strong>';
			} else {
				_e( 'Scheduled' );
			}
		} else {
			_e( 'Last Modified' );
		}
		echo '<br />';
		if ( 'excerpt' === $mode ) {
			echo apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode );
		} else {
			echo '<abbr title="' . $t_time . '">' . apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) . '</abbr>';
		}
	}

	/**
	 * Output standard table columns (not name)
	 * 
	 * @param  array $item 
	 * @param  string $column_name
	 * @since  1.0
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return $item['post_title'];
				break;
			case 'content_type':
				return $item->post_type;
				break;
			case 'url':
				$url = get_post_meta( $item->ID, 'sy_external_connection_url', true );

				if ( empty( $url ) ) {
					$url = esc_html__( 'None', 'syndicate' );
				}

				return $url;
				break;
		}
	}

	/**
	 * Output name column wrapper
	 * 
	 * @since 4.3.0
	 * @access protected
	 *
	 * @param WP_Post $post
	 * @param string  $classes
	 * @param string  $data
	 * @param string  $primary
	 */
	protected function _column_name( $item, $classes, $data, $primary ) {
		echo '<td class="' . $classes . ' page-title" ', $data, '>';
		echo $this->column_name( $item );
		echo $this->handle_row_actions( $item, 'title', $primary );
		echo '</td>';
	}

	/**
	 * Output inner name column with actions
	 * 
	 * @param  WP_Post $item
	 * @since  1.0
	 */
	public function column_name( $item ) {

		$title = '<strong>' . esc_html( $item->post_title ) . '</strong>';

		global $connection_now;

		if ( is_a( $connection_now, '\Syndicate\ExternalConnection' ) ) {
			$connection_type = 'external';
			$connection_id = $connection_now->id;
		} else {
			$connection_type = 'internal';
			$connection_id = $connection_now->site->blog_id;
		}

		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
			$actions = [
				'syndicate' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&_wp_http_referer=' . urlencode( $_SERVER['REQUEST_URI'] ) . '&action=syndicate&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'sy_syndicate' ) ), esc_html__( 'Syndicate (as draft)', 'syndicate' ) ),
				'skip' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&action=skip&_wp_http_referer=' . urlencode( $_SERVER['REQUEST_URI'] ) . '&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'sy_skip' ) ), esc_html__( 'Skip', 'syndicate' ) ),
			];
		} elseif ( 'skip' === $_GET['status'] ) {
			$actions = [
				'syndicate' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&_wp_http_referer=' . urlencode( $_SERVER['REQUEST_URI'] ) . '&action=syndicate&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'sy_syndicate' ) ), esc_html__( 'Syndicate (as draft)', 'syndicate' ) ),
			];
		} elseif ( 'sync' === $_GET['status'] ) {
			$actions = [
				'mark-new' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&_wp_http_referer=' . urlencode( $_SERVER['REQUEST_URI'] ) . '&action=mark-new&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'sy_mark_new' ) ), esc_html__( 'Mark as New', 'syndicate' ) ),
			];
		}

		echo $title;
		echo $this->row_actions( $actions );
	}

	/**
	 * Remotely get items for display in table
	 *
	 * @since  1.0
	 */
	public function prepare_items() {
		global $connection_now;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page( 'connections_per_page', 5 );
		$current_page = $this->get_pagenum();

		$remote_get_args = [
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
		];

		if ( ! empty( $_GET['s'] ) ) {
			$remote_get_args['s'] = $_GET['s'];
		}

		if ( is_a( $connection_now, '\Syndicate\ExternalConnection' ) ) {
			$pull_statuses = get_post_meta( $connection_now->id, 'sy_pull_statuses', true );

			if ( empty( $pull_statuses ) ) {
				$pull_statuses = array();
			}

			$skipped = array();
			$syndicated = array();

			foreach ( $pull_statuses as $post_id => $pull_status ) {
				if ( 'skip' === $pull_status ) {
					$skipped[] = (int) $post_id;
				} elseif ( 'sync' === $pull_status ) {
					$syndicated[] = (int) $post_id;
				}
			}

			if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
				$remote_get_args['post__not_in'] = array_merge( $skipped, $syndicated );
			} elseif ( 'skip' === $_GET['status'] ) {
				$remote_get_args['post__in'] = $skipped;
			} else {
				$remote_get_args['post__in'] = $syndicated;
			}
		} else {
			if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
				$remote_get_args['tax_query'] = [
					[
						'taxonomy' => 'sy-sync-status',
						'field'    => 'slug',
						'terms'    => [ 'sync', 'skip' ],
						'operator' => 'NOT IN',
					]
				];
			} elseif ( 'skip' === $_GET['status'] ) {
				$remote_get_args['tax_query'] = [
					[
						'taxonomy' => 'sy-sync-status',
						'field'    => 'slug',
						'terms'    => [ 'skip' ],
					]
				];
			} else {
				$remote_get_args['tax_query'] = [
					[
						'taxonomy' => 'sy-sync-status',
						'field'    => 'slug',
						'terms'    => [ 'sync' ],
					]
				];
			}
		}

		$remote_get = $connection_now->remote_get( $remote_get_args );

		$this->set_pagination_args( [
			'total_items' => $remote_get['total_items'],
			'per_page'    => $per_page,
		] );

		foreach ( $remote_get['items'] as $item ) {
			$this->items[] = $item;
		}
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since 4.3.0
	 * @access public
	 *
	 * @param WP_Post $post The current WP_Post object.
	 */
	public function column_cb( $post ) {
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo (int) $post->ID; ?>"><?php
			printf( __( 'Select %s' ), _draft_or_post_title() );
		?></label>
		<input id="cb-select-<?php echo (int) $post->ID; ?>" type="checkbox" name="post[]" value="<?php echo (int) $post->ID; ?>" />
		<div class="locked-indicator"></div>
		<?php
	}

	/**
	 * Get available bulk actions
	 *
	 * @since  1.0
	 * @return array
	 */
	public function get_bulk_actions() {
		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
			$actions = [
				'bulk-syndicate' => esc_html__( 'Syndicate', 'syndicate' ),
				'bulk-skip' => esc_html__( 'Skip', 'syndicate' ),
			];
		} elseif ( 'skip' === $_GET['status'] ) {
			$actions = [
				'bulk-syndicate' => esc_html__( 'Syndicate', 'syndicate' ),
			];
		} else {
			$actions = [
				'bulk-mark-new' => esc_html__( 'Mark as New', 'syndicate' ),
			];
		}

		return $actions;
	}

}
