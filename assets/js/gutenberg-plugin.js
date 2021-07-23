import { wp, dtGutenberg } from 'window';
import PluginIcon from '../img/icon.svg'; // eslint-disable-line no-unused-vars

const { Icon } = wp.components; // eslint-disable-line no-unused-vars
const { select, useSelect } = wp.data;
const { PluginDocumentSettingPanel } = wp.editPost; // eslint-disable-line no-unused-vars
const { __, sprintf } = wp.i18n;
const { registerPlugin } = wp.plugins;

/**
 * Add ability to show the admin bar, if needed
 */
const RenderShowAdminBar = () => { // eslint-disable-line no-unused-vars
	const bodyClasses = document.body.classList;
	const isFullScreenMode = select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' );
	const distributorTopMenu = document.querySelector( '#wp-admin-bar-distributor' );
	const distributorAdminItem = document.querySelector( '#wp-admin-bar-distributor > a' );

	// Don't show anything if this is a distributed item
	if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		return null;
	}

	if ( ! distributorTopMenu || ! distributorAdminItem ) {
		return (
			<div className="distributor-toggle">
				<span>{ __( 'Refresh page to see distribution options', 'distributor' ) }</span>
			</div>
		);
	}

	return (
		<div className="distributor-toggle">
			<button
				className="components-button is-secondary"
				type="button"
				onClick={ () => {
					const mouseEvent = new MouseEvent( 'mouseenter' );

					if ( isFullScreenMode ) {
						bodyClasses.add( 'is-showing-distributor' );
					} else {
						bodyClasses.remove( 'is-showing-distributor' );
					}

					distributorTopMenu.classList.toggle( 'hover' );
					distributorAdminItem.dispatchEvent( mouseEvent );
				} }
			>
				{ sprintf( __( 'Distribute %1$s', 'distributor' ), dtGutenberg.postTypeSingular || 'Content' ) }
			</button>
		</div>
	);
};

/**
 * Render the draft message
 */
const RenderDraftMessage = () => { // eslint-disable-line no-unused-vars
	if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		return null;
	}

	return (
		<p>
			{ __( 'Distribution options available once published', 'distributor' ) }
		</p>
	);
};

/**
 * Render the distribution information, if needed
 */
const RenderDistributionInfo = () => { // eslint-disable-line no-unused-vars
	if ( 0 < parseInt( dtGutenberg.syndicationCount ) ) {
		return <RenderDistributedTo />;
	} else if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		return <RenderDistributedFrom />;
	}

	return null;
};

/**
 * Render the distributed to component
 */
const RenderDistributedTo = () => { // eslint-disable-line no-unused-vars
	return(
		<span id='distributed-to'>
			{ sprintf( __( 'Distributed to %1$s connection%2$s.', 'distributor' ),
				dtGutenberg.syndicationCount,
				'1' === dtGutenberg.syndicationCount ? '' : 's' ) }
		</span>
	);
};

/**
 * Render the distributed from component
 */
const RenderDistributedFrom = () => { // eslint-disable-line no-unused-vars
	return(
		<span id='distributed-from'>
			{ __( 'Distributed on: ', 'distributor' ) }
			<strong> { dtGutenberg.syndicationTime } </strong>
		</span>
	);
};

/**
 * Create the Distributor icon
 */
const DistributorIcon = () => (
	<Icon
		className="components-panel__icon"
		icon={ <PluginIcon /> }
		size={ 20 }
	/>
);

/**
 * Add the Distributor panel to Gutenberg
 */
const DistributorPlugin = () => {
	// Ensure the user has proper permissions
	if ( dtGutenberg.noPermissions && 1 === parseInt( dtGutenberg.noPermissions ) ) {
		return null;
	}

	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType(),
	'' );
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostAttribute( 'status' ),
	'' );

	// Ensure we are on a supported post type
	if ( dtGutenberg.supportedPostTypes && dtGutenberg.supportedPostTypes[ postType ] === undefined ) {
		return null;
	}

	// If we are on a non-supported post status, change what we show
	if ( dtGutenberg.supportedPostStati && ! dtGutenberg.supportedPostStati.includes( postStatus ) ) {
		return (
			<PluginDocumentSettingPanel
				title={ __( 'Distributor', 'distributor' ) }
				icon={ DistributorIcon }
				className="distributor-panel"
			>
				<RenderDraftMessage />
				<RenderDistributionInfo />
			</PluginDocumentSettingPanel>
		);
	}

	return (
		<PluginDocumentSettingPanel
			title={ __( 'Distributor', 'distributor' ) }
			icon={ DistributorIcon }
			className="distributor-panel"
		>
			<RenderShowAdminBar />
			<RenderDistributionInfo />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'distributor-plugin', { render: DistributorPlugin } );
