<?php
/**
 * Show connections AMP template
 *
 * @package  distributor
 */
//phpcs:ignoreFile WordPressVIPMinimum.Security.Mustache.OutputNotation
?>

<script id="dt-show-connections" type="text/plain" template="amp-mustache" data-ampdevmode>
	<div class="inner">
	{{#foundConnections}}
		<?php /* translators: %s the post title */ ?>
		<p><?php echo sprintf( esc_html__( 'Distribute &quot;%s&quot; to other connections.', 'distributor' ), esc_html( get_the_title( $post->ID ) ) ); ?></p>

		<div class="connections-selector">
			<div>
				{{#showSearch}}
					<input type="text" id="dt-connection-search" placeholder="<?php esc_attr_e( 'Search available connections', 'distributor' ); ?>">
				{{/showSearch}}
				<div class="new-connections-list">
					{{#connections}}
						<button
							class="add-connection{{#syndicated}} syndicated{{/syndicated}}"
							data-connection-type="{{{type}}}"
							data-connection-id="{{{id}}}"
						>
								{{#internal}}
									<span>{{{url}}}</span>
								{{/internal}}
								{{^internal}}
									<span>{{{name}}}</span>
								{{/internal}}

								{{#syndicated}}
									<a href="{{{syndicated}}}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
								{{/syndicated}}
						</button>
					{{/connections}}
				</div>

				<button class="button button-primary selectall-connections unavailable"><?php esc_html_e( 'Select All', 'distributor' ); ?></button>
			</div>
		</div>

		<div class="connections-selected empty">
			<header class="with-selected">
				<?php esc_html_e( 'Selected connections', 'distributor' ); ?>
				<button class="button button-link selectno-connections unavailable"><?php esc_html_e( 'Clear', 'distributor' ); ?></button>
			</header>
			<header class="no-selected">
				<?php esc_html_e( 'No connections selected', 'distributor' ); ?>
			</header>

			<div class="selected-connections-list"></div>

			<div class="action-wrapper">
				<input type="hidden" id="dt-post-status" value="<?php echo esc_attr( $post->post_status ); ?>">
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

	{{/foundConnections}}
	{{^foundConnections}}
		<p class="no-connections-notice">
			<?php esc_html_e( 'No connections available for distribution.', 'distributor' ); ?>
		</p>
	{{/foundConnections}}
	</div>
</script>
