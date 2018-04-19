import { wp, dtGutenberg } from 'window'
const { Button, PanelBody } = wp.components
const { dispatch } = wp.data
const { Fragment } = wp.element
const { __ } = wp.i18n
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;

export const DistributedToSidebar = () => {

	return (
		<Fragment>
			<PluginSidebar
				name="distributor-sidebar"
				title="Distributor Details"
			>
				<PanelBody>
					<p>
						{ wp.i18n.sprintf(
							wp.i18n.__( "Distributed to %1$s connection%2$s.", 'distributor' ),
							dtGutenberg.syndicationCount,
							'1' === dtGutenberg.syndicationCount ? '' : 's'
						) }
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
