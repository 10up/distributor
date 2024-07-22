import { pluginIcon } from './components/plugin-icon';

import { Icon, Modal, Button, Flex } from '@wordpress/components';
import { select, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';

const { document, dt, dtGutenberg, MouseEvent } = window;

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
	const [ isOpen, setOpen ] = useState( false );
	const openModal = () => setOpen( true );
	const closeModal = () => setOpen( false );

	if ( ! parseInt( dtGutenberg.unlinked ) ) {
		return (
			<div>
				<span id="distributed-from">
					{ sprintf(
						/* translators: 1: Syndication date and time. */
						__(
							'Pulled & linked on %1$s from %2$s',
							'distributor'
						),
						dtGutenberg.syndicationTime,
						dtGutenberg.originalLocationName
					) }
				</span>
				<span id="distributed-data">
					{ __( 'Updating the ', 'distributor' ) }
					<a href={ dtGutenberg.postUrl }>
						{ __( 'Original Content', 'distributor' ) }
					</a>
					{ ', ' }
					{ __(
						'will update this post automatically.',
						'distributor'
					) }
				</span>
				<span id="distributed-unlink" onClick={ openModal }> {/* eslint-disable-line */}
					<a href='javascript:void(0);'> {/* eslint-disable-line */}
						<span className="dashicons dashicons-editor-unlink"></span>
						{ __( 'Unlink from Original', 'distributor' ) }
					</a>
				</span>
				{ isOpen && (
					<Modal
						title="Unlink from Original"
						icon={ pluginIcon }
						size={ 50 }
						onRequestClose={ closeModal }
						className="distributed-modal-popup"
						overlayClassName="distributed-modal-overlay"
					>
						<p
							dangerouslySetInnerHTML={ {
								__html: sprintf(
									/* translators: %1$s: Original Content URL Opening Tag, %2$s: Original Content URL Closing Tag */
									__(
										'Unlinking from the %1$sOriginal Content%2$s will stop updating this post automatically.',
										'distributor'
									),
									'<a href="' + dtGutenberg.postUrl + '">',
									'</a>'
								),
							} }
						/>
						<Flex justify="flex-start" className={ 'actions' }>
							<Button
								icon={ <Icon icon="editor-unlink" /> }
								variant="secondary"
								href={ dtGutenberg.unlinkNonceUrl }
							>
								{ __( 'Unlink', 'distributor' ) }
							</Button>
							<Button variant="link" onClick={ closeModal }>
								{ __( 'Cancel', 'distributor' ) }
							</Button>
						</Flex>
					</Modal>
				) }
			</div>
		);
	}
	return (
		<div>
			<span id="distributed-from">
				{ sprintf(
					/* translators: 1: Syndication date and time. */
					__( 'Pulled on %1$s from %2$s', 'distributor' ),
					dtGutenberg.syndicationTime,
					dtGutenberg.originalLocationName
				) }
			</span>
			<span id="distributed-data">
				{ __( 'This post has been unlinked from the ', 'distributor' ) }
				<a href={ dtGutenberg.postUrl }>
					{ __( 'Original Content', 'distributor' ) }
				</a>
				{ '.' }
			</span>
			<span id="distributed-restorelink" onClick={ openModal }> {/* eslint-disable-line */}
				<span className="dashicons dashicons-admin-links"></span>
				<a href='javascript:void(0);'> {/* eslint-disable-line */}
					{ __( 'Restore link to Original', 'distributor' ) }
				</a>
			</span>
			{ isOpen && (
				<Modal
					title="Restore link to Original"
					icon={ pluginIcon }
					size={ 50 }
					onRequestClose={ closeModal }
					className="distributed-modal-popup"
					overlayClassName="distributed-modal-overlay"
				>
					<span id="distributed-data">
						{ __( 'Restoring the link to the ', 'distributor' ) }
						<a href={ dtGutenberg.postUrl }>
							{ __( 'Original Content', 'distributor' ) }
						</a>
						{ __(
							' will start updating this post automatically from the Original, overwriting current content.',
							'distributor'
						) }
					</span>
					<div className="actions">
						<a href={ dtGutenberg.linkNonceUrl } className="button">
							<span className="dashicons dashicons-admin-links"></span>
							{ __( 'Restore link', 'distributor' ) }
						</a>
						<span id="close" onClick={ closeModal } aria-label="Cancel"> {/* eslint-disable-line */}
							<a href='javascript:void(0);'> {/* eslint-disable-line */}
								{ __( 'Cancel', 'distributor' ) }
							</a>
						</span>
					</div>
				</Modal>
			) }
		</div>
	);
};

/**
 * Create the Distributor icon
 */
const DistributorIcon = () => (
	<Icon className="components-panel__icon" icon={ pluginIcon } size={ 20 } />
);

/**
 * Create the Distributor title
 */
const isUnlinkedContent = parseInt( dtGutenberg.unlinked ) !== 0;

const DistributorTitle = () => {
	return isUnlinkedContent
		? __( 'Unlinked Content', 'distributor' )
		: __( 'Pulled Content', 'distributor' );
};

/**
 * Add the Distributor panel to Gutenberg
 */
const DistributorPlugin = () => {
	// eslint-disable-next-line no-shadow
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);

	// eslint-disable-next-line no-shadow
	const postStatus = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostAttribute( 'status' )
	);

	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const distributorTopMenu = document.querySelector(
		'#wp-admin-bar-distributor'
	);

	// eslint-disable-next-line no-shadow
	const post = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPost()
	);

	// Ensure the user has proper permissions.
	if (
		dtGutenberg.noPermissions &&
		1 === parseInt( dtGutenberg.noPermissions )
	) {
		return null;
	}

	// Ensure we are on a supported post type.
	if (
		dtGutenberg.supportedPostTypes &&
		dtGutenberg.supportedPostTypes[ postType ] === undefined
	) {
		return null;
	}

	// Make the post title and status available to the top menu.
	dt.postTitle = post.title;
	dt.postStatus = post.status;

	// If we are on a non-supported post status, change what we show.
	if (
		dtGutenberg.supportedPostStati &&
		! dtGutenberg.supportedPostStati.includes( postStatus )
	) {
		distributorTopMenu?.classList.add( 'hide' );
		return (
			<PluginDocumentSettingPanel
				title={ <DistributorTitle /> }
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
			title={ <DistributorTitle /> }
			icon={ DistributorIcon }
			className="distributor-panel"
		>
			<RenderShowAdminBar />
			<RenderDistributionInfo />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'distributor-plugin', { render: DistributorPlugin } );
