const { randomName } = require( '../support/functions' );

describe( 'Internal Pull', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
	} );

	it( 'Post should show per status', () => {
		const postName = 'Pull test ' + randomName();
		cy.createPost( {
			title: postName,
		} ).then( () => {
			cy.visit( 'second/wp-admin/admin.php?page=pull' );
			cy.get( '.page-title' ).contains( postName );

			cy.visit( 'second/wp-admin/admin.php?page=pull&status=pulled' );
			cy.get( '.wp-list-table tbody tr.no-items' ).should( 'be.visible' );

			cy.visit( 'second/wp-admin/admin.php?page=pull&status=skipped' );
			cy.get( '.wp-list-table tbody tr.no-items' ).should( 'be.visible' );
		} );
	} );
} );
