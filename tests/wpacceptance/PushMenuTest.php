<?php
/**
 * Test internal connection push
 *
 * @package distributor
 */
/**
 * PHPUnit test class
 */
class PushMenuTest extends \TestCase {

	/**
	 * Test that the menu shows on hover
	 */
	public function testMenuItemHover() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->seeElement( '#distributor-push-wrapper .new-connections-list' );
	}

	/**
	 * Test connection cross out
	 */
	public function testConnectionCrossOutOnPush() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .new-connections-list' );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );
		try {
			if ( $I->getElement( '.nux-dot-tip__disable' ) ) {
				$I->click( '.nux-dot-tip__disable' );
			}
		} catch ( \Exception $e ) {}

		$I->takeScreenshot( 'screenshots/testConnectionCrossOutOnPush' );

		// Distribute post (as draft)
		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		// See crossed out element
		$I->seeElement( '#distributor-push-wrapper .new-connections-list .add-connection.syndicated[data-connection-id="2"]' );
	}

	/**
	 * Test that we can select connections properly
	 */
	public function testSelectConnection() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		try {
			if ( $I->getElement( '.nux-dot-tip__disable' ) ) {
				$I->click( '.nux-dot-tip__disable' );
			}
		} catch ( \Exception $e ) {}

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .new-connections-list' );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );

		// Make sure added connection is visible
		$I->seeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="2"]' );

		// Make sure there is only one added connection
		$elements = $I->getElements( '#distributor-push-wrapper .selected-connections-list .added-connection' );

		$this->assertEquals( 1, count( $elements ) );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="3"]' );

		// Make sure TWO added connections are visible
		$I->seeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="2"]' );
		$I->seeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="3"]' );

		// Remove 2nd connection
		$I->click( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="3"] .remove-connection' );

		// Make sure 2nd connection is gone and 1st connection is still there
		$I->dontSeeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="3"]' );
		$I->seeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="2"]' );

		// Remove 1st connection
		$I->click( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="2"] .remove-connection' );

		// Make sure both connections are gone
		$I->dontSeeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="3"]' );
		$I->dontSeeElement( '#distributor-push-wrapper .selected-connections-list .added-connection[data-connection-id="2"]' );
	}
}
