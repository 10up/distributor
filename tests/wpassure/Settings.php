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

		// Now distribute a published post
		$I->moveTo( 'wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wp-admin-bar-distributor a' );

		$I->moveMouse( '#wp-admin-bar-distributor a' );

		$I->click( '#wp-admin-bar-distributor a' );

		// Distribute post

		$I->click( '#distributor-push-wrapper .new-connections-list .add-connection[data-connection-id="2"]' );

		usleep( 500 );

		$I->click( '#dt-as-draft' ); // Uncheck for publish, draft is checked by default

		$I->click( '#distributor-push-wrapper .syndicate-button' );

		$I->waitUntilElementVisible( '#distributor-push-wrapper .dt-success' );

		// Now let's navigate to the new post

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->click( '.wp-list-table tbody tr:nth-child(1) a.row-title' );

		$I->waitUntilElementVisible( '#title' );

		// Now let's check in the front end
		$I->click( '#wp-admin-bar-view a' );

		$post_url = $I->getCurrentUrl();

		// Site One byline is shown
		$I->seeText( 'Site One', '.byline .author' );

		// Uncheck the setting
		$I->moveTo( 'two/wp-admin/admin.php?page=distributor-settings' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$I->uncheckOptions( '.form-table input[type="checkbox"]' );

		$I->click( '#submit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Verify byline is normal
		$I->moveTo( $post_url );

		$I->seeText( 'wpsnapshots', '.byline .author' );
	}
}
