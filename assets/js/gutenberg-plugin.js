import { pluginIcon } from './components/plugin-icon';

import { Icon } from '@wordpress/components';
import { select, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';

const { document, dtGutenberg, MouseEvent } = window;

/**
 * Add ability to show the admin bar, if needed
 */
const RenderShowAdminBar = () => {
	const bodyClasses = document.body.classList;

	// Don't show anything if this is a distributed item
	if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		return null;
	}

	const distributorTopMenu = document.querySelector(
		'#wp-admin-bar-distributor'
	);

	const distributorAdminItem = document.querySelector(
		'#wp-admin-bar-distributor > a'
	);

	if ( ! distributorTopMenu || ! distributorAdminItem ) {
		return (
			<div className="distributor-toggle">
				<span>
					{ __(
						'Refresh page to see distribution options',
						'distributor'
					) }
				</span>
			</div>
		);
	}

	const isFullScreenMode =
		select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' );

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
				{ sprintf(
					/* translators: 1: Post type or generic term content. */
					__( 'Distribute %1$s', 'distributor' ),
					dtGutenberg.postTypeSingular.toLowerCase() ||
						_x(
							'content',
							'generic term for post content',
							'distributor'
						)
				) }
			</button>
		</div>
	);
};

/**
 * Render the draft message
 */
const RenderDraftMessage = () => {
	if ( 0 !== parseInt( dtGutenberg.syndicationTime ) ) {
		return null;
	}

	return (
		<p>
			{ __(
				'Distribution options available once published',
				'distributor'
			) }
		</p>
	);
};

/**
 * Render the distribution information, if needed
 */
const RenderDistributionInfo = () => {
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
const RenderDistributedTo = () => {
	return (
		<span id="distributed-to">
			{ sprintf(
				/* translators: 1: Number of connections content distributed to. */
				_n(
					'Distributed to %1$s connection.',
					'Distributed to %1$s connections.',
					dtGutenberg.syndicationCount * 1, // Syndication count is a string, so we need to convert it to a number.
					'distributor'
				),
				dtGutenberg.syndicationCount
			) }
		</span>
	);
};

/**
 * Render the distributed from component
 */
const RenderDistributedFrom = () => {
	return (
		<span id="distributed-from">
			{ sprintf(
				/* translators: 1: Syndication date and time. */
				__( 'Distributed on: %1$s', 'distributor' ),
				dtGutenberg.syndicationTime
			) }
		</span>
	);
};

/**
 * Create the Distributor icon
 */
const DistributorIcon = () => (
	<Icon className="components-panel__icon" icon={ pluginIcon } size={ 20 } />
);

/**
 * Add the Distributor panel to Gutenberg
 */
const DistributorPlugin = () => {
	// Ensure the user has proper permissions
	if (
		dtGutenberg.noPermissions &&
		1 === parseInt( dtGutenberg.noPermissions )
	) {
		return null;
	}

	// eslint-disable-next-line no-shadow, react-hooks/rules-of-hooks -- permission checks are needed.
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);

	// eslint-disable-next-line no-shadow, react-hooks/rules-of-hooks -- permission checks are needed.
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostAttribute( 'status' )
	);

	// Ensure we are on a supported post type
	if (
		dtGutenberg.supportedPostTypes &&
		dtGutenberg.supportedPostTypes[ postType ] === undefined
	) {
		return null;
	}

	const distributorTopMenu = document.querySelector(
		'#wp-admin-bar-distributor'
	);

	// eslint-disable-next-line no-shadow, react-hooks/rules-of-hooks -- permission checks are needed.
	const post = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPost()
	);
	// Make the post title available to the top menu.
	window.dt.postTitle = post.title;

	// If we are on a non-supported post status, change what we show
	if (
		dtGutenberg.supportedPostStati &&
		! dtGutenberg.supportedPostStati.includes( postStatus )
	) {
		distributorTopMenu?.classList.add( 'hide' );
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

	distributorTopMenu?.classList.remove( 'hide' );
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
