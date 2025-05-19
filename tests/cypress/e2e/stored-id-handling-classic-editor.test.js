const { randomName } = require( '../support/functions' );

describe( '[Classic Editor] Stored ID handling tests', () => {
	// prevent uncaught exceptions from failing the test on WP trunk.
	let externalConnectionOneToTwo, externalConnectionTwoToOne, postId, termId;
	const relatedPostTitle = 'Sample Related Post' + randomName();
	const shortcodeTermName = 'Sample Term' + randomName();

	before( () => {
		// Prevent uncaught exceptions from failing the test on WP trunk.
		Cypress.on( 'uncaught:exception', () => {
			return false;
		} );
		cy.login();
		cy.networkActivatePlugin( 'classic-editor' );
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'json-basic-authentication' );
		cy.networkActivatePlugin( 'distributor-e2e-test-plugin' );

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

		cy.visit( '/wp-admin/upload.php?mode=grid' );
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait( 2000 );
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( 'ul.attachments li' ).length === 0 ) {
				cy.uploadImage( './assets/img/banner-772x250.png' );
			}
		} );

		// Create Post
		cy.wpCli(
			`wp post create --post_type=post --post_title='${ relatedPostTitle }' --post_content='TEST content' --post_status=publish --porcelain`
		).then( ( response ) => {
			cy.log( 'Post ID: ' + response.stdout );
			postId = response.stdout;
		} );

		// Create Category
		cy.wpCli(
			`wp term create category '${ shortcodeTermName }' --slug=${ shortcodeTermName
				.split( ' ' )
				.join( '-' )
				.toLowerCase() } --porcelain`
		).then( ( response ) => {
			cy.log( 'Term ID: ' + response.stdout );
			termId = response.stdout;
		} );
	} );

	after( () => {
		cy.wpCli( `wp term delete category ${ termId }` );
		cy.wpCli( `wp post delete ${ postId } --force` );
	} );

	it( 'Should handle stored IDs when pushing to network connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			content: `[dt_term_shortcode id="${ termId }"]`,
		} ).then( ( sourcePostID ) => {
			cy.wpCli(
				`wp post meta update ${ sourcePostID } related_post_id ${ postId }`
			);
			cy.distributorPushPost(
				sourcePostID,
				'second',
				'',
				'publish',
				false,
				true
			).then( ( distributedPost ) => {
				// With classic editor rendered content is transferred and not the shortcode.
				// cy.verifyShortCodeTermId(
				// 	distributedPost.distributedPostId,
				// 	shortcodeTermName,
				// 	'http://localhost/second/'
				// );
				cy.verifyRelatedPostMeta(
					distributedPost.distributedPostId,
					relatedPostTitle,
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should handle stored IDs when pulling from network connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			content: `[dt_term_shortcode id="${ termId }"]`,
		} ).then( ( sourcePostID ) => {
			cy.wpCli(
				`wp post meta update ${ sourcePostID } related_post_id ${ postId }`
			);
			cy.distributorPullPost(
				sourcePostID,
				'second',
				'',
				'localhost'
			).then( ( distributedPost ) => {
				const matches =
					distributedPost.distributedEditUrl.match( /post=(\d+)/ );
				let distributedPostId;
				if ( matches ) {
					distributedPostId = matches[ 1 ];
				}

				// // With classic editor rendered content is transferred and not the shortcode.
				// cy.verifyShortCodeTermId(
				// 	distributedPostId,
				// 	shortcodeTermName,
				// 	'http://localhost/second/'
				// );
				cy.verifyRelatedPostMeta(
					distributedPostId,
					relatedPostTitle,
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should handle stored IDs when pushing to external connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			content: `[dt_term_shortcode id="${ termId }"]`,
		} ).then( ( sourcePostID ) => {
			cy.wpCli(
				`wp post meta update ${ sourcePostID } related_post_id ${ postId }`
			);
			cy.distributorPushPost(
				sourcePostID,
				externalConnectionOneToTwo,
				'',
				'publish',
				false,
				true
			).then( ( distributedPost ) => {
				// // With classic editor rendered content is transferred and not the shortcode.
				// cy.verifyShortCodeTermId(
				// 	distributedPost.distributedPostId,
				// 	shortcodeTermName,
				// 	'http://localhost/second/'
				// );
				cy.verifyRelatedPostMeta(
					distributedPost.distributedPostId,
					relatedPostTitle,
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should handle stored IDs when pulling from external connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			content: `[dt_term_shortcode id="${ termId }"]`,
		} ).then( ( sourcePostID ) => {
			cy.wpCli(
				`wp post meta update ${ sourcePostID } related_post_id ${ postId }`
			);
			cy.distributorPullPost(
				sourcePostID,
				'/second/', // Pull to second site.
				'', // From primary site.
				externalConnectionTwoToOne
			).then( ( distributedPost ) => {
				const matches =
					distributedPost.distributedEditUrl.match( /post=(\d+)/ );
				let distributedPostId;
				if ( matches ) {
					distributedPostId = matches[ 1 ];
				}
				// // With classic editor rendered content is transferred and not the shortcode.
				// cy.verifyShortCodeTermId(
				// 	distributedPostId,
				// 	shortcodeTermName,
				// 	'http://localhost/second/'
				// );
				cy.verifyRelatedPostMeta(
					distributedPostId,
					relatedPostTitle,
					'http://localhost/second/'
				);
			} );
		} );
	} );
} );
