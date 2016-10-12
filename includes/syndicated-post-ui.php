<?php

namespace Syndicate\SyndicatedPostUI;

/**
 * Setup actions and filters
 *
 * @since 0.8
 */
add_action( 'plugins_loaded', function() {
	add_action( 'edit_form_top', __NAMESPACE__ . '\syndicated_message', 10, 1 );
} );


function syndicated_message( $post ) {

	$original_blog_id = get_post_meta( $post->ID, 'sy_original_blog_id', true );
	$original_post_id = get_post_meta( $post->ID, 'sy_original_post_id', true );

	if ( empty( $original_post_id ) ) {
		return;
	}

	switch_to_blog( $original_blog_id );
	$post_url = get_permalink( $original_post_id );
	$blog_name = get_bloginfo( 'name' );
	restore_current_blog();

	if ( empty( $blog_name ) ) {
		$blog_name = sprintf( esc_html__( 'Blog #%d', 'syndicate' ), $original_blog_id );
	}
	
	?>
	<div class="updated syndicate-status">
		<p>
			<?php echo sprintf( __( 'Reposted from <a href="%s">%s</a>.', 'syndicate' ), esc_url( $post_url ), esc_html( $blog_name ) ); ?> 
			<span><?php echo sprintf( __( 'The original post will update this version unless you <a href="#" data-post-id="52">unlink from the original.</a>', 'syndicate' ) ); ?></span>
		</p>
	</div>
	<?php
}
