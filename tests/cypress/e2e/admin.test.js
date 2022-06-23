describe( 'Admin can login and make sure plugin is activated', () => {
	it( 'Can activate plugin if it is deactivated', () => {
		cy.visitAdminPage( 'plugins.php' );
		cy.get( '#deactivate-distributor' ).click();
		cy.get( '#activate-distributor' ).click();
		cy.get( '#deactivate-distributor' ).should( 'be.visible' );
	} );
} );
