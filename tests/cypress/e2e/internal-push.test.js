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

	it( 'Should push sync the post data', () => {
		const postTitle = 'Post to push ' + randomName();
		const content = 'Test content';
		const categoryName = 'Category' + randomName();
		const tagName = 'Tag' + randomName();
		cy.createTerm( categoryName, 'category' );
		cy.createTerm( tagName, 'post_tag' );

		cy.createPost( { title: postTitle, content } ).then( ( post ) => {
			// Set category and tag
			cy.wpCli( `post term set ${ post.id } category ${ categoryName }` );
			cy.wpCli( `post term set ${ post.id } post_tag ${ tagName }` );

			// Set post meta.
			cy.wpCli(
				`wp post meta set ${ post.id } custom_meta_key custom_meta_value`
			);

			// Set Featured Image
			cy.uploadMedia( 'assets/img/banner-772x250.png' ).then(
				( media ) => {
					if ( media && media.mediaId ) {
						cy.wpCli(
							`wp post meta set ${ post.id } _thumbnail_id ${ media.mediaId }`
						);
					}
				}
			);

			cy.distributorPushPost( post.id, 'second', '', 'publish' ).then(
				( distributeInfo ) => {
					const siteUrl = `${ Cypress.config( 'baseUrl' ) }/second`;
					cy.visit( 'second/wp-admin/edit.php' );
					// Validate title
					cy.get( '.wp-list-table tbody tr:nth-child(1) .row-title' )
						.should( 'exist' )
						.contains( postTitle );

					// validate category
					cy.get(
						'.wp-list-table tbody tr:nth-child(1) .column-categories'
					)
						.should( 'exist' )
						.contains( categoryName );

					// validate tag
					cy.get(
						'.wp-list-table tbody tr:nth-child(1) .column-tags'
					)
						.should( 'exist' )
						.contains( tagName );

					// Validate post meta
					cy.wpCli(
						`wp post meta get ${ distributeInfo.distributedPostId } custom_meta_key --url=${ siteUrl }`
					)
						.its( 'stdout' )
						.should( 'contain', 'custom_meta_value' );

					// validate content.
					cy.wpCli(
						`wp post get ${ distributeInfo.distributedPostId } --field=content --url=${ siteUrl }`
					)
						.its( 'stdout' )
						.should( 'contain', content );

					// validate featured image.
					cy.wpCli(
						`wp post meta get ${ distributeInfo.distributedPostId } _thumbnail_id --url=${ siteUrl }`
					)
						.its( 'stdout' )
						.should( 'not.empty' );
				}
			);
		} );
	} );
} );
