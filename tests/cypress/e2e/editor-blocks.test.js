const { randomName } = require( '../support/functions' );

describe( 'Distributed content block tests', () => {
	let externalConnectionOneToTwo, externalConnectionTwoToOne;

	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );

		externalConnectionOneToTwo = 'Site Two ' + randomName();
		cy.createExternalConnection(
			externalConnectionOneToTwo,
			'http://localhost/second/wp-json'
		);

		externalConnectionTwoToOne = 'Site One ' + randomName();
		cy.createExternalConnection(
			externalConnectionTwoToOne,
			'http://localhost/wp-json',
			'admin',
			'password',
			'second'
		);
	} );

	it( 'Should distribute blocks when pushing to network connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				'second',
				'',
				'publish'
			).then( ( distributedPost ) => {
				cy.postContains(
					distributedPost.distributedPostId,
					'<!-- wp:paragraph -->',
					'http://localhost/second/'
				);
			} );
		} );
	} );
} );
