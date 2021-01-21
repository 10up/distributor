<?php
/**
 * Admin list table for pulled posted
 *
 * @package  distributor
 */

namespace Distributor;

/**
 * List table class for pull screen
 */
class PullListTable extends \WP_List_Table {

	/**
	 * Stores all our connections
	 *
	 * @var array
	 */
	public $connection_objects = [];

	/**
	 * Store record of synced posts
	 *
	 * @var array
	 */
	public $sync_log = [];

	/**
	 * Save error to determine if we can show the pull table
	 *
	 * @var bool
	 */
	public $pull_error;

	/**
	 * Initialize pull table
	 *
	 * @since  0.8
	 */
	public function __construct() {
		parent::__construct(
			array(
				'ajax' => false,
			)
		);
	}

	/**
	 * Get pull tables columns
	 *
	 * @since  0.8
	 * @return array
	 */
	public function get_columns() {
		$columns = [
			'cb'   => '<input type="checkbox" />',
			'name' => esc_html__( 'Name', 'distributor' ),
			'date' => esc_html__( 'Date', 'distributor' ),
		];

		// Remove checkbox column on the Pulled view
		if ( isset( $_GET['status'] ) && 'pulled' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not needed.
			unset( $columns['cb'] );
		}

		return $columns;
	}

	/**
	 * Get table views
	 *
	 * @since  0.8
	 * @return array
	 */
	protected function get_views() {

		$current_status = ( empty( $_GET['status'] ) ) ? 'new' : sanitize_key( $_GET['status'] ); // @codingStandardsIgnoreLine No nonce needed.

		$request_uri = $_SERVER['REQUEST_URI'];

		$url         = add_query_arg(
			array(
				'paged' => false,
				's'     => false,
			),
			$request_uri
		);
		$new_url     = add_query_arg(
			array(
				'status' => 'new',
			),
			$url
		);
		$pulled_url  = add_query_arg(
			array(
				'status' => 'pulled',
			),
			$url
		);
		$skipped_url = add_query_arg(
			array(
				'status' => 'skipped',
			),
			$url
		);

		$status_links = [
			'new'     => '<a href="' . esc_url( $new_url ) . '" class="' . ( ( 'new' === $current_status ) ? 'current' : '' ) . '">' . esc_html__( 'New', 'distributor' ) . '</a>',
			'pulled'  => '<a href="' . esc_url( $pulled_url ) . '" class="' . ( ( 'pulled' === $current_status ) ? 'current' : '' ) . '">' . esc_html__( 'Pulled', 'distributor' ) . '</a>',
			'skipped' => '<a href="' . esc_url( $skipped_url ) . '" class="' . ( ( 'skipped' === $current_status ) ? 'current' : '' ) . '">' . esc_html__( 'Skipped', 'distributor' ) . '</a>',
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
			$no_new_actions = $this->get_bulk_actions();
			$this->_actions = $this->get_bulk_actions();
			// Filter documented in WordPress core.
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // @codingStandardsIgnoreLine valid filter name
			$this->_actions = array_intersect_assoc( $this->_actions, $no_new_actions );
			$two            = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . esc_html__( 'Select bulk action', 'distributor' ) . '</label>';
		echo '<select name="' . esc_attr( 'action' . $two ) . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";

		foreach ( $this->_actions as $name => $title ) {
			echo "\t" . '<option value="' . esc_attr( $name ) . '"' . ( 'edit' === $name ? ' class="hide-if-no-js"' : '' ) . '>' . esc_html( $title ) . "</option>\n";
		}

		echo "</select>\n";

		submit_button( esc_html__( 'Apply', 'distributor' ), 'action', '', false, array( 'id' => "doaction$two" ) );
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
	 * @param \WP_Post $post The current WP_Post object.
	 */
	public function column_date( $post ) {
		global $mode;

		if ( ! empty( $this->sync_log ) && ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) ) { // @codingStandardsIgnoreLine Nonce not needed.
			if ( isset( $this->sync_log[ $post->ID ] ) ) {
				if ( false === $this->sync_log[ $post->ID ] ) {
					echo '<span class="disabled">' . esc_html__( 'Skipped', 'distributor' ) . '</span>';
					return;
				} else {
					echo '<span class="disabled">' . esc_html__( 'Pulled', 'distributor' ) . '</span>';
					return;
				}
			}
		}

		if ( ! empty( $_GET['status'] ) && 'pulled' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce isn't required.
			if ( ! empty( $this->sync_log[ $post->ID ] ) ) {
				$syndicated_at = get_post_meta( $this->sync_log[ $post->ID ], 'dt_syndicate_time', true );

				if ( empty( $syndicated_at ) ) {
					esc_html_e( 'Post deleted.', 'distributor' );
				} else {
					$t_time = get_the_time( esc_html__( 'Y/m/d g:i:s a', 'distributor' ) );

					$time_diff = time() - $syndicated_at;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
						/* translators: %s: a human readable time */
						$h_time = sprintf( esc_html__( '%s ago', 'distributor' ), human_time_diff( $syndicated_at ) );
					} else {
						$h_time = gmdate( 'F j, Y', $syndicated_at );
					}

					/* translators: %s: time of pull */
					echo sprintf( esc_html__( 'Pulled %s', 'distributor' ), esc_html( $h_time ) );
				}
			}
		} else {
			if ( '0000-00-00 00:00:00' === $post->post_date ) {
				$t_time    = esc_html__( 'Unpublished', 'distributor' );
				$h_time    = esc_html__( 'Unpublished', 'distributor' );
				$time_diff = 0;
			} else {
				$t_time = get_the_time( esc_html__( 'Y/m/d g:i:s a', 'distributor' ) );
				$m_time = $post->post_date;
				$time   = get_post_time( 'G', true, $post );

				$time_diff = time() - $time;

				if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
					/* translators: %s: a human readable time */
					$h_time = sprintf( esc_html__( '%s ago', 'distributor' ), human_time_diff( $time ) );
				} else {
					$h_time = mysql2date( esc_html__( 'Y/m/d', 'distributor' ), $m_time );
				}
			}

			if ( 'publish' === $post->post_status ) {
				esc_html_e( 'Published', 'distributor' );
			} elseif ( 'future' === $post->post_status ) {
				if ( $time_diff > 0 ) {
					echo '<strong class="error-message">' . esc_html__( 'Missed schedule', 'distributor' ) . '</strong>';
				} else {
					esc_html_e( 'Scheduled', 'distributor' );
				}
			} else {
				esc_html_e( 'Last Modified', 'distributor' );
			}
			echo '<br />';
			if ( 'excerpt' === $mode ) {
				// Core filter, documented in wp-admin/includes/class-wp-posts-list-table.php.
				echo esc_html( apply_filters( 'post_date_column_time', $t_time, $post, 'date', $mode ) );
			} else {
				// Core filter, documented in wp-admin/includes/class-wp-posts-list-table.php.
				echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'post_date_column_time', $h_time, $post, 'date', $mode ) ) . '</abbr>';
			}
		}
	}

	/**
	 * Output standard table columns (not name)
	 *
	 * @param  array|\WP_Post $item Item to output.
	 * @param  string         $column_name Column name.
	 *
	 * @return string Url, post title, or empty string.
	 * @since  0.8
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'name':
				return $item['post_title'];
			case 'url':
				$url = get_post_meta( $item->ID, 'dt_external_connection_url', true );

				if ( empty( $url ) ) {
					$url = esc_html__( 'None', 'distributor' );
				}

				return $url;
		}

		return '';
	}

	/**
	 * Output name column wrapper
	 *
	 * @since 4.3.0
	 * @param \WP_Post $item Post object.
	 * @param string   $classes CSS classes.
	 * @param string   $data Column data.
	 * @param string   $primary Whether primary or not.
	 */
	protected function _column_name( $item, $classes, $data, $primary ) { // @codingStandardsIgnoreLine valid function name
		echo '<td class="' . esc_attr( $classes ) . ' page-title">';
		$this->column_name( $item );
		echo wp_kses_post( $this->handle_row_actions( $item, 'title', $primary ) );
		echo '</td>';
	}

	/**
	 * Output inner name column with actions
	 *
	 * @param  \WP_Post $item Post object.
	 * @since  0.8
	 */
	public function column_name( $item ) {

		global $connection_now;

		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$connection_type = 'external';
			$connection_id   = $connection_now->id;
		} else {
			$connection_type = 'internal';
			$connection_id   = $connection_now->site->blog_id;
		}

		$actions = [];
		$disable = false;

		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not needed.
			if ( isset( $this->sync_log[ $item->ID ] ) ) {
				$actions = [];
				$disable = true;
			} else {
				/**
				 * Filter the default value of the 'Pull in as draft' option in the pull ui
				 *
				 * @hook dt_pull_as_draft
				 *
				 * @param {bool}   $as_draft   Whether the 'Pull in as draft' option should be checked.
				 * @param {object} $connection The connection being used to pull from.
				 *
				 * @return {bool}
				 */
				$as_draft = apply_filters( 'dt_pull_as_draft', true, $connection_now );

				$draft = 'draft';
				if ( ! $as_draft ) {
					$draft = '';
				}

				$actions = [
					'pull' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&action=syndicate&_wp_http_referer=' . rawurlencode( $_SERVER['REQUEST_URI'] ) . '&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id . '&pull_post_type=' . $item->post_type . '&dt_as_draft=' . $draft ), 'bulk-distributor_page_pull' ) ), esc_html__( 'Pull', 'distributor' ) ),
					'view' => '<a href="' . esc_url( $item->link ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
					'skip' => sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=pull&action=skip&_wp_http_referer=' . rawurlencode( $_SERVER['REQUEST_URI'] ) . '&post=' . $item->ID . '&connection_type=' . $connection_type . '&connection_id=' . $connection_id ), 'dt_skip' ) ), esc_html__( 'Skip', 'distributor' ) ),
				];
			}
		} elseif ( 'skipped' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not needed.
			$actions = [
				'view' => '<a href="' . esc_url( $item->link ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
			];
		} elseif ( 'pulled' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not needed

			$new_post_id = ( ! empty( $this->sync_log[ (int) $item->ID ] ) ) ? $this->sync_log[ (int) $item->ID ] : 0;
			$new_post    = get_post( $new_post_id );

			if ( ! empty( $new_post ) ) {
				$actions = [
					'edit' => '<a href="' . esc_url( get_edit_post_link( $new_post_id ) ) . '">' . esc_html__( 'Edit', 'distributor' ) . '</a>',
					'view' => '<a href="' . esc_url( get_permalink( $new_post_id ) ) . '">' . esc_html__( 'View', 'distributor' ) . '</a>',
				];
			}
		}

		$title = $item->post_title;

		if ( empty( $title ) ) {
			$title = esc_html__( '(no title)', 'distributor' );
		}

		if ( $disable ) {
			echo '<div class="disabled">';
		}

		echo '<strong>' . esc_html( $title ) . '</strong>';
		echo wp_kses_post( $this->row_actions( $actions ) );

		if ( $disable ) {
			echo '</div>';
		}
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

		$columns  = $this->get_columns();
		$hidden   = get_hidden_columns( $this->screen );
		$sortable = [];

		$data = $this->table_data();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		/** Process bulk action */
		$this->process_bulk_action();

		$per_page = $this->get_items_per_page( 'pull_posts_per_page', get_option( 'posts_per_page' ) );

		$current_page = $this->get_pagenum();

		$remote_get_args = [
			'posts_per_page' => $per_page,
			'paged'          => $current_page,
			'post_type'      => $connection_now->pull_post_type ? $connection_now->pull_post_type : 'post',
			'orderby'        => 'ID', // this is because of include/exclude truncation
			'order'          => 'DESC', // default but specifying to be safe
		];

		if ( ! empty( $_GET['s'] ) ) { // @codingStandardsIgnoreLine Nonce isn't required.
			$remote_get_args['s'] = rawurlencode( $_GET['s'] ); // @codingStandardsIgnoreLine Nonce isn't required.
		}

		if ( is_a( $connection_now, '\Distributor\ExternalConnection' ) ) {
			$this->sync_log = get_post_meta( $connection_now->id, 'dt_sync_log', true );
		} else {
			$this->sync_log = [];

			$sync_log = get_option( 'dt_sync_log', [] );

			if ( ! empty( $sync_log[ $connection_now->site->blog_id ] ) ) {
				$this->sync_log = $sync_log[ $connection_now->site->blog_id ];
			}
		}

		if ( empty( $this->sync_log ) ) {
			$this->sync_log = [];
		}

		$skipped     = array();
		$syndicated  = array();
		$total_items = false;

		foreach ( $this->sync_log as $old_post_id => $new_post_id ) {
			if ( false === $new_post_id ) {
				$skipped[] = (int) $old_post_id;
			} else {
				$syndicated[] = (int) $old_post_id;
			}
		}

		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not required.
			// Sort from highest ID (newest) to low so the slice only affects later pagination.
			rsort( $skipped, SORT_NUMERIC );
			rsort( $syndicated, SORT_NUMERIC );

			// This is somewhat arbitrarily set to 200 and should probably be made filterable eventually.
			// IDs can get rather large and 400 easily exceeds typical header size limits.
			$post_ids = array_slice( array_merge( $skipped, $syndicated ), 0, 200, true );

			$remote_get_args['post__not_in'] = $post_ids;

			$remote_get_args['meta_query'] = [
				[
					'key'     => 'dt_syndicate_time',
					'compare' => 'NOT EXISTS',
				],
			];
		} elseif ( 'skipped' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not required.
			// Put most recently skipped items first.
			$skipped     = array_reverse( $skipped );
			$total_items = count( $skipped );
			$offset      = $per_page * ( $current_page - 1 );
			$post_ids    = array_slice( $skipped, $offset, $per_page, true );

			$remote_get_args['post__in'] = $post_ids;
			$remote_get_args['orderby']  = 'post__in';
			$remote_get_args['paged']    = 1;
		} else {
			// Put most recently pulled items first.
			$syndicated  = array_reverse( $syndicated );
			$total_items = count( $syndicated );
			$offset      = $per_page * ( $current_page - 1 );
			$post_ids    = array_slice( $syndicated, $offset, $per_page, true );

			$remote_get_args['post__in'] = $post_ids;
			$remote_get_args['orderby']  = 'post__in';
			$remote_get_args['paged']    = 1;
		}

		$remote_get = $connection_now->remote_get( $remote_get_args );

		if ( is_wp_error( $remote_get ) ) {
			$this->pull_error = true;

			return;
		}

		// Get total items retrieved from the remote request if not already set.
		if ( false === $total_items ) {
			$total_items = $remote_get['total_items'];
		}

		$this->set_pagination_args(
			[
				'total_items' => $total_items,
				'per_page'    => $per_page,
			]
		);

		foreach ( $remote_get['items'] as $item ) {
			$this->items[] = $item;
		}
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @since 4.3.0
	 * @param \WP_Post $post The current WP_Post object.
	 */
	public function column_cb( $post ) {
		if ( isset( $this->sync_log[ $post->ID ] ) ) {
			return;
		}
		?>
		<label class="screen-reader-text" for="cb-select-<?php echo (int) $post->ID; ?>">
		<?php /* translators: %s: the post title or draft */ ?>
		<?php echo esc_html( sprintf( esc_html__( 'Select %s', 'distributor' ), _draft_or_post_title() ) ); ?>
		</label>
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
		if ( empty( $_GET['status'] ) || 'new' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not required.
			$actions = [
				'bulk-syndicate' => esc_html__( 'Pull', 'distributor' ),
				'bulk-skip'      => esc_html__( 'Skip', 'distributor' ),
			];
		} elseif ( 'skipped' === $_GET['status'] ) { // @codingStandardsIgnoreLine Nonce not required.
			$actions = [
				'bulk-syndicate' => esc_html__( 'Pull', 'distributor' ),
			];
		} else {
			$actions = [];
		}

		return $actions;
	}

	/**
	 * Adds a hook after the bulk actions dropdown above and below the list table
	 *
	 * @param string $which Whether above or below the table.
	 */
	public function extra_tablenav( $which ) {
		global $connection_now;

		if ( $connection_now && $connection_now->pull_post_types && $connection_now->pull_post_type ) :
			?>

			<div class="alignleft actions">
				<label for="pull_post_type" class="screen-reader-text">Content to Pull</label>
				<select id="pull_post_type" name="pull_post_type">
					<?php foreach ( $connection_now->pull_post_types as $post_type ) : ?>
						<option <?php selected( $connection_now->pull_post_type, $post_type['slug'] ); ?> value="<?php echo esc_attr( $post_type['slug'] ); ?>">
							<?php echo esc_html( $post_type['name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<input type="submit" name="filter_action" id="pull_post_type_submit" class="button" value="<?php esc_attr_e( 'Filter', 'distributor' ); ?>">

				<?php
				if ( empty( $_GET['status'] ) || 'pulled' !== $_GET['status']  ) :
					// Filter documented above.
					$as_draft = apply_filters( 'dt_pull_as_draft', true, $connection_now );
				?>

					<label class="as-draft" for="dt-as-draft-<?php echo esc_attr( $which ); ?>">
						<input type="checkbox" id="dt-as-draft-<?php echo esc_attr( $which ); ?>" name="dt_as_draft" value="draft" <?php checked( $as_draft ); ?>> <?php esc_html_e( 'Pull in as draft', 'distributor' ); ?>
					</label>
				<?php endif; ?>
			</div>

			<?php
		endif;

		/**
		 * Action fired when extra table nav is generated.
		 *
		 * @since 1.0
		 * @hook dt_pull_filters
		 */
		do_action( 'dt_pull_filters' );
	}
}
