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
} );
