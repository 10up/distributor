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

		// Don't test in block editor.
		$editor_has_blocks =  $this->editorHasBlocks( $I );
		if ( $editor_has_blocks ) {
			return;
		}

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
	 * Test network push status updates with the `dt_distribute_post_status` filter.
	 */
	public function testNetworkPushStatusDistribution() {
		$I = $this->openBrowserPage();
		$I->loginAs( 'wpsnapshots' );

		// Don't test in block editor.
		$editor_has_blocks =  $this->editorHasBlocks( $I );
		if ( $editor_has_blocks ) {
			return;
		}

		$post_info = $this->pushPost( $I, 40, 2 );
		$this->statusDistributionTest( $post_info, $I );
	}
	/**
	 * Test network pull status updates with the `dt_distribute_post_status` filter.
	 */
	public function testNetworkPullStatusDistribution() {
		$I = $this->openBrowserPage();
		$I->loginAs( 'wpsnapshots' );

		// Don't test in block editor.
		$editor_has_blocks =  $this->editorHasBlocks( $I );
		if ( $editor_has_blocks ) {
			return;
		}

		$post_info = $this->pullPost( $I, 40, 'two', '' );
		$this->statusDistributionTest( $post_info, $I );
	}

	/**
	 * Test external push status updates with the `dt_distribute_post_status` filter.
	 */
	public function testExternalPushStatusDistribution() {
		$I = $this->openBrowserPage();
		$I->loginAs( 'wpsnapshots' );

		// Don't test in block editor.
		$editor_has_blocks =  $this->editorHasBlocks( $I );
		if ( $editor_has_blocks ) {
			return;
		}

		// Create an external connection.
		$this->createExternalConnection( $I );
		$url = $I->getCurrentUrl();
		preg_match( '/post=(\d+)/', $url, $matches );

		$post_info = $this->pushPost( $I, 40, (int) $matches[1], '', 'publish', true );
		$I->moveTo( 'two/wp-admin/edit.php' );

		// Grab the distributed post edit URL.
		$I->waitUntilElementVisible( '#the-list' );
		$I->click( 'a.row-title' );
		$I->waitUntilNavigation();
		$url = $I->getCurrentUrl();
		$post_info['distributed_edit_url'] = $url;

		$this->statusDistributionTest( $post_info, $I );
	}

	/**
	 * Test external pull status updates with the `dt_distribute_post_status` filter.
	 */
	public function testExternalPullStatusDistribution() {
		$I = $this->openBrowserPage();
		$I->loginAs( 'wpsnapshots' );

		// Don't test in block editor.
		$editor_has_blocks =  $this->editorHasBlocks( $I );
		if ( $editor_has_blocks ) {
			return;
		}

		// Create an external connection.
		$this->createExternalConnection( $I, 'two', '' );
		// Pull post from external connection.
		$post_info = $this->pullPost( $I, 40, 'two', '', 'Test External Connection' );
		$this->statusDistributionTest( $post_info, $I );
	}

}
