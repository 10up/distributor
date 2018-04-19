import { wp, dtGutenberg } from 'window'
const { Button, PanelBody } = wp.components
const { dispatch } = wp.data
const { Fragment } = wp.element
const { __ } = wp.i18n
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;

export const DistributedFromSidebar = () => {

	return (
		<Fragment>
			<PluginSidebar
				name="distributor-sidebar"
				title="Distributor Details"
			>
				<PanelBody>
					<p>
						{ __( 'Distributed on: ' ) }
						<strong> { dtGutenberg.syndicationTime } </strong>
					</p>
				</PanelBody>
			</PluginSidebar>
			<PluginSidebarMoreMenuItem
				target="distributor-sidebar"
			>
				{ __( 'Distributor Details' ) }
			</PluginSidebarMoreMenuItem>
		</Fragment>
	)
}
