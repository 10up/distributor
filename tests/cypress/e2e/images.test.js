const { randomName } = require( '../support/functions' );

describe( '[Block Editor] Image distribution tests', () => {
	// prevent uncaught exceptions from failing the test on WP trunk.
	let externalConnectionOneToTwo, externalConnectionTwoToOne;
	const attachImages = () => {
		cy.openDocumentSettingsSidebar( 'Post' );
		cy.get( 'body' ).then( ( $body ) => {
			if (
				$body.find(
					'.editor-post-featured-image .editor-post-featured-image__toggle'
				).length
			) {
				cy.get( '.editor-post-featured-image__toggle' ).click();
				cy.get( '.media-menu-item' )
					.contains( 'Media Library' )
					.click();
				cy.get( '.attachments-browser .attachment' ).first().click();
				cy.get( '.media-button-select' ).click();
			} else {
				cy.openDocumentSettingsPanel( 'Featured Image' );
				cy.get( '.editor-post-featured-image__toggle' ).click();
				cy.get( '.media-menu-item' )
					.contains( 'Media Library' )
					.click();
				cy.get( '.attachments-browser .attachment' ).first().click();
				cy.get( '.media-button-select' ).click();
			}
		} );

		cy.insertBlock( 'core/image', 'Image' ).then( ( id ) => {
			cy.getBlockEditor()
				.find( `#${ id } button.components-button` )
				.contains( 'Media Library' )
				.click();
			cy.get( '.attachments-browser .attachment' ).eq( 1 ).click();
			cy.get( '.media-button-select' ).click();
			cy.getBlockEditor().find( `#${ id } img` ).should( 'be.visible' );
		} );
	};

	before( () => {
		// Prevent uncaught exceptions from failing the test on WP trunk.
		Cypress.on( 'uncaught:exception', () => {
			return false;
		} );
		cy.login();
		cy.networkDeactivatePlugin( 'classic-editor' );
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );

		externalConnectionOneToTwo = 'Site Two ' + randomName();
		cy.createExternalConnection(
			externalConnectionOneToTwo,
			'http://localhost/second/wp-json'
		);

		externalConnectionTwoToOne = 'Site One ' + randomName();
		cy.createExternalConnection(
			externalConnectionTwoToOne,
			'http://localhost/wp-json',
			'admin',
			'password',
			'second'
		);

		cy.visit( 'wp-admin/admin.php?page=distributor-settings' );
		cy.get( '.form-table input[type="checkbox"]' ).first().check();
		cy.get( 'input[type="radio"]' ).check( 'attached' );
		cy.get( '#submit' ).click();

		cy.visit( '/second/wp-admin/admin.php?page=distributor-settings' );
		cy.get( '.form-table input[type="checkbox"]' ).first().check();
		cy.get( 'input[type="radio"]' ).check( 'attached' );
		cy.get( '#submit' ).click();

		cy.visit( '/wp-admin/upload.php?mode=grid' );
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait( 2000 );
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( 'ul.attachments li' ).length === 0 ) {
				cy.uploadImage( './assets/img/banner-772x250.png' );
				cy.uploadImage( './assets/img/banner-1544x500.png' );
			}
		} );
	} );

	it( 'Should distribute images when pushing to network connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( {
			title: postTitle,
			beforeSave: attachImages,
		} ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				'second',
				'',
				'publish'
			).then( ( distributedPost ) => {
				// eslint-disable-next-line cypress/no-unnecessary-waiting
				cy.wait( 1000 );
				cy.postContains(
					distributedPost.distributedPostId,
					'<img src="http://localhost/wp-content/uploads/sites/2/', // For the push to network connection, image url will be generated from the source site, this is due to https://core.trac.wordpress.org/ticket/25650
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should distribute images when pulling from network connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.createPost( { title: postTitle, beforeSave: attachImages } ).then(
			( sourcePost ) => {
				cy.distributorPullPost(
					sourcePost.id,
					'second',
					'',
					'localhost'
				).then( ( distributedPost ) => {
					cy.closeWelcomeGuide();
					const matches =
						distributedPost.distributedEditUrl.match(
							/post=(\d+)/
						);
					let distributedPostId;
					if ( matches ) {
						distributedPostId = matches[ 1 ];
					}
					cy.postContains(
						distributedPostId,
						'<img src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
				} );
			}
		);
	} );

	it( 'Should distribute images when pushing to external connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle, beforeSave: attachImages } ).then(
			( sourcePost ) => {
				cy.distributorPushPost(
					sourcePost.id,
					externalConnectionOneToTwo,
					'',
					'publish'
				).then( ( distributedPost ) => {
					cy.postContains(
						distributedPost.distributedPostId,
						'<img src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
				} );
			}
		);
	} );

	it( 'Should distribute image when pulling from external connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.createPost( { title: postTitle, beforeSave: attachImages } ).then(
			( sourcePost ) => {
				cy.distributorPullPost(
					sourcePost.id,
					'/second/', // Pull to second site.
					'', // From primary site.
					externalConnectionTwoToOne
				).then( ( distributedPost ) => {
					cy.closeWelcomeGuide();
					const matches =
						distributedPost.distributedEditUrl.match(
							/post=(\d+)/
						);
					let distributedPostId;
					if ( matches ) {
						distributedPostId = matches[ 1 ];
					}
					cy.postContains(
						distributedPostId,
						'<img src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
				} );
			}
		);
	} );
} );
