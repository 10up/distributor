const { randomName } = require( '../support/functions' );

describe( '[Classic Editor] Image distribution tests', () => {
	let externalConnectionOneToTwo, externalConnectionTwoToOne;
	const attachImages = () => {
		cy.get( '#postimagediv a#set-post-thumbnail' ).click();
		cy.get( '.media-menu-item' ).contains( 'Media Library' ).click();
		cy.get( '.attachments-browser .attachment' ).first().click();
		cy.get( '.media-button-select' ).click();
		cy.get( '#postimagediv img' ).should( 'be.visible' );

		cy.get( 'button#insert-media-button' ).click();
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait( 1000 );
		cy.get( '.attachments-wrapper .attachments li.attachment:visible' )
			.eq( 1 )
			.click();
		cy.get( '.button-primary.media-button-insert' ).click();
	};

	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		cy.networkActivatePlugin( 'classic-editor' );
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

		cy.visit( 'wp-admin/admin.php?page=distributor-settings' );
		cy.get( '.form-table input[type="checkbox"]' ).first().check();
		cy.get( 'input[type="radio"]' ).check( 'attached' );
		cy.get( '#submit' ).click();

		cy.visit( '/second/wp-admin/admin.php?page=distributor-settings' );
		cy.get( '.form-table input[type="checkbox"]' ).first().check();
		cy.get( 'input[type="radio"]' ).check( 'attached' );
		cy.get( '#submit' ).click();

		cy.visit( '/wp-admin/upload.php?mode=grid' );
		// eslint-disable-next-line cypress/no-unnecessary-waiting
		cy.wait( 2000 );
		cy.get( 'body' ).then( ( $body ) => {
			if ( $body.find( 'ul.attachments li' ).length === 0 ) {
				cy.uploadImage( './assets/img/banner-772x250.png' );
				cy.uploadImage( './assets/img/banner-1544x500.png' );
			}
		} );
	} );

	after( () => {
		cy.networkDeactivatePlugin( 'classic-editor' );
	} );

	it( 'Should distribute images when pushing to network connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			beforeSave: attachImages,
		} ).then( ( sourcePostID ) => {
			cy.distributorPushPost(
				sourcePostID,
				'second',
				'',
				'publish',
				false,
				true
			).then( ( distributedPost ) => {
				cy.postContains(
					distributedPost.distributedPostId,
					' src="http://localhost/wp-content/uploads/sites/2/', // For the push to network connection, image url will be generated from the source site, this is due to https://core.trac.wordpress.org/ticket/25650
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should distribute images when pulling from network connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			beforeSave: attachImages,
		} ).then( ( sourcePostID ) => {
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
				cy.postContains(
					distributedPostId,
					' src="http://localhost/second/wp-content/uploads/',
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should distribute images when pushing to external connections.', () => {
		const postTitle = 'Post to push ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			beforeSave: attachImages,
		} ).then( ( sourcePostID ) => {
			cy.distributorPushPost(
				sourcePostID,
				externalConnectionOneToTwo,
				'',
				'publish',
				false,
				true
			).then( ( distributedPost ) => {
				cy.postContains(
					distributedPost.distributedPostId,
					' src="http://localhost/second/wp-content/uploads/',
					'http://localhost/second/'
				);
			} );
		} );
	} );

	it( 'Should distribute image when pulling from external connections.', () => {
		const postTitle = 'Post to pull ' + randomName();

		cy.classicCreatePost( {
			title: postTitle,
			beforeSave: attachImages,
		} ).then( ( sourcePostID ) => {
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
				cy.postContains(
					distributedPostId,
					' src="http://localhost/second/wp-content/uploads/',
					'http://localhost/second/'
				);
			} );
		} );
	} );
} );
