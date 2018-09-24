<?php
/**
 * Test internal connection push
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class InternalPushTest extends \TestCase {

	/**
	 * Test pushing a draft
	 */
	public function testPushDraftPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$this->distributePost( $I, 40, 2, '', 'draft' );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->seeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}

	/**
	 * Test pushing as published
	 */
	public function testPushPublishPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$this->distributePost( $I, 40, 2 );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->dontSeeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}
}
