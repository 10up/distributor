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
		$this->assertEquals( "<p>https://twitter.com/10up/status/1067517868441387008</p>\n<p>&nbsp;</p>", $content->getText(), 'oEmbed was not pushed properly' );
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
		$this->assertEquals( "https://twitter.com/10up/status/1067517868441387008\n\n&nbsp;", $content->getText(), 'oEmbed was not pulled properly' );
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
		$I->fillField( '#dt_password', 'password' );
		$I->pressEnterKey( '#create-connection' );


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
		$this->assertEquals( "<p>https://twitter.com/10up/status/1067517868441387008</p>\n<p>&nbsp;</p>", $content->getText(), 'oEmbed was not pushed properly' );
	}


}
