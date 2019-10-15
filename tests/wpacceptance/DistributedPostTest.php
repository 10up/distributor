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
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		self::assertPostFieldContains( 40, 'post_title', 'Test Post' );

		// Distribute post
		$post_info = $this->pushPost( $I, 40, 2 );

		$I->moveTo( $post_info['original_edit_url'] );

		$I->waitUntilElementVisible( 'body.post-php' );;

		$I->seeText( '1', '#distributed-to strong' );

		// Distribute post
		$post_info = $this->pushPost( $I, 40, 3 );

		$I->moveTo( $post_info['original_edit_url'] );

		$I->waitUntilElementVisible( 'body.post-php' );;

		$I->seeText( '2', '#distributed-to strong' );
	}

	/**
	 * Test UI for a post that has been distributed (not original)
	 */
	public function testDistributedFrom() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$post_info = $this->pushPost( $I, 40, 2 );

		// Now let's navigate to the new post

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		// Make sure we see distributed icon for first post
		$I->seeElement( '.wp-list-table tbody tr:nth-child(1) .distributor img' );

		$I->moveTo( $post_info['distributed_edit_url'] );

		$I->waitUntilElementVisible( 'body.post-php' );

		$editor_has_blocks =  $this->editorHasBlocks( $I );
		// Make sure we see distributed time in publish box
		if ( $editor_has_blocks ) {
			$I->seeText( 'Distributed on:', '#distributed-from' );
		} else {
			$I->seeText( 'Distributed on', '#syndicate-time' );
		}

		// Make sure we see distributed status admin notice and that it shows as linked
		if ( $editor_has_blocks ) {
			$I->seeText( 'Distributed from Site One. The original will update this unless youunlink from original.View Original', '.components-notice__content' );
			$element = $I->getElement( '.components-notice__action' );
			$I->seeText( 'unlink from original.', '.components-notice__action' );
		} else {
			$I->seeText( 'Distributed from', '.syndicate-status');
			$I->seeText( 'unlink from the original', '.syndicate-status' );
		}

		// Now let's check in the front end
		$I->moveTo( $post_info['distributed_front_url'] );

		$I->waitUntilElementVisible( '#masthead' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		usleep( 750 );

		// Make sure the distributed admin bar menu shows the post has been distributed
		$I->seeText( 'This post has been distributed', '#distributor-push-wrapper .syndicated-notice');

		// Make sure canonical link contains the original
		$source = $I->getPageSource();

		$this->assertTrue( ( false !== strpos( $source, '<link rel="canonical" href="' . rtrim( $post_info['original_front_url'], '/' ) ) ) );
	}

	/**
	 * Test network push status updates and the `dt_distribute_post_status` filter.
	 */
	public function testPushDistributedStatus() {

		$I = $this->openBrowserPage();
		$I->loginAs( 'wpsnapshots' );

		$editor_has_blocks =  $this->editorHasBlocks( $I );
		// Don't test in block editor.
		if ( $editor_has_blocks ) {
			return;
		}

		// TEST SCENARIO: with 'dt_distribute_post_status' FALSE (DEFAULT).
		$post_info = $this->pushPost( $I, 40, 2, '', 'Draft' );

		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( 'body.post-php' );

		$status = $I->getElement( '#post-status-display' );
		$content = trim( $I->getElementInnerText( $status ) );

		// The remote post should be in a draft state.
		$I->seeText( 'Draft', '#post-status-display' );

		// Publish the remote post - status will be 'published'.
		$I->click( '#publish' );
		$I->waitUntilElementVisible( '#wpadminbar' );

		// Change the origin post status to draft.
		$I->moveTo( $post_info['original_edit_url'] );
		$I->waitUntilElementVisible( 'body.post-php' );

		// The origin post should be in a published state.
		$I->seeText( 'Published', '#post-status-display' );

		// Change the remote post status to Draft.
		$I->click( '.edit-post-status' );
		$I->waitUntilElementVisible( '#post_status' );
		$I->selectOptionByValue( '#post_status', 'draft' );
		$I->click( '.save-post-status' );

		usleep( 300 );
		$I->click( '#publish' );
		$I->waitUntilElementVisible( '#wpadminbar' );

		// The remote post will still be in a published status, the post status is not distributed.
		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( 'body.post-php' );
		$I->seeText( 'Published', '#post-status-display' );

		// TEST SCENARIO: with 'dt_distribute_post_status' true - activate the helper plugin.
		$I->moveTo( '/wp-admin/plugins.php' );
		$I->click( '[data-slug="enable-post-status-distribution"] .activate a' );
		$I->waitUntilElementVisible( '#message' );

		// Update the origin post
		$I->moveTo( $post_info['original_edit_url'] );
		$I->waitUntilElementVisible( 'body.post-php' );

		// The origin post should be in a draft state.
		$I->seeText( 'Draft', '#post-status-display' );

		// Change the remote post title and update.
		$I->typeInField( '#title', 'New test title' );
		$I->click( '#publish' );
		$I->waitUntilElementVisible( '#wpadminbar' );

		// The remote post should now in a draft status, the post status is distributed.
		$I->moveTo( $post_info['distributed_edit_url'] );
		$I->waitUntilElementVisible( 'body.post-php' );
		$I->seeText( 'Draft', '#post-status-display' );

	}

}
