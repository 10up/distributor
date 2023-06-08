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
} );
