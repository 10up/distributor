<?php
/**
 * Add connection template
 *
 * @package  distributor
 */

?>

<script id="dt-add-connection" type="text/html">
	<button class="<# if (selectedConnections[connection.type + connection.id]) { #>added<# }#> add-connection <# if (connection.syndicated) { #>syndicated<# } #>" data-connection-type="{{ connection.type }}" data-connection-id="{{ connection.id }}" <# if (connection.syndicated) { #>disabled<# } #>>
		<# if ('internal' === connection.type) { #>
			<span>{{ connection.url }}</span>
		<# } else { #>
			<span>{{{ connection.name }}}</span>
		<# } #>

		<# if (connection.syndicated) { #>
			<a href="{{ connection.syndicated }}"><?php esc_html_e( 'View', 'distributor' ); ?></a>
		<# } #>
	</button>
</script>
