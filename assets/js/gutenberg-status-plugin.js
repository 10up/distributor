import { wp, dtGutenberg } from 'window';

if ( 'undefined' !== typeof wp.editPost.PluginPostStatusInfo ) {
	const { registerPlugin } = wp.plugins;
	const { __ } = wp.i18n;
	const { PluginPostStatusInfo } = wp.editPost; // eslint-disable-line no-unused-vars

	const renderDistributedFrom = () => {
		return(
			<PluginPostStatusInfo>
				<span id='distributed-from'>
					{ __( 'Distributed on: ' ) }
					<strong> { dtGutenberg.syndicationTime } </strong>
				</span>
			</PluginPostStatusInfo>
		);
	};

	const renderDistributedTo = () => {
		return(
			<PluginPostStatusInfo>
				<span id='distributed-to'>
					{ wp.i18n.sprintf( wp.i18n.__( 'Distributed to %1$s connection%2$s.', 'distributor' ),
						dtGutenberg.syndicationCount,
						'1' === dtGutenberg.syndicationCount ? '' : 's' ) }
				</span>
			</PluginPostStatusInfo>
		);
	};

	if ( 0 < parseInt( dtGutenberg.syndicationCount ) ) {
		registerPlugin( 'distributor-status-panel', { render: renderDistributedTo } );
	} else if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		registerPlugin( 'distributor-status-panel', { render: renderDistributedFrom } );
	}
}
