const { randomName } = require( '../support/functions' );

describe( 'Internal Pull', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
	} );

	it( 'Should show per status', () => {
		const postName = 'Pull test ' + randomName();
		cy.createPost( {
			title: postName,
		} ).then( () => {
			cy.visit( 'second/wp-admin/admin.php?page=pull' );
			cy.get( '.page-title' ).contains( postName ).should( 'exist' );

			[
				'second/wp-admin/admin.php?page=pull&status=pulled',
				'second/wp-admin/admin.php?page=pull&status=skipped',
			].forEach( ( link ) => {
				cy.visit( link );
				cy.get( 'body' ).then( ( $body ) => {
					if (
						$body.find( '.wp-list-table tbody tr.no-items' )
							.length > 0
					) {
						cy.get( '.wp-list-table tbody tr.no-items' ).should(
							'be.visible'
						);
					} else {
						cy.get( '.page-title' )
							.contains( postName )
							.should( 'not.exist' );
					}
				} );
			} );
		} );
	} );

	it( 'Should pull post', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.distributorPullPost( post.id, 'second', '' );

			// Pulled post should not exist on a New tab
			cy.visit( 'second/wp-admin/admin.php?page=pull' );
			cy.get( '.wp-list-table .page-title' )
				.contains( postTitle )
				.should( 'not.exist' );

			// Pulled post should exist on Pulled tab
			cy.visit( 'second/wp-admin/admin.php?page=pull&status=pulled' );
			cy.get( '.wp-list-table .page-title' )
				.contains( postTitle )
				.should( 'exist' );
		} );
	} );

	it( 'Should skip post', () => {
		const postTitle = 'Post to skip ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( post ) => {
			cy.visit( 'second/wp-admin/admin.php?page=pull' );
			cy.get( '#bulk-action-selector-top' ).select( 'bulk-skip' );
			cy.get( '#cb-select-' + post.id ).check();
			cy.get( '#doaction' ).click();

			cy.visit( 'second/wp-admin/admin.php?page=pull&status=skipped' );
			cy.get( '.wp-list-table .page-title' )
				.contains( postTitle )
				.should( 'exist' );
		} );
	} );
} );
