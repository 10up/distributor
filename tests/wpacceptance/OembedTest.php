<?php
/**
 * Test oembeds.
 *
 * @package distributor
 */

/**
 * PHPUnit test class.
 */
class OembedTests extends \TestCase {

	/**
	 * Test network pushing content with an oEmbed.
	 */
	public function testOembedNetworkPushedContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		// Push post to connection 2.
		$post_info = $this->pushPost( $I, 48, 2 );
		$I->moveTo( $post_info['distributed_edit_url'] );

		// Switch to the text editor.
		$I->waitUntilElementVisible( '#content-html' );
		$I->jsClick( '#content-html' );

		// Grab the post content.
		$I->waitUntilElementVisible( '.wp-editor-area' );
		$content = $I->getElement( '.wp-editor-area' );

		// Test the distributed post content.
		$this->assertEquals(
			"<p>https://twitter.com/10up/status/1067517868441387008</p>\n<p>&nbsp;</p>",
			$content->getText(),
			'oEmbed was not pushed properly over a network connection'
		);
	}

	/**
	 * Test network pulling content with an oEmbed.
	 */
	public function testOembedNetworkPulledContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$post_info = $this->pullPost( $I, 48, 'two', '' );
		$I->moveTo( $post_info['distributed_edit_url'] );

		// Switch to the text editor.
		$I->waitUntilElementVisible( '#content-html' );
		$I->jsClick( '#content-html' );

		// Grab the post content.
		$I->waitUntilElementVisible( '.wp-editor-area' );
		$content = $I->getElement( '.wp-editor-area' );

		// Test the distributed post content.
		$this->assertEquals(
			"https://twitter.com/10up/status/1067517868441387008\n\n&nbsp;",
			$content->getText(),
			'oEmbed was not pulled properly over a network connection'
		);
	}

	/**
	 * Test external pushing content with an oEmbed.
	 */
	public function testOembedExternalPushedContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php?post_type=dt_ext_connection' );

		$I->fillField( '#title', 'Test External Connection' );

		$I->fillField( '#dt_username', 'wpsnapshots' );

		$I->fillField( '#dt_external_connection_url', $this->getWPHomeUrl() . '/two/wp-json' );

		$I->fillField( '#dt_password', 'password' );

		$I->waitUntilElementContainsText( 'Connection established', '.endpoint-result' );

		$I->pressEnterKey( '#create-connection' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->moveTo( 'wp-admin/admin.php?page=distributor' );

		$post_info = $this->pushPost( $I, 48, 51, '', 'publish', true );
		$I->moveTo( 'two/wp-admin/edit.php' );

		// Switch to the distributed post.
		$I->waitUntilElementVisible( '#the-list' );
		$I->jsClick( 'a.row-title' );

		// Switch to the text editor.
		$I->waitUntilElementVisible( '#content-html' );
		$I->jsClick( '#content-html' );

		// Grab the post content.
		$I->waitUntilElementVisible( '.wp-editor-area' );
		$content = $I->getElement( '.wp-editor-area' );

		// Test the distributed post content.
		$this->assertEquals(
			"<p>https://twitter.com/10up/status/1067517868441387008</p>\n<p>&nbsp;</p>",
			$content->getText(),
			'oEmbed was not pushed properly over an external connection'
		);
	}

	/**
	 * Test external pulling content with an oEmbed.
	 */
	public function testOembedExternalPulledContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'two/wp-admin/post-new.php?post_type=dt_ext_connection' );

		$I->fillField( '#title', 'Test External Connection' );

		$I->fillField( '#dt_username', 'wpsnapshots' );

		$I->fillField( '#dt_external_connection_url', $this->getWPHomeUrl() . '/wp-json' );


		$I->fillField( '#dt_password', 'password' );

		$I->waitUntilElementContainsText( 'Connection established', '.endpoint-result' );

		$I->pressEnterKey( '#create-connection' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->moveTo( 'two/wp-admin/admin.php?page=distributor' );

		// Pull post from external connection.
		$post_info = $this->pullPost( $I, 48, 'two', '', 'Test External Connection' );
		$I->moveTo( $post_info['distributed_edit_url'] );

		// Switch to the text editor.
		$I->waitUntilElementVisible( '#content-html' );
		$I->jsClick( '#content-html' );

		// Grab the post content.
		$I->waitUntilElementVisible( '.wp-editor-area' );
		$content = $I->getElement( '.wp-editor-area' );

		// Test the distributed post content.
		$this->assertEquals(
			"<p>https://twitter.com/10up/status/1067517868441387008</p>\n<p>&nbsp;</p>",
			$content->getText(),
			'oEmbed was not pulled properly over an external connection'
		);
	}
}
