const { randomName } = require( '../support/functions' );

describe( 'Internal Push', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
	} );

	it( 'Should push post (As a Draft post)', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.distributorPushPost( post.id, 'second', '', 'draft' );

			cy.visit( 'second/wp-admin/edit.php' );
			cy.get(
				'.wp-list-table tbody tr:nth-child(1) .row-title'
			).contains( postTitle );
			cy.get(
				'.wp-list-table tbody tr:nth-child(1) .post-state'
			).contains( 'Draft' );
		} );
	} );

	it( 'Should push post (As a published post)', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.distributorPushPost( post.id, 'second', '', 'publish' );

			cy.visit( 'second/wp-admin/edit.php' );
			cy.get( '.wp-list-table tbody tr:nth-child(1) .row-title' )
				.should( 'exist' )
				.contains( postTitle );
			cy.get( '.wp-list-table tbody tr:nth-child(1) .post-state' ).should(
				'not.exist'
			);
		} );
	} );
} );
