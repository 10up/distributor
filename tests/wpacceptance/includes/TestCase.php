<?php
/**
 * Test case class that provides us with some baseline shortcut functionality
 *
 * @package distributor
 */

/**
 * Class extends \WPAcceptance\PHPUnit\TestCase
 */
class TestCase extends \WPAcceptance\PHPUnit\TestCase {

	/**
	 * Push a post
	 *
	 * @param  \WPAcceptance\PHPUnit\Actor $actor            WP Acceptance actor
	 * @param  int                     $post_id          Post ID to distributor
	 * @param  int                     $to_connection_id Connection ID to distribute from
	 * @param  string                  $from_blog_slug   Blog where original post lives. Empty string is main blog.
	 * @param  string                  $post_status      New post status.
	 * @param  boolean                 $external         Is this an external connection push?
	 * @return array
	 */
	protected function pushPost( \WPAcceptance\PHPUnit\Actor $I, $post_id, $to_connection_id, $from_blog_slug = '', $post_status = 'publish', $external = false ) {
		$info = [
			'original_edit_url' => $from_blog_slug . '/wp-admin/post.php?post=' . $post_id . '&action=edit',
		];

		// Now distribute a published post
		$I->moveTo( $info['original_edit_url'] );

		try {
			$info['original_front_url'] = $I->getElementAttribute( '#wp-admin-bar-view a', 'href' );
		} catch ( \Exception $e ) {
			$info['original_front_url'] = $I->getElementAttribute( '#wp-admin-bar-preview a', 'href' );
		}

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .new-connections-list' );

		// Distribute post

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="' . $to_connection_id . '"]' );

		usleep( 500 );

		if ( 'publish' === $post_status ) {
			$I->click( '#dt-as-draft' ); // Uncheck for publish, draft is checked by default
		}

		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		// Now let's navigate to the new post - only works for network connections.
		if ( ! $external ) {

			$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="' . $to_connection_id . '"] a' );

			$I->waitUntilElementVisible( '#wp-admin-bar-edit' );

			$info['distributed_front_url'] = $I->getCurrentUrl();

			$I->click( '#wp-admin-bar-edit a' );

			$I->waitUntilElementVisible( '#title' );

			$info['distributed_edit_url'] = $I->getCurrentUrl();

			$info['distributed_post_id'] = (int) $I->getElementAttribute( '#post_ID', 'value' );
		}

		return $info;
	}

	/**
	 * Pull a post
	 *
	 * @param  \WPAcceptance\PHPUnit\Actor $actor            WP Acceptance actor
	 * @param  int                     $original_post_id Original post id
	 * @param  int                     $to_blog_slug     Blog slug where post is being pulled in
	 * @param  string                  $from_blog_slug   Blog we are pulling from. Empty string is main blog
	 * @param  string                  $use_connection   The full connection name to use on the pull screen.
	 *
	 * @return array
	 */
	protected function pullPost( \WPAcceptance\PHPUnit\Actor $I, $original_post_id, $to_blog_slug, $from_blog_slug = '', $use_connection = false ) {
		if ( ! empty( $to_blog_slug ) ) {
			$to_blog_slug .= '/';
		}

		if ( ! empty( $from_blog_slug ) ) {
			$from_blog_slug .= '/';
		}

		$info = [
			'original_edit_url' => $from_blog_slug . '/wp-admin/post.php?post=' . $original_post_id . '&action=edit',
		];

		$I->moveTo( $to_blog_slug . 'wp-admin/admin.php?page=pull' );

		if ( $use_connection ) {
			$I->checkOptions( '#pull_connections', $use_connection );
			$I->waitUntilElementVisible( '.wp-list-table #cb-select-' . $original_post_id );
		}

		$I->checkOptions( '.wp-list-table #cb-select-' . $original_post_id );

		$I->click( '#doaction' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->click( '.pulled > a' );
		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->moveMouse( '.wp-list-table tbody tr:nth-child(1) .page-title' );
		$I->click( '.wp-list-table tbody tr:nth-child(1) .page-title .view a' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$info['distributed_view_url'] = $I->getCurrentUrl();

		$I->click( '#wp-admin-bar-edit a' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$info['distributed_edit_url'] = $I->getCurrentUrl();

		return $info;
	}
}
