// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })
const { randomName } = require( '../support/functions' );

Cypress.Commands.add( 'networkActivatePlugin', ( slug ) => {
	cy.visit( '/wp-admin/network/plugins.php' );
	cy.get( `#the-list tr[data-slug="${ slug }"]` ).then( ( $pluginRow ) => {
		if ( $pluginRow.find( '.activate > a' ).length > 0 ) {
			cy.get( `#the-list tr[data-slug="${ slug }"] .activate > a` )
				.should( 'have.text', 'Network Activate' )
				.click();
		}
	} );
} );

Cypress.Commands.add( 'networkDeactivatePlugin', ( slug ) => {
	cy.visit( '/wp-admin/network/plugins.php' );
	cy.get( `#the-list tr[data-slug="${ slug }"]` ).then( ( $pluginRow ) => {
		if ( $pluginRow.find( '.deactivate > a' ).length > 0 ) {
			cy.get( `#the-list tr[data-slug="${ slug }"] .deactivate > a` )
				.should( 'have.text', 'Network Deactivate' )
				.click();
		}
	} );
} );

Cypress.Commands.add( 'networkEnableTheme', ( slug ) => {
	cy.visit( '/wp-admin/network/themes.php' );
	cy.get( `#the-list tr[data-slug="${ slug }"]` ).then( ( $themeRow ) => {
		if ( $themeRow.find( '.enable > a' ).length > 0 ) {
			cy.get( `#the-list tr[data-slug="${ slug }"] .enable > a` )
				.should( 'have.text', 'Network Enable' )
				.click();
		}
	} );
} );

Cypress.Commands.add( 'disableFullscreenEditor', () => {
	cy.window().then( ( win ) => {
		if (
			!! win.wp.data &&
			win.wp.data
				.select( 'core/edit-post' )
				.isFeatureActive( 'fullscreenMode' )
		) {
			win.wp.data
				.dispatch( 'core/edit-post' )
				.toggleFeature( 'fullscreenMode' );
		}
	} );
} );

Cypress.Commands.add( 'dismissNUXTip', () => {
	cy.get( 'body' ).then( ( $body ) => {
		if ( $body.find( '.nux-dot-tip__disable' ).length ) {
			cy.get( '.nux-dot-tip__disable' ).click();
		}
	} );
} );

Cypress.Commands.add(
	'createExternalConnection',
	(
		name = 'Test Connection',
		url = 'http://localhost/wp-json',
		user = 'admin',
		password = 'password',
		blog = ''
	) => {
		let adminUrl = '/wp-admin';
		if ( blog ) {
			adminUrl = '/' + blog + adminUrl;
		}

		cy.visit( adminUrl + '/admin.php?page=distributor' );

		cy.get( '.row-title, .no-items' ).then( ( elements ) => {
			const noItems = elements.hasClass( 'no-items' );
			const found = elements.toArray().reduce( ( prev, el ) => {
				if ( el.textContent === name ) {
					prev = true;
				}
				return prev;
			}, false );
			if ( noItems || ! found ) {
				cy.visit(
					adminUrl + '/post-new.php?post_type=dt_ext_connection'
				);

				cy.get( '.manual-setup-button' ).click();

				cy.get( '#title' ).type( name );

				cy.get( '#dt_username' ).type( user );

				cy.get( '#dt_password' ).type( password );

				cy.get( '#dt_external_connection_url' ).type( url );

				cy.get( '#create-connection' ).click();
			}

			// Visit the list and check the validation.
			cy.visit( adminUrl + '/admin.php?page=distributor' );
			cy.get( '.row-title' )
				.contains( name )
				.closest( 'tr' )
				.find( '.connection-status' )
				.should( 'have.class', 'valid' );
		} );
	}
);

Cypress.Commands.add(
	'distributorPushPost',
	(
		postId,
		toConnectionName,
		fromBlogSlug = '',
		postStatus = 'publish',
		external = false,
		classicEditor = false
	) => {
		const info = {
			originalEditUrl:
				fromBlogSlug +
				'/wp-admin/post.php?post=' +
				postId +
				'&action=edit',
		};

		cy.visit( info.originalEditUrl );

		cy.get( 'body' ).then( ( $body ) => {
			let originalFrontUrl;
			if ( $body.find( '#wp-admin-bar-view a' ).length ) {
				originalFrontUrl = $body
					.find( '#wp-admin-bar-view a' )
					.first()
					.prop( 'href' );
			} else {
				originalFrontUrl = $body
					.find( '#wp-admin-bar-preview a' )
					.first()
					.prop( 'href' );
			}
			info.originalFrontUrl = originalFrontUrl;
		} );

		if ( ! classicEditor ) {
			cy.disableFullscreenEditor();
			cy.dismissNUXTip();
			cy.closeWelcomeGuide();
		}

		cy.get( '#wp-admin-bar-distributor' )
			.contains( 'Distributor' )
			.should( 'be.visible' )
			.click();

		cy.get( '#distributor-push-wrapper .new-connections-list' ).should(
			'be.visible'
		);

		// Distribute post
		cy.get(
			'#distributor-push-wrapper .new-connections-list .add-connection'
		)
			.contains( toConnectionName )
			.click();

		if ( 'publish' === postStatus ) {
			// Uncheck for publish, draft is checked by default.
			cy.get( '#dt-as-draft' ).click();
		}

		cy.get( '#distributor-push-wrapper .syndicate-button' ).click();

		cy.get( '#distributor-push-wrapper .dt-success' ).should(
			'be.visible'
		);

		// Now let's navigate to the new post - only works for network connections.
		if ( ! external ) {
			cy.get(
				'#distributor-push-wrapper .new-connections-list .add-connection'
			)
				.contains( toConnectionName )
				.closest( '.add-connection' )
				.find( 'a' )
				.contains( 'View' )
				.click();

			cy.get( '#wp-admin-bar-edit a' )
				.invoke( 'attr', 'href' )
				.then( ( href ) => {
					info.distributedEditUrl = href;
					const matches = href.match( /post=(\d+)/ );
					if ( matches ) {
						info.distributedPostId = matches[ 1 ];
					}
				} );

			cy.url().then( ( url ) => {
				info.distributedFrontUrl = url;
			} );
		}

		cy.wrap( info );
	}
);

Cypress.Commands.add(
	'distributorPullPost',
	(
		originalPostId,
		toBlogSlug,
		fromBlogSlug = '',
		useConnection = false
	) => {
		toBlogSlug = toBlogSlug.replace( /\/?$/, '/' );
		fromBlogSlug = fromBlogSlug.replace( /\/?$/, '/' );

		const info = {
			originalEditUrl:
				fromBlogSlug +
				'/wp-admin/post.php?post=' +
				originalPostId +
				'&action=edit',
		};

		cy.visit( toBlogSlug + 'wp-admin/admin.php?page=pull' );

		if ( useConnection ) {
			cy.get( '#pull_connections' ).select( useConnection );
			cy.get( '.wp-list-table #cb-select-' + originalPostId ).should(
				'be.visible'
			);
		}

		cy.get( '.wp-list-table #cb-select-' + originalPostId ).check();
		cy.get( '#bulk-action-selector-top' ).select( 'bulk-syndicate' );
		cy.get( '#doaction' ).click();

		cy.get( '.pulled > a' ).click();
		cy.get(
			'.wp-list-table tbody tr:nth-child(1) .page-title .view a'
		).click( { force: true } ); // Using force true to click "View" link

		cy.url().then( ( url ) => {
			info.distributedViewUrl = url;
		} );

		cy.get( '#wp-admin-bar-edit a' ).click();

		cy.url().then( ( url ) => {
			info.distributedEditUrl = url;
		} );

		cy.wrap( info );
	}
);

Cypress.Commands.add( 'createTweetOEmbedPost', ( tweetUrl ) => {
	const postTitle = 'oEmbed ' + randomName();
	cy.createPost( {
		title: postTitle,
		beforeSave: () => {
			cy.insertBlock( 'core/embed/twitter', 'Twitter' ).then( ( id ) => {
				cy.getBlockEditor()
					.find( `#${ id } input[type="url"]` )
					.click()
					.type( tweetUrl );
				cy.getBlockEditor()
					.find( `#${ id } button[type="submit"]` )
					.click();
			} );
		},
	} ).then( ( post ) => {
		cy.wrap( post );
	} );
} );

Cypress.Commands.add( 'postContains', ( postId, content, siteUrl ) => {
	let cliCommand = `wp post get ${ postId } --field=content`;
	if ( siteUrl ) {
		cliCommand += ` --url=${ siteUrl }`;
	}
	cy.wpCli( cliCommand ).its( 'stdout' ).should( 'contain', content );
} );

Cypress.Commands.add( 'uploadImage', ( imagePath ) => {
	cy.visit( '/wp-admin/media-new.php' );
	cy.get( '#plupload-upload-ui' ).should( 'exist' );
	cy.get( '#plupload-upload-ui input[type=file]' ).selectFile( imagePath, {
		force: true,
	} );

	cy.get( '#media-items .media-item a.edit-attachment', {
		timeout: 20000,
	} ).should( 'exist' );
	cy.get( '#media-items .media-item a.edit-attachment' )
		.invoke( 'attr', 'href' )
		.then( ( editLink = '' ) => {
			const mediaId = editLink?.split( 'post=' )[ 1 ]?.split( '&' )[ 0 ];
			cy.wrap( mediaId );
		} );
} );

Cypress.Commands.add(
	'verifyRelatedPostMeta',
	( postId, relatedPostTitle, siteUrl ) => {
		const cliCommand = `wp post meta get ${ postId } related_post_id --url=${ siteUrl }`;
		cy.wpCli( cliCommand ).then( ( response ) => {
			cy.wpCli(
				`wp post get ${ response.stdout } --field=post_title --url=${ siteUrl }`
			)
				.its( 'stdout' )
				.should( 'eq', relatedPostTitle );
		} );
	}
);

Cypress.Commands.add(
	'verifyShortCodeTermId',
	( postId, shortcodeTermName, siteUrl ) => {
		const slug = shortcodeTermName.split( ' ' ).join( '-' ).toLowerCase();
		const cliCommand = `wp term get category ${ slug } --by=slug --field=term_id --url=${ siteUrl }`;
		cy.wpCli( cliCommand ).then( ( response ) => {
			cy.wpCli(
				`wp post get ${ postId } --field=content --url=${ siteUrl }`
			)
				.its( 'stdout' )
				.should(
					'contain',
					`[dt_term_shortcode id="${ response.stdout }"]`
				);
		} );
	}
);

Cypress.Commands.add( 'insertBlockLocal', ( type, name ) => {
	const [ namespace = '', ...blockNameRest ] = type.split( '/' );
	let blockNames = [
		blockNameRest.join( '/' ).replace( /\//g, '-' ),
		blockNameRest.join( '/' ).replace( /\//g, String.raw`\/` ),
	];
	blockNames = blockNames.filter( ( x, i, a ) => a.indexOf( x ) == i );
	// let blockName = blockNameRest.join('/').replace( '/', '\\/' );
	let inserterBtn;
	let search = '';
	if ( typeof name === 'string' && name.length ) {
		search = name;
	} else {
		search = type;
	}
	// Start of block inserter toggle button click logic.
	cy.get( 'body' ).then( ( $body ) => {
		const selectors = [
			'button[aria-label="Add block"]',
			'button[aria-label="Toggle block inserter"]',
			'button[aria-label="Block Inserter"]', // 6.8
		];
		selectors.forEach( ( selector ) => {
			if ( $body.find( selector ).length ) {
				cy.get( selector ).then( ( $button ) => {
					if ( $button.length ) {
						inserterBtn = cy.wrap( $button );
						inserterBtn.first().click();
					}
				} );
			}
		} );
	} );
	// End of block inserter toggle button click logic.
	// Start of Block tab click logic.
	cy.get( 'button[role="tab"]' )
		.contains( 'Blocks' )
		.then( ( $tab ) => {
			if ( $tab.length ) {
				cy.wrap( $tab ).click();
			}
		} );
	// End of Block tab click logic.
	// Start of Block search logic.
	cy.get( 'input[placeholder="Search"]' ).then( ( $input ) => {
		if ( $input.length ) {
			cy.wrap( $input ).type( search );
		}
	} );
	// End of Block search logic.
	blockNames.forEach( ( blockName ) => {
		const blockSelector = `.editor-block-list-item-${
			'core' === namespace ? '' : namespace + '-'
		}${ blockName }`;
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( blockSelector ).length ) {
				// Start of Block insertion by click logic.
				cy.get( blockSelector ).then( ( $block ) => {
					if ( $block.length ) {
						cy.wait( 1000 );
						cy.wrap( $block ).click();
						cy.wait( 1000 );
						inserterBtn.click();
						cy.wait( 1000 );
						const [ ns, rest ] = type.split( '/' ); // namespace = ns, second namespace or block name = rest
						cy.get( 'body' ).then( ( $body ) => {
							if (
								$body.find( 'iframe[name="editor-canvas"]' )
									.length
							) {
								// Works with WP 6.4
								( 0, get_iframe_1.getIframe )(
									'iframe[name="editor-canvas"]'
								).then( ( $iframe ) => {
									const blockInIframe = $iframe.find(
										`.wp-block[data-type="${ ns }/${ rest }"]`
									);
									if ( blockInIframe.length > 0 ) {
										cy.wrap(
											blockInIframe.last().prop( 'id' )
										);
									}
								} );
							} else if (
								$body.find(
									`.wp-block[data-type="${ ns }/${ rest }"]`
								).length
							) {
								// Works with WP 5.7
								cy.get(
									`.wp-block[data-type="${ ns }/${ rest }"]`
								).then( ( $blockInEditor ) => {
									expect( $blockInEditor.length ).to.equal(
										1
									);
									cy.wrap( $blockInEditor.prop( 'id' ) );
								} );
							} else {
								throw new Error(
									`${ ns }/${ rest } not found.`
								);
							}
						} );
					}
				} );
				// End of Block insertion by click logic.
			}
		} );
	} );
} );
