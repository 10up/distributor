const { randomName } = require( '../support/functions' );

describe( '[Block Editor] Stored ID handling tests', () => {
	// prevent uncaught exceptions from failing the test on WP trunk.
	let externalConnectionOneToTwo, externalConnectionTwoToOne, postId, termId;
	const relatedPostTitle = 'Sample Related Post' + randomName();
	const shortcodeTermName = 'Sample Term' + randomName();
	const addPostContent = () => {
		cy.insertBlockLocal( 'core/shortcode', 'Shortcode' ).then( ( id ) => {
			cy.getBlockEditor()
				.find( `#${ id } textarea.blocks-shortcode__textarea` )
				.type( `[dt_term_shortcode id="${ termId }"]` );
		} );
		cy.insertBlockLocal( 'core/cover/cover', 'Cover' ).then( ( id ) => {
			cy.getBlockEditor()
				.find( `#${ id } button.components-button` )
				.contains( 'Media Library' )
				.click();
			cy.get( '.attachments-browser .attachment' ).first().click();
			cy.get( '.media-button-select' ).click();
			cy.getBlockEditor().find( `#${ id } img` ).should( 'be.visible' );
		} );
	};

	before( () => {
		// Prevent uncaught exceptions from failing the test on WP trunk.
		Cypress.on( 'uncaught:exception', () => {
			return false;
		} );
		cy.login();
		cy.networkDeactivatePlugin( 'classic-editor' );
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

	[ true, false ].forEach( ( withType ) => {
		const prefix = withType ? '[With Type]' : '[Without Type]';
		it( `${ prefix } Should handle stored IDs when pushing to network connections.`, () => {
			if ( withType ) {
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType }`
				);
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType } --url=http://localhost/second`
				);
			} else {
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type`
				);
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type --url=http://localhost/second`
				);
			}

			const postTitle = 'Post to push ' + randomName();

			cy.createPost( {
				title: postTitle,
				beforeSave: addPostContent,
			} ).then( ( sourcePost ) => {
				cy.wpCli(
					`wp post meta update ${ sourcePost.id } related_post_id ${ postId }`
				);
				cy.distributorPushPost(
					sourcePost.id,
					'second',
					'',
					'publish'
				).then( ( distributedPost ) => {
					cy.verifyShortCodeTermId(
						distributedPost.distributedPostId,
						shortcodeTermName,
						'http://localhost/second/'
					);
					cy.verifyRelatedPostMeta(
						distributedPost.distributedPostId,
						relatedPostTitle,
						'http://localhost/second/'
					);
					cy.postContains(
						distributedPost.distributedPostId,
						'src="http://localhost/wp-content/uploads/sites/2/', // For the push to network connection, image url will be generated from the source site, this is due to https://core.trac.wordpress.org/ticket/25650
						'http://localhost/second/'
					);
					if ( ! withType ) {
						cy.postContains(
							distributedPost.distributedPostId,
							'"url":"http://localhost/wp-content/uploads/sites/2/', // For the push to network connection, image url will be generated from the source site, this is due to https://core.trac.wordpress.org/ticket/25650
							'http://localhost/second/'
						);
					}
				} );
			} );
		} );

		it( `${ prefix } handle stored IDs when pulling from network connections.`, () => {
			const postTitle = 'Post to pull ' + randomName();
			if ( withType ) {
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType }`
				);
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType } --url=http://localhost/second`
				);
			} else {
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type`
				);
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type --url=http://localhost/second`
				);
			}

			cy.createPost( {
				title: postTitle,
				beforeSave: addPostContent,
			} ).then( ( sourcePost ) => {
				cy.wpCli(
					`wp post meta update ${ sourcePost.id } related_post_id ${ postId }`
				);
				cy.distributorPullPost(
					sourcePost.id,
					'second',
					'',
					'localhost'
				).then( ( distributedPost ) => {
					cy.closeWelcomeGuide();
					const matches =
						distributedPost.distributedEditUrl.match(
							/post=(\d+)/
						);
					let distributedPostId;
					if ( matches ) {
						distributedPostId = matches[ 1 ];
					}

					cy.verifyShortCodeTermId(
						distributedPostId,
						shortcodeTermName,
						'http://localhost/second/'
					);
					cy.verifyRelatedPostMeta(
						distributedPostId,
						relatedPostTitle,
						'http://localhost/second/'
					);
					cy.postContains(
						distributedPostId,
						'src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
					if ( ! withType ) {
						cy.postContains(
							distributedPostId,
							'"url":"http://localhost/second/wp-content/uploads/',
							'http://localhost/second/'
						);
					}
				} );
			} );
		} );

		it( `${ prefix } handle stored IDs when pushing to external connections.`, () => {
			const postTitle = 'Post to push ' + randomName();
			if ( withType ) {
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType }`
				);
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType } --url=http://localhost/second`
				);
			} else {
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type`
				);
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type --url=http://localhost/second`
				);
			}

			cy.createPost( {
				title: postTitle,
				beforeSave: addPostContent,
			} ).then( ( sourcePost ) => {
				cy.wpCli(
					`wp post meta update ${ sourcePost.id } related_post_id ${ postId }`
				);
				cy.distributorPushPost(
					sourcePost.id,
					externalConnectionOneToTwo,
					'',
					'publish'
				).then( ( distributedPost ) => {
					cy.verifyShortCodeTermId(
						distributedPost.distributedPostId,
						shortcodeTermName,
						'http://localhost/second/'
					);
					cy.verifyRelatedPostMeta(
						distributedPost.distributedPostId,
						relatedPostTitle,
						'http://localhost/second/'
					);
					cy.postContains(
						distributedPost.distributedPostId,
						'src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
					if ( ! withType ) {
						cy.postContains(
							distributedPost.distributedPostId,
							'"url":"http://localhost/second/wp-content/uploads/',
							'http://localhost/second/'
						);
					}
				} );
			} );
		} );

		it( `${ prefix } handle stored IDs when pulling from external connections.`, () => {
			const postTitle = 'Post to pull ' + randomName();
			if ( withType ) {
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType }`
				);
				cy.wpCli(
					`wp option update distributor_registered_data_with_type ${ withType } --url=http://localhost/second`
				);
			} else {
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type`
				);
				cy.wpCli(
					`wp option delete distributor_registered_data_with_type --url=http://localhost/second`
				);
			}

			cy.createPost( {
				title: postTitle,
				beforeSave: addPostContent,
			} ).then( ( sourcePost ) => {
				cy.wpCli(
					`wp post meta update ${ sourcePost.id } related_post_id ${ postId }`
				);
				cy.distributorPullPost(
					sourcePost.id,
					'/second/', // Pull to second site.
					'', // From primary site.
					externalConnectionTwoToOne
				).then( ( distributedPost ) => {
					cy.closeWelcomeGuide();
					const matches =
						distributedPost.distributedEditUrl.match(
							/post=(\d+)/
						);
					let distributedPostId;
					if ( matches ) {
						distributedPostId = matches[ 1 ];
					}
					cy.verifyShortCodeTermId(
						distributedPostId,
						shortcodeTermName,
						'http://localhost/second/'
					);
					cy.verifyRelatedPostMeta(
						distributedPostId,
						relatedPostTitle,
						'http://localhost/second/'
					);
					cy.postContains(
						distributedPostId,
						'src="http://localhost/second/wp-content/uploads/',
						'http://localhost/second/'
					);
					if ( ! withType ) {
						cy.postContains(
							distributedPostId,
							'"url":"http://localhost/second/wp-content/uploads/',
							'http://localhost/second/'
						);
					}
				} );
			} );
		} );
	} );
} );
