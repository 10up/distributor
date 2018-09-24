<?php
/**
 * Test internal pull
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class InternalPullTest extends \TestCase {

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

	/**
	 * Test skipping a post
	 */
	public function testSkipPost() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->selectOptions( '#bulk-action-selector-top', 'bulk-skip' );

		$I->checkOptions( '.wp-list-table #cb-select-40');

		$I->click( '#doaction' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->moveTo( 'two/wp-admin/admin.php?page=pull&status=skipped' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table .page-title' );
	}
}
