<?php

namespace Distributor;

class PullListTable extends \WP_List_Table {

	/**
	 * @var array
	 */
	public $connection_objects = array();

	public $sync_log = [];

	/**
	 * Initialize pull table
	 *
	 * @since  0.8
	 */
	public function __construct() {
		parent::__construct( array(
			'ajax' => false,
		) );
	}

	/**
	 * Get pull tables columns
	 *
	 * @since  0.8
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'      => '<input type="checkbox" />',
			'name'    => esc_html__( 'Name', 'distributor' ),
			'content_type' => esc_html__( 'Content Type', 'distributor' ),
			'date' => esc_html__( 'Date', 'distributor' ),
		];

		return $columns;
	}

	/**
	 * Get sortable table columns
	 *
	 * @since  0.8
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
	 * @since  0.8
	 * @return array
	 */
	protected function get_views() {

		$current_status = ( empty( $_GET['status'] ) ) ? 'new' : $_GET['status'];

		$status_links = [
			'new' => sprintf( __( '<a href="%s" class="%s">New</a>', 'distributor' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=new' ), ( 'new' === $current_status ) ? 'current' : '' ),
			'pulled' => sprintf( __( '<a href="%s" class="%s">Pulled</a>', 'distributor' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=pulled' ), ( 'pulled' === $current_status ) ? 'current' : '' ),
			'skipped' => sprintf( __( '<a href="%s" class="%s">Skipped</a>', 'distributor' ), esc_url( $_SERVER['REQUEST_URI'] . '&status=skipped' ), ( 'skipped' === $current_status ) ? 'current' : '' ),
		];

		return $status_links;
	}

	/**
	 * Display the bulk actions dropdown.
	 *
	 * @since 3.1.0
	 * @access protected
	 *
	 * @param string $which The location of the bulk actions: 'top' or 'bottom'.
	 *                      This is designated as optional for backward compatibility.
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$no_new_actions = $this->_actions = $this->get_bulk_actions();
			/**
			 * Filters the list table Bulk Actions drop-down.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen, usually a string.
			 *
			 * This filter can currently only be used to remove bulk actions.
			 *
			 * @since 3.5.0
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions );
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) )
			return;

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		
		foreach ( $this->_actions as $name => $title ) {
			$class = 'edit' === $name ? ' class="hide-if-no-js"' : '';

			echo "\t" . '<option value="' . $name . '"' . $class . '>' . $title . "</option>\n";
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', '', false, array( 'id' => "doaction$two" ) );
		echo "\n";
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

		if ( ! empty( $_GET['status'] ) && 'pulled' === $_GET['status'] ) {
			if ( ! empty( $this->sync_log[ $post->ID ] ) ) {
				$syndicated_at = get_post_meta( $this->sync_log[ $post->ID ], 'dt_syndicate_time', true );
				
				if ( empty( $syndicated_at ) ) {
					esc_html_e( 'Post deleted.', 'distributor' );
				} else {
					$t_time = get_the_time( __( 'Y/m/d g:i:s a' ) );

					$time_diff = time() - $syndicated_at;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
						$h_time = sprintf( __( '%s ago' ), human_time_diff( $syndicated_at ) );
					} else {
						$h_time = date( 'F j, Y', $syndicated_at );
					}

					echo sprintf( __( 'Pulled %s', 'distributor' ), esc_html( $h_time ) );
				}
			}
		} else {
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
	}

	/**
	 * Output standard table columns (not name)
	 *
	 * @param  array  $item
	 * @param  string $column_name
	 * @since  0.8
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return $item['post_title'];
				break;
			case 'content_type':
				$post_type_object = get_post_type_object( $item->post_type );
				if ( empty( $post_type_object ) ) {
					return $item->post_type;
				}

				return $post_type_object->labels->singular_name;
				break;
			case 'url':
				$url = get_post_meta( $item->ID, 'dt_external_connection_url', true );

				if ( empty( $url ) ) {
					$url = esc_html__( 'None', 'distributor' );
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
	 * @since  0.8
	 */
	public function column_name( $item ) {

		$title = '<strong>' . esc_html( $item->post_title ) . '</strong>';

		global $connection_now;

		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$connection_type = 'external';
			$connection_id = $connection_now->id;
		} else {
			$connection_type = 'internal';
			$connection_id = $connection_now->site->blog_id;
		}

		if ( empty( $sync_map ) ) {
			$sync_map = array();
		}

		$actions = [];

		$as_draft = '';
		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$as_draft = ' ' . esc_html__( '(as draft)', 'distributor' );
		}

		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
			$actions = [
				'view' => '<a href="' . esc_url( $item->link ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
				'skip' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&action=skip&_wp_http_referer=' . urlencode( $_SERVER['REQUEST_URI'] ) . '&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'dt_skip' ) ), esc_html__( 'Skip', 'distributor' ) ),
			];
		} elseif ( 'skipped' === $_GET['status'] ) {
			$actions = [
				'view' => '<a href="' . esc_url( $item->link ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
			];
		} elseif ( 'pulled' === $_GET['status'] ) {

			$new_post_id = ( ! empty( $this->sync_log[ (int) $item->ID ] ) ) ? $this->sync_log[ (int) $item->ID ] : 0;
			$new_post = get_post( $new_post_id );

			if ( ! empty( $new_post ) ) {
				$actions = [
					'view' => '<a href="' . esc_url( get_permalink( $new_post_id ) ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
					'edit' => '<a href="' . esc_url( get_edit_post_link( $new_post_id ) ) . '">' . esc_html__( 'Edit', 'distributor' ) . '</a>',
				];
			}
		}

		echo $title;
		echo $this->row_actions( $actions );
	}

	/**
	 * Remotely get items for display in table
	 *
	 * @since  0.8
	 */
	public function prepare_items() {
		global $connection_now;

		if ( empty( $connection_now ) ) {
			return;
		}

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

		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$this->sync_log = get_post_meta( $connection_now->id, 'dt_sync_log', true );
		} else {
			$this->sync_log = get_site_option( 'dt_sync_log_' . $connection_now->site->blog_id, array() );
		}

		if ( empty( $this->sync_log ) ) {
			$this->sync_log = [];
		}

		$skipped = array();
		$syndicated = array();

		foreach ( $this->sync_log as $old_post_id => $new_post_id ) {
			if ( false === $new_post_id ) {
				$skipped[] = (int) $old_post_id;
			} else {
				$syndicated[] = (int) $old_post_id;
			}
		}

		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
			$remote_get_args['post__not_in'] = array_merge( $skipped, $syndicated );
		} elseif ( 'skipped' === $_GET['status'] ) {
			$remote_get_args['post__in'] = $skipped;
		} else {
			$remote_get_args['post__in'] = $syndicated;
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
	 * @since  0.8
	 * @return array
	 */
	public function get_bulk_actions() {
		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) {
			$actions = [
				'bulk-syndicate' => esc_html__( 'Syndicate', 'distributor' ),
				'bulk-skip' => esc_html__( 'Skip', 'distributor' ),
			];
		} elseif ( 'skipped' === $_GET['status'] ) {
			$actions = [
				'bulk-syndicate' => esc_html__( 'Syndicate', 'distributor' ),
			];
		} else {
			$actions = [];
		}

		return $actions;
	}
}
