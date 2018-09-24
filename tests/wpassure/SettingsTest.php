<?php
/**
 * Test settings
 *
 * @package distributor
 */

/**
 * PHPUnit test class
 */
class SettingsTest extends \TestCase {

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

	/**
	 * Test author bylines
	 */
	public function testAuthorBylineSetting() {
		$I = $this->getAnonymousUser();

		$I->loginAs( 'wpsnapshots' );

		// First save the setting as checked
		$I->moveTo( 'two/wp-admin/admin.php?page=distributor-settings' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->checkOptions( '.form-table input[type="checkbox"]' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$post_info = $this->distributePost( $I, 40, 2 );

		// Check front end
		$I->moveTo( $post_info['distributed_front_url'] );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Site One byline is shown
		$I->seeText( 'Site One', '.byline .author' );

		// Uncheck the setting
		$I->moveTo( 'two/wp-admin/admin.php?page=distributor-settings' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->uncheckOptions( '.form-table input[type="checkbox"]' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Verify byline is normal
		$I->moveTo( $post_info['distributed_front_url'] );

		$I->seeText( 'wpsnapshots', '.byline .author' );
	}
}
