const { randomName } = require( '../support/functions' );

describe( 'Push menu test', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
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
			).then( ( distributedPost ) => {
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
} );
