<?php
/**
 * Test linking and unlinking
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class LinkUnlinkTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Test unlinking
	 */
	public function testUnlinkPublishPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		// Publish post

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		// Distribute post

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );

		$I->click( '#dt-as-draft' ); // Uncheck for publish, draft is checked by default

		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		// Now let's navigate to the new post

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->click( '.wp-list-table tbody tr:nth-child(1) a.row-title' );

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
