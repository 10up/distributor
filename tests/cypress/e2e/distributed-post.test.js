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
			cy.get( 'button[aria-label="Settings"]' ).then( ( $settings ) => {
				if ( $settings.attr( 'aria-expanded' ) === 'false' ) {
					$settings.trigger( 'click' );
				}
				cy.openDocumentSettingsSidebar( 'Post' );
				cy.openDocumentSettingsPanel( 'Distributor' );
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
				cy.openDocumentSettingsPanel( 'Distributor' );
				cy.get( '#distributed-to' ).should(
					'contain.text',
					'Distributed to 2 connections'
				);
			} );
		} );
	} );
} );
