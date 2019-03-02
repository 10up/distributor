<?php
/**
 * Test external connection create
 *
 * @package distributor
 */

use WPAcceptance\EnvironmentFactory;

/**
 * PHPUnit test class
 */
class ExternalConnectionCreateTest extends \TestCase {

	/**
	 * Test creating an external connection. Test various connection statuses
	 */
	public function testCreateExternalConnection() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/post-new.php?post_type=dt_ext_connection' );

		$I->fillField( '#title', 'Test External Connection' );

		// First test no connection warning

		$I->fillField( '#dt_external_connection_url', 'badurl' );

		$I->waitUntilElementContainsText( 'No connection found', '.endpoint-result' );

		// Now test limited connection warning

		$I->fillField( '#dt_username', 'wpsnapshots' );

		$I->fillField( '#dt_external_connection_url', $this->getWPHomeUrl() . '/two/wp-json' );

		$I->waitUntilElementContainsText( 'Limited connection', '.endpoint-result' );

		// Now test good connection

		$I->fillField( '#dt_password', 'password' );

		$I->waitUntilElementContainsText( 'Connection established', '.endpoint-result' );

		$I->pressEnterKey( '#create-connection' );

		$I->waitUntilElementVisible( '.notice-success' );

		$I->moveTo( 'wp-admin/admin.php?page=distributor' );

		$I->seeText( 'Test External Connection' );
	}
}
