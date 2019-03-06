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

		$I->waitUntilElementVisible( '#title' );

		// I see linked link
		$I->seeLink( 'unlink from the original' );

		// I cant interact with title field
		$I->cannotInteractWithField( '#title' );

		// Unlink post
		$I->click( '.syndicate-status span a' );

		$I->waitUntilElementVisible( '#title' );

		// I see unlinked text
		$I->seeText( 'This post has been unlinked from the original', '.syndicate-status' );

		// I can interact with title field
		$I->canInteractWithField( '#title' );
	}
}
