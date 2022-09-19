describe( 'Test Settings', () => {
	before( () => {
		cy.login();
	} );

	it( 'Should Save Settings', () => {
		cy.visit( 'wp-admin/admin.php?page=distributor-settings' );

		// Round one.

		cy.get( '.form-table input[type="checkbox"]' ).first().check();
		cy.get( 'input[type="radio"]' ).check( 'featured' );

		cy.get( '#submit' ).click();

		cy.get( '.form-table input[type="checkbox"]' )
			.first()
			.should( 'be.checked' );

		cy.get( 'input[type="radio"]:checked' )
			.should( 'be.checked' )
			.and( 'have.value', 'featured' );

		// Round two.
		cy.get( '.form-table input[type="checkbox"]' ).first().uncheck();
		cy.get( 'input[type="radio"]' ).check( 'attached' );
		cy.get( '#submit' ).click();

		cy.get( '.form-table input[type="checkbox"]' )
			.first()
			.should( 'not.be.checked' );

		cy.get( 'input[type="radio"]:checked' )
			.should( 'be.checked' )
			.and( 'have.value', 'attached' );
	} );
} );
