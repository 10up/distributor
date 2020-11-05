<?php
/**
 * Add connection AMP template
 */
?>

<script id="dt-add-connection" type="text/plain" template="amp-mustache" data-ampdevmode>
	{{#connection}}
		<button
			class="add-connection{{#added}} added{{/added}}{{#syndicated}} syndicated{{/syndicated}}"
			data-connection-type="{{{type}}}"
			data-connection-id="{{{id}}}"
			{{#syndicated}} disabled="true"{{/syndicated}}
		>
			<span>{{{name}}}</span>

			{{#syndicated}}
				<a href="{{{syndicated}}}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
			{{/syndicated}}
		</button>
	{{/connection}}
</script>
