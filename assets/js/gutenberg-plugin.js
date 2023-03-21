import { pluginIcon } from './components/plugin-icon';

import { Icon, Modal, Button } from '@wordpress/components';
import { select, useSelect } from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { __, _n, _x, sprintf } from '@wordpress/i18n';
import { registerPlugin } from '@wordpress/plugins';
import { useState } from '@wordpress/element';


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

	const [ isOpen, setOpen ] = useState( false );
    const openModal = () => setOpen( true );
    const closeModal = () => setOpen( false );

	if ( ! parseInt( dtGutenberg.unlinked ) ) {
		return (
			<div>
				<span id="distributed-from">
					{ sprintf(
						/* translators: 1: Syndication date and time. */
						__( 'Pulled & linked on %1$s from %2$s', 'distributor' ),
						dtGutenberg.syndicationTime,
						dtGutenberg.originalLocationName
					) }
				</span>
				<span id="distributed-data">
					{ sprintf(
							/* translators: %s is the URL to Original Content */
							__( 'Updating the ', 'distributor' ),
						)
					}
					<a href={dtGutenberg.postUrl}>
					{ sprintf(
							/* translators: %s is the URL to Original Content */
							__( 'Original Content', 'distributor' ),
						)
					},

					</a> 
					{ sprintf(
							/* translators: %s is the URL to Original Content */
							__( 'will update this post automatically.', 'distributor' ),
						)
					}
				</span>
				<span id="distributed-unlink"
				onClick={ openModal }
				>
					<span class="dashicons dashicons-editor-unlink"></span>
					{ sprintf(
						/* translators: %s is the URL to Original Content */
						__( 'Unlink from Original', 'distributor' ),
					) }
				</span>
				{ isOpen && (
	                <Modal title="Unlink from Original" icon={ pluginIcon } size={ 50 } onRequestClose={ closeModal }>
	                	<span id="distributed-data">
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'Unlinking from the ', 'distributor' ),
								)
							}
							<a href={dtGutenberg.postUrl}>
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'Original Content ', 'distributor' ),
								)
							}

							</a> 
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'will stop updating this post automatically.', 'distributor' ),
								)
							}
						</span>
						<br />
						<div class="actions">
		                    <a href={ dtGutenberg.unlinkNonceUrl }>
		                    	<span class="dashicons dashicons-editor-unlink"></span>
								{ sprintf(
										/* translators: %s is the URL to Original Content */
										__( 'Unlink', 'distributor' ),
									)
								}
							</a> 
							<span id="close" onClick={ closeModal }>
								{ sprintf(
										/* translators: %s is the URL to Original Content */
										__( 'Cancel', 'distributor' ),
									)
								}
							</span>
						</div>
	                </Modal>
	            ) }
			</div>
		);
	} else {
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
					{ sprintf(
							/* translators: %s is the URL to Original Content */
							__( 'This post has been unlinked from the ', 'distributor' ),
						)
					}
					<a href={dtGutenberg.postUrl}>
						{ sprintf(
								/* translators: %s is the URL to Original Content */
								__( 'Original Content', 'distributor' ),
							)
						}
					</a>
				</span>
				<span id="distributed-restorelink"
				onClick={ openModal }
				>
					<span class="dashicons dashicons-editor-unlink"></span>
					{ sprintf(
						/* translators: %s is the URL to Original Content */
						__( 'Restore link to Original', 'distributor' ),
					) }
				</span>
				{ isOpen && (
	                <Modal title="Restore link to Original" icon={ pluginIcon } size={ 50 } onRequestClose={ closeModal }>
	                	<span id="distributed-data">
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'Restoring the link to the ', 'distributor' ),
								)
							}
							<a href={dtGutenberg.postUrl}>
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'Original Content. ', 'distributor' ),
								)
							}

							</a> 
							{ sprintf(
									/* translators: %s is the URL to Original Content */
									__( 'will start updating this post automatically from the Original, overwriting current content.', 'distributor' ),
								)
							}
						</span>
						<div class="actions">
		                    <a href={ dtGutenberg.linkNonceUrl }>
		                    	<span class="dashicons dashicons-editor-unlink"></span>
								{ sprintf(
										/* translators: %s is the URL to Original Content */
										__( 'Restore link', 'distributor' ),
									)
								}
							</a> 
							<span id="close" onClick={ closeModal }>
								{ sprintf(
										/* translators: %s is the URL to Original Content */
										__( 'Cancel', 'distributor' ),
									)
								}
							</span>
						</div>
	                </Modal>
	            ) }
			</div>
		);
	}
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
const DistributorTitle = () => {
	if ( ! parseInt( dtGutenberg.unlinked ) ) {
		return __( 'Pulled Content', 'distributor' );
	} else {
		return __( 'Unlinked Content', 'distributor' );
	}

	return null;
};

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

	// If we are on a non-supported post status, change what we show
	if (
		dtGutenberg.supportedPostStati &&
		! dtGutenberg.supportedPostStati.includes( postStatus )
	) {
		return (
			<PluginDocumentSettingPanel
				title={ DistributorTitle }
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
			title={ DistributorTitle }
			icon={ DistributorIcon }
			className="distributor-panel"
		>
			<RenderShowAdminBar />
			<RenderDistributionInfo />
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'distributor-plugin', { render: DistributorPlugin } );
