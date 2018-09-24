<?php
/**
 * Test settings
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class SettingsTest extends \WPAssure\PHPUnit\TestCase {

	/**
	 * Test that settings actually save
	 */
	public function testSettingsSave() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( 'wp-admin/admin.php?page=distributor-settings' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Round one options edit/save

		$I->checkOptions( '.form-table input[type="checkbox"]' );
		$I->checkOptions( '.form-table input[type="radio"][value="featured"]' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->seeCheckboxIsChecked( '.form-table input[type="checkbox"]' );
		$I->seeCheckboxIsChecked( '.form-table input[type="radio"][value="featured"]' );

		// Round two options edit/save

		$I->uncheckOptions( '.form-table input[type="checkbox"]' );
		$I->checkOptions( '.form-table input[type="radio"][value="attached"]' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->dontSeeCheckboxIsChecked( '.form-table input[type="checkbox"]' );
		$I->seeCheckboxIsChecked( '.form-table input[type="radio"][value="attached"]' );
	}
}
