describe( 'oEmbed Test', () => {
	before( () => {
		cy.login();
		cy.networkActivatePlugin( 'distributor' );
		// Ignore exception.
		// TODO: Figure out why this is happening and remove this.
		Cypress.on( 'uncaught:exception', ( err, runnable ) => {
			if (
				err.message.includes( 'ResizeObserver loop limit exceeded' )
			) {
				return false;
			}
		} );
	} );

	it( 'Should push oEmbed content (Network push)', () => {
		const tweetUrl = 'https://twitter.com/10up/status/1067517868441387008';
		cy.createTweetOEmbedPost( tweetUrl ).then( ( post ) => {
			cy.distributorPushPost( post.id, 'second', '', 'draft' ).then(
				( distributeInfo ) => {
					const siteUrl = `http://localhost/second`;

					// validate oEmbed content.
					cy.postContains(
						distributeInfo.distributedPostId,
						`<!-- wp:embed {"url":"${ tweetUrl }"`,
						siteUrl
					);
				}
			);
		} );
	} );

	it( 'Should pull oEmbed content (Network pull)', () => {
		const tweetUrl = 'https://twitter.com/10up/status/1067517868441387008';
		cy.createTweetOEmbedPost( tweetUrl ).then( ( post ) => {
			cy.distributorPullPost( post.id, 'second', '' ).then(
				( distributeInfo ) => {
					const siteUrl = `http://localhost/second`;
					const postId =
						distributeInfo.distributedViewUrl.split( '?p=' )[ 1 ];

					// validate oEmbed content.
					cy.postContains(
						postId,
						`<!-- wp:embed {"url":"${ tweetUrl }"`,
						siteUrl
					);
				}
			);
		} );
	} );

	it( 'Should push oEmbed content (External connection push)', () => {
		// Create external connection if not yet
		const connectionName = 'oEmbed connection';
		cy.createExternalConnection(
			connectionName,
			'http://localhost/second/wp-json'
		);

		const tweetUrl = 'https://twitter.com/10up/status/1067517868441387008';
		cy.createTweetOEmbedPost( tweetUrl ).then( ( post ) => {
			cy.distributorPushPost( post.id, connectionName ).then(
				( distributeInfo ) => {
					const siteUrl = `http://localhost/second`;

					// validate oEmbed content.
					cy.postContains(
						distributeInfo.distributedPostId,
						`<!-- wp:embed {"url":"${ tweetUrl }"`,
						siteUrl
					);
				}
			);
		} );
	} );

	it( 'Should pull oEmbed content (External connection pull)', () => {
		// Create external connection if not yet
		const connectionName = 'oEmbed connection';
		cy.createExternalConnection(
			connectionName,
			'http://localhost/wp-json',
			'admin',
			'password',
			'second'
		);

		const tweetUrl = 'https://twitter.com/10up/status/1067517868441387008';
		cy.createTweetOEmbedPost( tweetUrl ).then( ( post ) => {
			cy.distributorPullPost(
				post.id,
				'second',
				'',
				connectionName
			).then( ( distributeInfo ) => {
				const siteUrl = `http://localhost/second`;
				const postId =
					distributeInfo.distributedViewUrl.split( '?p=' )[ 1 ];

				// validate oEmbed content.
				cy.postContains(
					postId,
					`<!-- wp:embed {"url":"${ tweetUrl }"`,
					siteUrl
				);
			} );
		} );
	} );
} );
