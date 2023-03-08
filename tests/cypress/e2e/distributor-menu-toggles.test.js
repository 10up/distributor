describe( 'Distributor post toggles as expected in block editor', () => {
	before( () => {
		cy.login();
	} );

	it( 'Distribution info is hidden when post is not published', () => {
		cy.createPost( {
			title: 'Test Post',
			content: 'Test Content',
			status: 'draft',
		} ).then( ( post ) => {
			cy.openDocumentSettingsPanel( 'Distributor' );
			cy.get( '.distributor-panel > p' ).should(
				'contain.text',
				'Distribution options available once published'
			);

			cy.get( '.editor-post-publish-panel__toggle' ).should(
				'be.enabled'
			);
			cy.get( '.editor-post-publish-panel__toggle' ).click();
			cy.intercept( { method: 'POST' }, ( req ) => {
				const body = req.body;
				if ( body.status === 'publish' ) {
					req.alias = 'publishPost';
				}
			} );
			cy.get( '.editor-post-publish-button' ).click();
			cy.get(
				'.components-snackbar, .components-notice.is-success'
			).should( 'be.visible' );
			cy.wait( '@publishPost' ).then( ( response ) => {
				let _a;
				cy.wrap(
					( _a = response.response ) === null || _a === void 0
						? void 0
						: _a.body
				);
			} );
		} );
		cy.get(
			'.editor-post-publish-panel__header .components-button'
		).click();
		cy.openDocumentSettingsPanel( 'Distributor' );
		cy.get( '.distributor-panel .distributor-toggle button' ).should(
			'contain.text',
			'Distribute post'
		);
	} );
} );
