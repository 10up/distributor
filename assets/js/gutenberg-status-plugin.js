import { wp, dtGutenberg } from 'window'

const { registerPlugin } = wp.plugins;
const { PluginPostStatusVisibility } = wp.editPost;
const { __ } = wp.i18n;
const { PanelRow } =  wp.components;

const renderDistributedFrom = () => {
	return(
		<PluginPostStatusVisibility.Fill>
				<p>
					{ __( 'Distributed on: ' ) }
					<strong> { dtGutenberg.syndicationTime } </strong>
				</p>
		</PluginPostStatusVisibility.Fill>
	)
}

const renderDistributedTo = () => {
	return(
		<PanelRow>
			<PluginPostStatusVisibility.Fill>
					<p>
					{ wp.i18n.sprintf(
						wp.i18n.__( "Distributed to %1$s connection%2$s.", 'distributor' ),
						dtGutenberg.syndicationCount,
						'1' === dtGutenberg.syndicationCount ? '' : 's'
					) }
					</p>
			</PluginPostStatusVisibility.Fill>
		</PanelRow>
	)
}

const renderFunction = ( dtGutenberg.syndicationCount > 0  ) ? renderDistributedTo : renderDistributedFrom

registerPlugin( 'distributor-status-panel', { render: renderFunction } );