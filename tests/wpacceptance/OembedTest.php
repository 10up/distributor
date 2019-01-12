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
	 * Test pushing content with an oEmbed.
	 */
	public function testOembedPushedContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		// Push post to connection 2.
		$post_info = $this->pushPost( $I, 48, 2 );

		$I->moveTo( $post_info['distributed_edit_url'] );


		// Blog 3 is connection 2.
		switch_to_blog( 3 );
		$post = get_post( (int) $post_info['distributed_post_id'] );

		// Test the distributed post content.
		$I->assertTrue( $post['post_content'] === 'https:\/\/twitter.com\/10up\/status\/1067517868441387008\r\n\r\n&nbsp;', 'oEmbed was sent properly, without being expanded.' );
	}

	/**
	 * Test pulling content with an oEmbed.
	 */
	public function testOembedPulledContent() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$post_info = $this->pullPost( $I, 48, 'two', '' );
		switch_to_blog( 2 );
		$post = get_post( (int) $post_info['distributed_post_id'] );

		// Test the distributed post content.
		$I->assertTrue( $post['post_content'] === 'https:\/\/twitter.com\/10up\/status\/1067517868441387008\r\n\r\n&nbsp;', 'oEmbed was sent properly, without being expanded.' );
	}

}
