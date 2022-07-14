describe( 'Admin can login and make sure plugin is activated', () => {
	before( () => {
		cy.login();
		cy.visit( '/wp-admin/network/plugins.php' );
	} );

	it( 'Can deactivate and activate plugin ', () => {
		cy.deactivatePlugin( 'distributor' );
		cy.activatePlugin( 'distributor' );
	} );
} );
