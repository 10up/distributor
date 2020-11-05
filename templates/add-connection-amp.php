<?php
/**
 * Add connection AMP template
 */
?>

<script id="dt-add-connection" type="text/plain" template="amp-mustache" data-ampdevmode>
	<button class="{{#selectedConnections[connection.type + connection.id]}}added{{/selectedConnections[connection.type + connection.id]}} add-connection {{#connection.syndicated}}syndicated{{/connection.syndicated}}" data-connection-type="{{{connection.type}}}" data-connection-id="{{{connection.id}}}" {{#connection.syndicated}}disabled{{/connection.syndicated}}>
		<span>{{{connection.name}}}</span>

		{{#connection.syndicated}}
			<a href="{{{connection.syndicated}}}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
		{{/connection.syndicated}}
	</button>
</script>
