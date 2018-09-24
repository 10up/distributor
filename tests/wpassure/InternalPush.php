<?php
/**
 * Test internal connection push
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class InternalPushTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Test pushing a draft
	 */
	public function testPushDraftPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );

		// Distribute post (as draft)
		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->seeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}

	/**
	 * Test pushing as published
	 */
	public function testPushPublishPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );

		$I->click( '#dt-as-draft' ); // Uncheck for publish, draft is checked by default

		// Distribute post (as draft)
		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->dontSeeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}
}
