const { randomName } = require( '../support/functions' );

describe( 'Linking and unlinking tests', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
	} );

	it( 'Should show the unlinking option for linked posts', () => {
		const postTitle = 'Post to distribute ' + randomName();

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			// Push the post to the second network site.
			cy.distributorPushPost(
				sourcePost.id,
				'second',
				'',
				'publish'
			).then( ( distributedPost ) => {
				// Open the distributed post in the second network site.
				cy.visit( distributedPost.distributedEditUrl );
				cy.closeWelcomeGuide();

				// Ensure the post shows link to original post.
				cy.get( '.components-notice__content' )
					.should( 'contain.text', 'Distributed from' )
					.should(
						'contain.text',
						'This post is linked to the origin post.'
					);
				cy.get( '.components-notice__actions a:nth-child(1)' )
					.should( 'contain.text', 'Unlink from the origin post' )
					.should( 'have.attr', 'href' )
					.and( 'match', /&action=unlink&/ );
				cy.get( '.components-notice__actions a:nth-child(2)' )
					.should( 'contain.text', 'View the origin post' )
					.should( 'have.attr', 'href', sourcePost.link );

				// Ensure the post is not editable
				cy.get( '.edit-post-visual-editor' ).should(
					'have.css',
					'pointer-events',
					'none'
				);
			} );
		} );
	} );
} );
