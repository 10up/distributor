<?php
/**
 * Show connections template
 *
 * @package  distributor
 */

?>

<script id="dt-show-connections" type="text/html">
	<div class="inner">
	<# if ( ! _.isEmpty( connections ) ) { #>
		<?php /* translators: %s the post title */ ?>
		<p><?php echo sprintf( esc_html__( 'Distribute &quot;%s&quot; to other connections.', 'distributor' ), '{{ postTitle }}' ); ?></p>

		<div class="connections-selector">
			<div>
				<# if ( 5 < _.keys( connections ).length ) { #>
					<input type="text" id="dt-connection-search" placeholder="<?php esc_attr_e( 'Search available connections', 'distributor' ); ?>">
				<# } #>
				<div class="new-connections-list">
					<# for ( var key in connections ) { #>
						<button
							class="add-connection<# if ( ! _.isEmpty( connections[ key ]['syndicated'] ) ) { #> syndicated<# } #>"
							data-connection-type="{{ connections[ key ]['type'] }}"
							data-connection-id="{{ connections[ key ]['id'] }}"
							<# if ( ! _.isEmpty( connections[ key ]['syndicated'] ) && connections[ key ]['syndicated'] ) { #>disabled<# } #>
						>
							<# if ( 'external' === connections[ key ]['type'] ) { #>
								<span>{{ connections[ key ]['name'] }}</span>
							<# } else { #>
								<span>{{ connections[ key ]['url'] }}</span>
							<# } #>
							<# if ( ! _.isEmpty( connections[ key ]['syndicated'] ) && connections[ key ]['syndicated'] ) { #>
								<a href="{{ connections[ key ]['syndicated'] }}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
							<# } #>
						</button>
					<# } #>
				</div>

				<button class="button button-primary selectall-connections unavailable"><?php esc_html_e( 'Select All', 'distributor' ); ?></button>
			</div>
		</div>
		<div class="connections-selected empty">
			<header class="with-selected">
				<span class="selected-connections-text"></span>
				<button class="button button-link selectno-connections unavailable"><?php esc_html_e( 'Clear', 'distributor' ); ?></button>
			</header>
			<header class="no-selected">
				<?php esc_html_e( 'No connections selected', 'distributor' ); ?>
			</header>

			<div class="selected-connections-list"></div>

			<div class="action-wrapper">
				<?php
				$as_draft = ( 'draft' !== $post->post_status ) ? true : false;
				/**
				* Filter whether the 'As Draft' option appears in the push ui.
				*
				* @hook dt_allow_as_draft_distribute
				*
				* @param {bool}    $as_draft   Whether the 'As Draft' option should appear.
				* @param {object}  $connection The connection being used to push.
				* @param {WP_Post} $post       The post being pushed.
				*
				* @return {bool} Whether the 'As Draft' option should appear.
				*/
				$as_draft = apply_filters( 'dt_allow_as_draft_distribute', $as_draft, $connection = null, $post );
				?>
				<button class="button button-primary syndicate-button"><?php esc_html_e( 'Distribute', 'distributor' ); ?></button> <?php if ( $as_draft ) : ?><label class="as-draft" for="dt-as-draft"><input type="checkbox" id="dt-as-draft" checked> <?php esc_html_e( 'As draft', 'distributor' ); ?></label><?php endif; ?>
			</div>

		</div>

		<div class="messages">
			<div class="dt-success">
				<?php esc_html_e( 'Post successfully distributed.', 'distributor' ); ?>
			</div>
			<div class="dt-error">
				<?php esc_html_e( 'There were some issues distributing the post.', 'distributor' ); ?>
				<ul class="details"></ul>
			</div>
		</div>

	<# } else { #>
		<p class="no-connections-notice">
			<?php esc_html_e( 'No connections available for distribution.', 'distributor' ); ?>
		</p>
	<# } #>
	</div>
</script>
