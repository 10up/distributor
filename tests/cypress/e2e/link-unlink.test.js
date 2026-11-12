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
				cy.get(
					'.components-notice .components-notice__actions .components-notice__action:eq(0)'
				)
					.should( 'contain.text', 'Unlink from the origin post' )
					.should( 'have.attr', 'href' )
					.and( 'match', /&action=unlink&/ );
				// Ensure the post is not editable
				cy.get( '.edit-post-visual-editor' ).should(
					'have.css',
					'pointer-events',
					'none'
				);
			} );
		} );
	} );

	it( 'Should show the linking option for unlinked posts', () => {
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

				// Unlink the post.
				cy.get(
					'.components-notice .components-notice__actions .components-notice__action:eq(0)'
				)
					.should( 'contain.text', 'Unlink from the origin post' )
					.click();

				// Ensure the post shows link to original post.
				cy.get( '.components-notice__content' )
					.should(
						'contain.text',
						'This post has been unlinked from the origin post'
					)
					.should(
						'contain.text',
						'Edits to the origin post will not update this remote version'
					);

				// Ensure the post is editable
				cy.get( '.edit-post-visual-editor' ).should(
					'not.have.css',
					'pointer-events',
					'none'
				);
			} );
		} );
	} );
} );

describe( 'Relinking external posts', () => {
	const connectionName = 'Relink connection';
	const secondSiteUrl = 'http://localhost/second';

	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );
		cy.networkActivatePlugin( 'distributor-e2e-test-plugin' );
		cy.createExternalConnection(
			connectionName,
			secondSiteUrl + '/wp-json'
		);
	} );

	/**
	 * Wait until the second site stores the origin's subscription update.
	 *
	 * Subscription updates are sent without waiting for a response, so the
	 * stored update can arrive after the origin save finishes.
	 *
	 * @param {number} postId   Post ID on the second site.
	 * @param {string} title    Title the stored update should contain.
	 * @param {number} attempts Attempts left.
	 */
	const waitForStoredUpdate = ( postId, title, attempts = 20 ) => {
		cy.wpCli(
			`wp post meta get ${ postId } dt_subscription_update --format=json --url=${ secondSiteUrl }`,
			true
		).then( ( response ) => {
			if ( response.stdout.includes( title ) ) {
				return;
			}

			if ( attempts <= 1 ) {
				throw new Error( 'The subscription update was not stored.' );
			}

			// eslint-disable-next-line cypress/no-unnecessary-waiting -- poll between WP-CLI checks for the stored update.
			cy.wait( 1000 );
			waitForStoredUpdate( postId, title, attempts - 1 );
		} );
	};

	it( 'Should apply the stored update when relinking an external post', () => {
		const postTitle = 'Relink' + randomName();
		const updatedTitle = postTitle + 'Updated';

		cy.createPost( { title: postTitle } ).then( ( sourcePost ) => {
			cy.distributorPushPost(
				sourcePost.id,
				connectionName,
				'',
				'publish',
				true
			);

			cy.wpCli(
				`wp post list --post_type=post --title=${ postTitle } --field=ID --url=${ secondSiteUrl }`
			).then( ( response ) => {
				const remotePostId = response.stdout.trim();
				const remoteEditUrl = `second/wp-admin/post.php?post=${ remotePostId }&action=edit`;

				// Unlink the post on the second site.
				cy.visit( remoteEditUrl );
				cy.closeWelcomeGuide();
				cy.get(
					'.components-notice .components-notice__actions .components-notice__action:eq(0)'
				)
					.should( 'contain.text', 'Unlink from the origin post' )
					.click();
				cy.get( '.components-notice__content' ).should(
					'contain.text',
					'This post has been unlinked from the origin post'
				);

				// Update the origin post. The second site stores the update but does not apply it.
				cy.visit(
					`/wp-admin/post.php?post=${ sourcePost.id }&action=edit`
				);
				cy.closeWelcomeGuide();
				cy.window().then( ( win ) => {
					win.wp.data
						.dispatch( 'core/editor' )
						.editPost( { title: updatedTitle } );
					return win.wp.data.dispatch( 'core/editor' ).savePost();
				} );

				waitForStoredUpdate( remotePostId, updatedTitle );

				cy.wpCli(
					`wp post get ${ remotePostId } --field=post_title --url=${ secondSiteUrl }`
				)
					.its( 'stdout' )
					.should( 'eq', postTitle );

				// Relink the post on the second site.
				cy.visit( remoteEditUrl );
				cy.closeWelcomeGuide();
				cy.get(
					'.components-notice .components-notice__actions .components-notice__action:eq(0)'
				)
					.should( 'contain.text', 'Relink to the origin post' )
					.click();
				cy.get( '.components-notice__content' ).should(
					'contain.text',
					'This post is linked to the origin post.'
				);

				// The stored update is applied and the receive hooks ran for the relink.
				cy.wpCli(
					`wp post get ${ remotePostId } --field=post_title --url=${ secondSiteUrl }`
				)
					.its( 'stdout' )
					.should( 'eq', updatedTitle );
				cy.wpCli(
					`wp post meta get ${ remotePostId } dt_e2e_receive_source --url=${ secondSiteUrl }`
				)
					.its( 'stdout' )
					.should( 'eq', 'relink' );
			} );
		} );
	} );
} );
