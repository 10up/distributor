<?php
/**
 * Test internal pull
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class IntenralPullTest extends \TestCase {

	/**
	 * Test the correct posts show in "new", "pulled", "skipped"
	 */
	public function testPostShowingPerStatus() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table .page-title' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull&status=pulled' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeElement( '.wp-list-table tbody tr.no-items' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull&status=skipped' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeElement( '.wp-list-table tbody tr.no-items' );
	}

	/**
	 * Test pulling a post
	 */
	public function testPullPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$this->pullPost( $I, 40, 'two', '' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->dontSeeText( 'Test Post', '.wp-list-table .page-title' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull&status=pulled' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table .page-title' );
	}
}
