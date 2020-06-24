<?php
/**
 * Test linking and unlinking
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class LinkUnlinkTest extends \TestCase {

	/**
	 * Test unlinking
	 */
	public function testUnlinkPublishPost() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$post_info = $this->pushPost( $I, 40, 2 );

		// Now let's navigate to the new post

		$I->moveTo( $post_info['distributed_edit_url'] );

		$I->waitUntilElementVisible( 'body.post-php' );

		$editor_has_blocks =  $this->editorHasBlocks( $I );

		// I see linked link
		if ( $editor_has_blocks ) {
			$I->seeLink( 'View Original' );
			// Unlink post
			$I->click( '.components-notice__action' );

			$I->waitUntilElementVisible( 'body.post-php' );;

			sleep( 1 );

			// I see unlinked text
			$I->seeText( 'Originally distributed from Site One. This Post has been unlinked from the original. However, you can alwaysrestore it.View Original', '.components-notice__content' );

			// I can interact with title field
			$I->canInteractWithField( '#post-title-0' );

		} else {
			$I->seeLink( 'unlink from the original' );
			// I cant interact with title field
			$I->cannotInteractWithField( '#title' );
			// Unlink post
			$I->click( '.syndicate-status span a' );

			$I->waitUntilElementVisible( 'body.post-php' );;

			// I see unlinked text
			$I->seeText( 'This post has been unlinked from the original', '.syndicate-status' );

			// I can interact with title field
			$I->canInteractWithField( '#title' );
		}

	}
}
