<?php
/**
 * Test distributed post UIs
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class DistributedPost extends \TestCase {

	/**
	 * Test distributed count on post edit screen for a post that has been distributed TO multiple
	 * locations
	 */
	public function testDistributedCount() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		// Distribute post
		$post_info = $this->pushPost( $I, 40, 2 );

		$I->moveTo( $post_info['original_edit_url'] );

		$I->waitUntilElementVisible( '#title' );

		$I->seeText( '1', '#distributed-to strong' );

		// Distribute post
		$post_info = $this->pushPost( $I, 40, 3 );

		$I->moveTo( $post_info['original_edit_url'] );

		$I->waitUntilElementVisible( '#title' );

		$I->seeText( '2', '#distributed-to strong' );
	}

	/**
	 * Test UI for a post that has been distributed (not original)
	 */
	public function testDistributedFrom() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$post_info = $this->pushPost( $I, 40, 2 );

		// Now let's navigate to the new post

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		// Make sure we see distributed icon for first post
		$I->seeElement( '.wp-list-table tbody tr:nth-child(1) .distributor img' );

		$I->moveTo( $post_info['distributed_edit_url'] );

		$I->waitUntilElementVisible( '#title' );

		// Make sure we see distributed time in publish box
		$I->seeText( 'Distributed on', '#syndicate-time' );

		// Make sure we see distributed status admin notice and that it shows as linked
		$I->seeText( 'Distributed from', '.syndicate-status');
		$I->seeText( 'unlink from the original', '.syndicate-status' );

		// Now let's check in the front end
		$I->moveTo( $post_info['distributed_front_url'] );

		$I->waitUntilElementVisible( '#masthead' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		usleep( 500 );

		// Make sure the distributed admin bar menu shows the post has been distributed
		$I->seeText( 'This post has been distributed', '#distributor-push-wrapper .syndicated-notice');

		// Make sure canonical link contains the original
		$source = $I->getPageSource();

		$this->assertTrue( ( false !== strpos( $source, '<link rel="canonical" href="' . rtrim( $post_info['original_front_url'], '/' ) ) ) );
	}
}
