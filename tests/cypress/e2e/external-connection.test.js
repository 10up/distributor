const { randomName } = require( '../support/functions' );

describe( 'Admin can add a new external connection', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );
	} );

	it( 'Should create external connection', () => {
		const connectionName = 'Connection ' + randomName();
		// The command includes the workaround to sucessfully create new connection.
		cy.createExternalConnection(
			connectionName,
			'http://localhost/second/wp-json'
		);
	} );

	it( 'Should suggest wp-json URL', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		cy.get( '.manual-setup-button' ).click();
		cy.get( '#dt_external_connection_url' ).type(
			'http://localhost/second'
		);
		cy.get( '.endpoint-result' ).should( 'contain.text', 'Did you mean' );
		cy.get( '.suggest.button-link' ).should(
			'contain.text',
			'http://localhost/second/wp-json'
		);
	} );

	it( 'Should display endpoint error', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		cy.get( '.manual-setup-button' ).click();
		cy.get( '#dt_external_connection_url' ).type(
			'http://' + randomName()
		);
		cy.get( '.endpoint-result' ).should(
			'contain.text',
			'No connection found'
		);
	} );

	it( 'Should auto-populate connection name from URL', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		const name = randomName();
		cy.get( '.manual-setup-button' ).click();
		cy.get( '#dt_external_connection_url' ).type( 'http://' + name );
		cy.get( '#create-connection' ).click();

		cy.get( '#title' ).should( 'have.value', name );
	} );

	it( 'Should display warning status', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		const name = randomName();
		cy.get( '#title' ).click().type( name );

		cy.get( '.manual-setup-button' ).click();
		cy.get( '#dt_external_connection_url' ).type(
			'http://' + randomName()
		);
		cy.get( '#create-connection' ).click();

		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.row-title' )
			.contains( name )
			.closest( '.hentry' )
			.find( '.connection-status' )
			.should( 'have.class', 'warning' );
	} );

	it( 'Should display error status', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		const name = randomName();
		cy.get( '#title' ).click().type( name );

		cy.get( '.manual-setup-button' ).click();
		cy.get( '#create-connection' ).click();

		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.row-title' )
			.contains( name )
			.closest( '.hentry' )
			.find( '.connection-status' )
			.should( 'have.class', 'error' );
	} );

	it( 'Should display limited connection warning', () => {
		cy.visit( '/wp-admin/admin.php?page=distributor' );
		cy.get( '.page-title-action' ).contains( 'Add New' ).click();

		const name = randomName();
		cy.get( '#title' ).click().type( name );

		cy.get( '.manual-setup-button' ).click();
		cy.get( '#dt_username' ).type( 'invalid_username' );
		cy.get( '#dt_password' ).type( 'invalid_password' );
		cy.get( '#dt_external_connection_url' ).type(
			'http://localhost/second/wp-json'
		);
		cy.get( '.description.endpoint-result' ).should(
			'contain.text',
			'Limited connection established.'
		);
	} );
} );
