const { randomName } = require( '../support/functions' );

describe( 'Distributed Post Tests', () => {
	let externalConnectionName = '';

	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );

		externalConnectionName = 'Connection ' + randomName();
		cy.createExternalConnection(
			externalConnectionName,
			'http://localhost/second/wp-json'
		);
	} );

	it( 'Counter should increase after distributing a post', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.distributorPushPost( post.id, 'second', '', 'publish' );
			// Return the the post edit screen.
			cy.visit( '/wp-admin/post.php?post=' + post.id + '&action=edit' );
			// Ensure the settings panel is open.
			cy.get( 'button[aria-label="Settings"]' ).then( () => {
				cy.openDocumentSettingsSidebar( 'Post' );
				cy.openDocumentSettingsPanel( 'Pulled Content' );
				cy.get( '#distributed-to' ).should(
					'contain.text',
					'Distributed to 1 connection'
				);
			} );

			cy.distributorPushPost(
				post.id,
				externalConnectionName,
				'',
				'publish'
			);
			// Return the the post edit screen.
			cy.visit( '/wp-admin/post.php?post=' + post.id + '&action=edit' );
			// Ensure the settings panel is open.
			cy.get( 'button[aria-label="Settings"]' ).then( ( $settings ) => {
				if ( $settings.attr( 'aria-expanded' ) === 'false' ) {
					$settings.trigger( 'click' );
				}
				cy.openDocumentSettingsSidebar( 'Post' );
				cy.openDocumentSettingsPanel( 'Pulled Content' );
				cy.get( '#distributed-to' ).should(
					'contain.text',
					'Distributed to 2 connections'
				);
			} );
		} );
	} );

	it( 'Should display source information in distributed copies of content', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				'second',
				'',
				'publish'
			).then( ( distributedPost ) => {
				cy.visit( distributedPost.distributedFrontUrl );
				cy.get( '#wp-admin-bar-distributor .syndicated-notice' )
					.should( 'contain.text', 'This post was distributed from' )
					.should( 'contain.text', 'View the origin post.' );
				cy.get( 'link[rel=canonical]' ).should(
					'have.attr',
					'href',
					sourcePost.link
				);

				cy.visit( distributedPost.distributedEditUrl );
				cy.closeWelcomeGuide();
				cy.get( '.components-notice__content' )
					.should( 'contain.text', 'Distributed from' )
					.should(
						'contain.text',
						'This post is linked to the origin post. Edits to the origin post will update this remote version.'
					);

				// Ensure the settings panel is open.
				cy.get( 'button[aria-label="Settings"]' ).then(
					( $settings ) => {
						if ( $settings.attr( 'aria-expanded' ) === 'false' ) {
							$settings.trigger( 'click' );
						}
						cy.openDocumentSettingsSidebar( 'Post' );
						cy.openDocumentSettingsPanel( 'Pulled Content' );
						cy.get( '#distributed-from' ).should(
							'contain.text',
							'Pulled & linked on'
						);
					}
				);
			} );
		} );
	} );
} );
