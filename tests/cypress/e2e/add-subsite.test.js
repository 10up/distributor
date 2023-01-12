describe( 'Admin can add a new subsite', () => {
	before( () => {
		cy.login();
	} );

	it( 'Check if a subsite exists, if not, add it', () => {
		// Check if a second site is already added. If not, add it.
		cy.visit( '/wp-admin/network/sites.php' );
		cy.get( 'body' ).then( ( $body ) => {
			if (
				$body.find( 'a[href="http://localhost/second/"]' ).length === 0
			) {
				cy.visit( '/wp-admin/network/site-new.php' )
					.get( '#site-address' )
					.type( 'second' );
				cy.get( '#site-title' ).type( 'Second Site' );
				cy.get( '#admin-email' ).type( 'admin@gmail.com' );

				cy.get( '#add-site' ).click();
			}

			// Visit the list and check if a site is added.
			cy.visit( '/wp-admin/network/sites.php' );
			cy.get( 'a' ).should( 'contain.text', 'localhost/second' );
		} );
	} );
} );
