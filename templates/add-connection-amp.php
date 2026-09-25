<?php
/**
 * Add connection AMP template
 *
 * @package  distributor
 */
//phpcs:ignoreFile WordPressVIPMinimum.Security.Mustache.OutputNotation
?>

<script id="dt-add-connection" type="text/plain" template="amp-mustache" data-ampdevmode>
	{{#connection}}
		<button
			class="add-connection{{#added}} added{{/added}}{{#syndicated}} syndicated{{/syndicated}}"
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
	{{/connection}}
</script>
