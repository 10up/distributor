<?php
/**
 * Test case class that provides us with some baseline shortcut functionality
 *
 * @package distributor
 */

/**
 * Class extends \WPAssure\PHPUnit\TestCase
 */
class TestCase extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Distribute a post
	 *
	 * @param  \WPAssure\PHPUnit\Actor $actor            WP Assure actore
	 * @param  int                     $post_id          Post ID to distribute
	 * @param  int                     $to_connection_id Connection ID to distribute from
	 * @param  string                  $from_blog_slug   Blog where original post lives. Empty string is main blog.
	 * @param  string                  $post_status      New post status
	 * @return array
	 */
	protected function distributePost( \WPAssure\PHPUnit\Actor $I, $post_id, $to_connection_id, $from_blog_slug = '', $post_status = 'publish' ) {
		$info = [
			'original_edit_url' => $from_blog_slug . '/wp-admin/post.php?post=' . $post_id . '&action=edit',
		];

		// Now distribute a published post
		$I->moveTo( $info['original_edit_url'] );

		try {
			$info['original_front_url'] = $I->getElement( '#wp-admin-bar-view a')->getAttribute( 'href' );
		} catch ( \Exception $e ) {
			$info['original_front_url'] = $I->getElement( '#wp-admin-bar-preview a')->getAttribute( 'href' );
		}

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		// Distribute post

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="' . $to_connection_id . '"]' );

		usleep( 500 );

		if ( 'publish' === $post_status ) {
			$I->click( '#dt-as-draft' ); // Uncheck for publish, draft is checked by default
		}

		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		// Now let's navigate to the new post

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="' . $to_connection_id . '"] a' );

		$I->waitUntilElementVisible( '#wp-admin-bar-edit' );

		$info['distributed_front_url'] = $I->getCurrentUrl();

		$I->click( '#wp-admin-bar-edit a' );

		$I->waitUntilElementVisible( '#title' );

		$info['distributed_edit_url'] = $I->getCurrentUrl();

		$info['distributed_post_id'] = (int) $I->getElement( '#post_ID' )->getAttribute( 'value' );

		return $info;
	}
}
