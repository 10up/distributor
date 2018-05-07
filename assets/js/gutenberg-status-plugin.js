import { wp, dtGutenberg } from 'window';

const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { __ } = wp.i18n;
const { PanelRow } =  wp.components;

const renderDistributedFrom = () => {
	return(
		<PluginPostStatusInfo>
				<span id='distributed-from'>
					{ __( 'Distributed on: ' ) }
					<strong> { dtGutenberg.syndicationTime } </strong>
					<a className='open-distributor-help'>(?)</a>
				</span>
		</PluginPostStatusInfo>
	)
}

const renderDistributedTo = () => {
	return(
		<PluginPostStatusInfo>
				<span id='distributed-to'>
				{ wp.i18n.sprintf(
					wp.i18n.__( "Distributed to %1$s connection%2$s.", 'distributor' ),
					dtGutenberg.syndicationCount,
					'1' === dtGutenberg.syndicationCount ? '' : 's'
				) }
				</span>
		</PluginPostStatusInfo>
	)
}

const renderFunction = ( dtGutenberg.syndicationCount > 0  ) ? renderDistributedTo : renderDistributedFrom

//registerPlugin( 'distributor-status-panel', { render: renderFunction } );

