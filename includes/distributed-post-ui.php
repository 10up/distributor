<?php
/**
 * Distributed post UI
 *
 * @package  distributor
 */

namespace Distributor\DistributedPostUI;

use Distributor\EnqueueScript;

/**
 * Setup actions and filters
 *
 * @since 1.0
 */
function setup() {
	add_action(
		'plugins_loaded',
		function() {
			add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_post_scripts_styles' );
			add_action( 'post_submitbox_misc_actions', __NAMESPACE__ . '\distributed_to' );
			add_action( 'in_admin_header', __NAMESPACE__ . '\add_help_tab' );
		}
	);
}

/**
 * Register distributor help tab
 */
function add_help_tab() {
	global $pagenow;

	if ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) {
		return;
	}

	if ( empty( $_GET['post'] ) ) { // @codingStandardsIgnoreLine Nonce validation not necessary here.
		return;
	}

	$post_id = intval( $_GET['post'] ); // @codingStandardsIgnoreLine Nonce not necessary, simply type-casting data here from the admin.

	$connection_map = get_post_meta( $post_id, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	$screen = get_current_screen();

	$post_type_object = get_post_type_object( get_post_type( $post_id ) );

	// Add my_help_tab if current screen is My Admin Page
	$screen->add_help_tab(
		array(
			'id'      => 'distributer',
			'title'   => esc_html__( 'Distributor', 'distributor' ),
			/* translators: %1$s: Post type singular name, %2$s: Post type singular name, %3$s: Post type name */
			'content' => '<p>' . sprintf( esc_html__( 'The number of connections this %1$s has been distributed to is shown in the publish meta box. If this %2$s is deleted, it could have ramifications across all those %3$s.', 'distributor' ), esc_html( strtolower( $post_type_object->labels->singular_name ) ), esc_html( strtolower( $post_type_object->labels->singular_name ) ), esc_html( strtolower( $post_type_object->labels->name ) ) ) . '</p>',
		)
	);
}

/**
 * Output distributed to number
 *
 * @param  \WP_Post $post Post object.
 * @since  0.8
 */
function distributed_to( $post ) {
	$connection_map = get_post_meta( $post->ID, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	$total_connections = 0;

	if ( ! empty( $connection_map['internal'] ) ) {
		$total_connections = $total_connections + count( $connection_map['internal'] );
	}

	if ( ! empty( $connection_map['external'] ) ) {
		$total_connections = $total_connections + count( $connection_map['external'] );
	}
	?>

	<div class="misc-pub-section curtime misc-pub-curtime">
		<span id="distributed-to">
			<?php
			echo wp_kses_post(
				sprintf(
					/* translators: 1: Number of connections content distributed to. */
					_n(
						'Distributed to %1$s connection.',
						'Distributed to %1$s connections.',
						(int) $total_connections, // Syndication count is a string, so we need to convert it to a number.
						'distributor'
					),
					'<strong>' . esc_html( $total_connections ) . '</strong>'
				)
			);
			?>
			<a class="open-distributor-help">(?)</a>
		</span>
	</div>

	<?php
}


/**
 * Enqueue admin scripts/styles for post.php
 *
 * @param  string $hook WP hook.
 * @since  0.8
 */
function enqueue_post_scripts_styles( $hook ) {
	if ( 'post-new.php' !== $hook && 'post.php' !== $hook ) {
		return;
	}

	global $post;

	$connection_map = get_post_meta( $post->ID, 'dt_connection_map', true );

	if ( empty( $connection_map ) ) {
		return;
	}

	$admin_distributed_post_script = new EnqueueScript(
		'dt-admin-distributed-post',
		'admin-distributed-post.min'
	);
	$admin_distributed_post_script->load_in_footer()->enqueue();

	wp_enqueue_style(
		'dt-admin-distributed-post',
		plugins_url( '/dist/css/admin-distributed-post.min.css', __DIR__ ),
		array(),
		$admin_distributed_post_script->get_version()
	);
}
