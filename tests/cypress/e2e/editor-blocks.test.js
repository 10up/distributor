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

	it( 'Should distribute blocks when pulling from network connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPullPost(
				sourcePost.id,
				'second',
				'',
				'localhost'
			).then( ( distributedPost ) => {
				cy.closeWelcomeGuide();
				const matches =
					distributedPost.distributedEditUrl.match( /post=(\d+)/ );
				let distributedPostId;
				if ( matches ) {
					distributedPostId = matches[ 1 ];
				}
				cy.postContains(
					distributedPostId,
					'<!-- wp:paragraph -->',
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should distribute blocks when pushing to external connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				externalConnectionOneToTwo,
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

	it( 'Should distribute blocks when pulling from external connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPullPost(
				sourcePost.id,
				'/second/', // Pull to second site.
				'', // From primary site.
				externalConnectionTwoToOne
			).then( ( distributedPost ) => {
				cy.closeWelcomeGuide();
				const matches =
					distributedPost.distributedEditUrl.match( /post=(\d+)/ );
				let distributedPostId;
				if ( matches ) {
					distributedPostId = matches[ 1 ];
				}
				cy.postContains(
					distributedPostId,
					'<!-- wp:paragraph -->',
					'http://localhost/second/'
				);
			} );
		} );
	} );
} );
