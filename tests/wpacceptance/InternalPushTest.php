<?php
/**
 * Test internal connection push
 *
 * @package distributor
 */

use Facebook\WebDriver\WebDriverBy;

/**
 * PHPUnit test class
 */
class InternalPushTest extends \TestCase {

	/**
	 * Test pushing a draft
	 */
	public function testPushDraftPost() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$this->pushPost( $I, 40, 2, '', 'draft' );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->seeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}

	/**
	 * Test pushing as published
	 */
	public function testPushPublishPost() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$this->pushPost( $I, 40, 2 );

		$I->moveTo( '/two/wp-admin/edit.php' );

		$I->waitUntilElementVisible( '.wp-list-table' );

		$I->seeText( 'Test Post', '.wp-list-table tbody tr:nth-child(1) .row-title' );
		$I->dontSeeText( 'Draft', '.wp-list-table tbody tr:nth-child(1) .post-state' );
	}

	/**
	 * Test that all data gets synced on push
	 */
	public function testPushDataSync() {
		$I = $this->openBrowserPage();

		$I->loginAs( 'wpsnapshots' );

		$I->moveTo( '/wp-admin/post.php?post=40&action=edit' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		$editor_has_blocks =  $this->editorHasBlocks( $I );

		// Skip this test in Gutenberg for now.
		// @todo This needs to be re-written for the Gutenberg UI or easier a prebuilt post added to the docker images we can test push data sync with.
		if ( $editor_has_blocks ) {
			return;
		}

		// Fill out title
		$I->fillField( '#title', 'Test Title' );

		// Add custom meta
		$I->click( '#show-settings-link' );

		$I->checkOptions( '#postcustom-hide' );

		$I->waitUntilElementVisible( '#enternew' );

		$I->jsClick( '#enternew' );

		$I->waitUntilElementVisible( '#metakeyinput' );

		$I->setElementProperty( '#metakeyinput', 'value', 'custom_meta_key' );
		$I->setElementProperty( '#metavalue', 'value', 'custom_meta_value' );

		$I->click( '#newmeta-submit' );

		// Add tag
		$I->setElementProperty( '#new-tag-post_tag', 'value', 'tag-one' );
		$I->click( '.tagadd' );

		// Add category
		$I->click( '#category-add-toggle' );
		usleep( 500 );
		$I->setElementProperty( '#newcategory', 'value', 'Test Category' );
		$I->click( '#category-add-submit' );

		$I->scrollTo( 0, 0 );

		// Fill in content
		$I->click( '#content-html' );
		$I->fillField( '#content', 'The content' );

		// Set featured image
		$I->click( '#set-post-thumbnail' );

		$I->waitUntilElementVisible( '.media-modal-content' );

		$I->attachFile( '.media-modal-content input[type="file"]', __DIR__ . '/img/browser-frame.jpg' );

		$I->waitUntilElementEnabled( '.media-modal-content .media-button-select' );

		$I->click( '.media-modal-content .media-button-select' );

		$I->waitUntilElementVisible( '#remove-post-thumbnail' );

		$I->scrollTo( 0, 0 );

		$I->click( '#publish' );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Distribute post
		$post_info = $this->pushPost( $I, 40, 2 );

		$I->moveTo( $post_info['distributed_edit_url'] );

		$I->waitUntilElementVisible( '#wpadminbar' );

		// Now check everything

		// See title
		$I->seeValueInAttribute( '#title', 'value', 'Test Title' );

		// See tag
		$I->seeText( 'tag-one', '.tagchecklist');

		// See image
		$I->seeElement( '#postimagediv img' );

		// See content
		$I->seeFieldValue( '#content', 'The content' );

		// Check custom meta
		$I->click( '#show-settings-link' );

		$I->checkOptions( '#postcustom-hide' );
		$I->seeTextInSource( 'custom_meta_key' );
		$I->seeTextInSource( 'custom_meta_value' );

		// Get Element containing category, then check it for checked input
		$category_parent = $I->getElementContaining( 'Test Category' );

		foreach ( $category_parent as $element ) {
			if ( 'LABEL' === $I->getElementTagName( $element ) ) {
				$category_parent = $element;
				break;
			}
		}

		$checked = $I->getElement( 'input:checked', $category_parent );

		$this->assertTrue( ! empty( $checked ) );
	}
}
