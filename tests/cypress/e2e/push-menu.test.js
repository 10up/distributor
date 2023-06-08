const { randomName } = require( '../support/functions' );

describe( 'Push menu test', () => {
	let externalConnectionName;

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

	it( 'Push menu should not be visible on new post', () => {
		cy.visit( '/wp-admin/post-new.php' );
		cy.disableFullscreenEditor();
		cy.get( '#wp-admin-bar-distributor' ).should( 'not.be.visible' );
	} );

	it( 'Push menu should be visible', () => {
		const postTitle = 'Push menu test ' + randomName();
		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.visit( '/wp-admin/post.php?post=' + post.id + '&action=edit' );
			cy.disableFullscreenEditor();
			cy.get( '#wp-admin-bar-distributor' ).should( 'be.visible' );
			cy.get( '#wp-admin-bar-distributor > a' ).click();
			cy.get( '#distributor-push-wrapper .new-connections-list' ).should(
				'be.visible'
			);
		} );
	} );

	it( 'Push menu should prevent pushing to the same site', () => {
		const postTitle = 'Push menu test ' + randomName();
		const toConnectionName = 'localhost/second';
		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				toConnectionName,
				'',
				'publish'
			).then( () => {
				// Visit the source post in the dashboard.
				cy.visit(
					'/wp-admin/post.php?post=' + sourcePost.id + '&action=edit'
				);
				cy.disableFullscreenEditor();
				cy.get( '#wp-admin-bar-distributor > a' ).click();

				// Get menu item for site already distributed to.
				cy.get(
					'#distributor-push-wrapper .new-connections-list .add-connection'
				)
					.contains( toConnectionName )
					.parent()
					.should( 'be.disabled' );
			} );
		} );
	} );

	it( 'Should allow selecting and deselecting connections', () => {
		const postTitle = 'Push menu test ' + randomName();
		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.visit( '/wp-admin/post.php?post=' + post.id + '&action=edit' );
			cy.disableFullscreenEditor();
			cy.get( '#wp-admin-bar-distributor' ).should( 'be.visible' );
			cy.get( '#wp-admin-bar-distributor > a' ).click();

			// Select the network connection `localhost/second`.
			cy.get(
				'#distributor-push-wrapper .new-connections-list .add-connection'
			)
				.contains( 'localhost/second' )
				.click();

			// Check that the connection is selected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( 'localhost/second' )
				.should( 'be.visible' );

			// Select the external connection.
			cy.get(
				'#distributor-push-wrapper .new-connections-list .add-connection'
			)
				.contains( externalConnectionName )
				.click();

			// Check that the connection is selected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( externalConnectionName )
				.should( 'be.visible' );

			// Deselect the network connection.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( 'localhost/second' )
				.find( '.remove-connection' )
				.click();

			// Check that the connection is deselected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( 'localhost/second' )
				.should( 'not.exist' );

			// Check that the external connection is still selected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( externalConnectionName )
				.should( 'be.visible' );

			// Deselct the external connection.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( externalConnectionName )
				.find( '.remove-connection' )
				.click();

			// Check that the connection is deselected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.contains( externalConnectionName )
				.should( 'not.exist' );

			// Check that no other connections are selected.
			cy.get( '#distributor-push-wrapper .selected-connections-list' )
				.children()
				.should( 'not.exist' );
		} );
	} );
} );
