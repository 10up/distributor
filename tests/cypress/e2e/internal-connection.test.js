describe( 'Admin can add a new external connection', () => {
	before( () => {
		cy.login();
	} );

	it( 'Check if a an external connection exists, if not, add it', () => {
		// Check if an external connection is added, if not, add it.
		cy.visit( '/wp-admin/admin.php?page=distributor' );

		cy.get( 'body' ).then( $body => {
			if ( $body.find( '.no-items' ).length > 0 ) {
				cy.visit( '/wp-admin/post-new.php?post_type=dt_ext_connection' );

				cy.get( '.manual-setup-button' ).click();

				cy
					.get( '#title' )
					.type( 'Second Site' );

				cy
					.get( '#dt_username' )
					.type( 'admin' );

				cy
					.get( '#dt_password' )
					.type( 'password' );

				cy
					.get( '#dt_external_connection_url' )
					.type( 'http://localhost/second/wp-json' );

				cy.get( '#create-connection' ).click();

			}

			// Visit the list and check the validation.
			cy.visit( '/wp-admin/admin.php?page=distributor' );
			cy.get( '.connection-status' ).should( 'have.class', 'valid' );
		} );
	} );
} );
