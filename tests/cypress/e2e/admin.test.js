describe( 'Admin can login and make sure plugin is activated', () => {
	before( () => {
		cy.login();
	} );

	it( 'Can deactivate and activate plugin?', () => {
		cy.deactivatePlugin( 'distributor' );
		cy.activatePlugin( 'distributor' );
	} );
} );
