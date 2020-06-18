<?php
/**
 * Test Blocks blocks.
 *
 * @package distributor
 */

/**
 * PHPUnit test class.
 */

class BlocksTests extends \TestCase {

	public function addContentToTestPost( $I ) {
		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		$I->refresh();

		try {
			$I->getElement( '.editor-default-block-appender__content' );
			$I->waitUntilElementVisible( '.editor-default-block-appender__content' );
		} catch( \Exception $e ) {
			$I->waitUntilElementVisible( '.block-editor-default-block-appender__content' );
		}

		$this->disableFullscreenEditor( $I );

		$this->dismissNUXTip( $I );

		usleep( 500 );

		try {
			$I->getElement( '.editor-default-block-appender__content' );
			$I->getPage()->type( '.editor-default-block-appender__content', 'Lorem ipsum dolor sit amet.', [ 'delay' => 10 ] );
		} catch( \Exception $e ) {
			$I->getPage()->type( '.block-editor-default-block-appender__content', 'Lorem ipsum dolor sit amet.', [ 'delay' => 10 ] );
		}

		$I->jsClick( '.editor-post-publish-button' );

		try {
			$I->getElement( '.components-editor-notices__snackbar' );
			$I->waitUntilElementContainsText( 'Post updated', '.components-editor-notices__snackbar' );
		} catch( \Exception $e ) {
			$I->waitUntilElementVisible( '.is-success' );
		}
	}

	/**
	 * Test network pushing content with blocks.
	 */
	public function testBlocksNetworkPushedContent() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );
		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		// Only test for the block editor.
		if ( ! $this->editorHasBlocks( $I ) ) {
			return;
		}
		$this->addContentToTestPost( $I );
		$post_info = $this->pushPost( $I, 40, 2 );

		// Now let's navigate to the new post
		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( '#wpadminbar' );

		// Check that the blocks are intact by looking for the paragraph comment.
		$source = $I->getPageSource();

		$this->assertTrue(
			(bool) preg_match( '<!-- wp:paragraph -->', stripslashes( $source ) ),
			'Blocks were not pushed properly over an external connection'
		);
	}

	/**
	 * Test network pulling content with blocks.
	 */
	public function testBlocksNetworkPulledContent() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );
		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		// Only test for the block editor.
		if ( ! $this->editorHasBlocks( $I ) ) {
			return;
		}
		$this->addContentToTestPost( $I );
		$post_info = $this->pullPost( $I, 40, 'two', '' );
		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( '#wpadminbar' );
		$this->dismissNUXTip( $I );

		// Grab the post content.
		$source = $I->getPageSource();

		// Test the distributed post content.
		$this->assertTrue(
			(bool) preg_match( '<!-- wp:paragraph -->', $source ),
			'Blocks was not pulled properly over a network connection'
		);
	}

	/**
	 * Test external pushing content with blocks.
	 */
	public function testBlocksExternalPushedContent() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );
		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		// Only test for the block editor.
		if ( ! $this->editorHasBlocks( $I ) ) {
			return;
		}
		$this->addContentToTestPost( $I );
		$I->moveTo( 'wp-admin/post-new.php?post_type=dt_ext_connection' );

		$I->click( '.manual-setup-button' );

		$I->typeInField( '#title', 'Test External Connection' );

		$I->typeInField( '#dt_username', 'wpsnapshots' );

		$I->typeInField( '#dt_external_connection_url', $this->getWPHomeUrl() . '/two/wp-json' );

		$I->typeInField( '#dt_password', 'password' );

		$I->waitUntilElementContainsText( 'Connection established', '.endpoint-result' );

		$I->click( '#create-connection' );

		$I->waitUntilElementVisible( '.notice-success' );
		$url = $I->getCurrentUrl();
		preg_match( '/post=(\d+)/', $url, $matches );

		$post_info = $this->pushPost( $I, 40, (int) $matches[1], '', 'publish', true );
		$I->moveTo( 'two/wp-admin/edit.php' );

		// Switch to the distributed post.
		$I->waitUntilElementVisible( '#the-list' );
		$I->click( 'a.row-title' );
		$I->waitUntilNavigation();

		// Use moveTo to prime page object.
		$url = $I->getCurrentUrl();
		$I->moveTo( 'two/wp-admin/edit.php' );
		$I->moveTo( $url );

		$I->waitUntilElementVisible( 'body.post-php' );

		// Check that the blocks are intact by looking for the paragraph comment.
		$source = $I->getPageSource();

		$this->assertTrue(
			(bool) preg_match( '<!-- wp:paragraph -->', stripslashes( $source ) ),
			'Blocks were not pushed properly over an external connection'
		);
	}

	/**
	 * Test external pulling content with blocks.
	 */
	public function testBlocksExternalPulledContent() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );
		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		// Only test for the block editor.
		if ( ! $this->editorHasBlocks( $I ) ) {
			return;
		}
		$this->addContentToTestPost( $I );
		$I->moveTo( 'two/wp-admin/post-new.php?post_type=dt_ext_connection' );

		$I->click( '.manual-setup-button' );

		$I->typeInField( '#title', 'Test External Connection' );

		$I->typeInField( '#dt_username', 'wpsnapshots' );

		$I->typeInField( '#dt_external_connection_url', $this->getWPHomeUrl() . '/wp-json' );

		$I->typeInField( '#dt_password', 'password' );

		$I->waitUntilElementContainsText( 'Connection established', '.endpoint-result' );

		$I->click( '#create-connection' );

		$I->waitUntilElementVisible( '.notice-success' );

		// Pull post from external connection.
		$post_info = $this->pullPost( $I, 40, 'two', '', 'Test External Connection' );
		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( '#wpadminbar' );

		// Check that the blocks are intact by looking for the paragraph comment.
		$source = $I->getPageSource();
		$this->assertTrue(
			(bool) preg_match( '<!-- wp:paragraph -->', stripslashes( $source ) ),
			'Blocks were not pushed properly over an external connection'
		);
	}


}

