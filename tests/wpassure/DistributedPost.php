<?php
/**
 * Test distributed post UIs
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class DistributedPost extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Test distributed count on post edit screen for a post that has been distributed TO multiple
	 * locations
	 */
	public function testDistributedCount() {
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

		$I->refresh();

		$I->waitUntilElementVisible( '#title' );

		$I->seeText( '1', '#distributed-to strong' );

		// Distribute post again

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="3"]' );

		usleep( 500 );

		// Distribute post (as draft)
		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		$I->refresh();

		$I->waitUntilElementVisible( '#title' );

		$I->seeText( '2', '#distributed-to strong' );
	}

	/**
	 * Test UI for a post that has been distributed (not original)
	 */
	public function testDistributedFrom() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		// Publish post

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-view a' );

		// Get URL to check canonical later on distributed copy

		$original_url = $I->getCurrentURL();

		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

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

		// Make sure we see distributed icon for first post
		$I->seeElement( '.wp-list-table tbody tr:nth-child(1) .distributor img' );

		$I->click( '.wp-list-table tbody tr:nth-child(1) a.row-title' );

		$I->waitUntilElementVisible( '#title' );

		// Make sure we see distributed time in publish box
		$I->seeText( 'Distributed on', '#syndicate-time' );

		// Make sure we see distributed status admin notice and that it shows as linked
		$I->seeText( 'Distributed from', '.syndicate-status');
		$I->seeText( 'unlink from the original', '.syndicate-status' );

		// Now let's check in the front end
		$I->click( '#wp-admin-bar-view a' );

		$I->waitUntilElementVisible( '#masthead' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		// Make sure the distributed admin bar menu shows the post has been distributed
		$I->seeText( 'This post has been distributed', '#distributor-push-wrapper .syndicated-notice');

		// Make sure canonical link contains the original
		$source = $I->getPageSource();

		$this->assertTrue( ( false !== strpos( $source, '<link rel="canonical" href="' . rtrim( $original_url, '/' ) ) ) );
	}
}
