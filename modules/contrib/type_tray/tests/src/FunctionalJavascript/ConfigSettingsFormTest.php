<?php

namespace Drupal\Tests\type_tray\FunctionalJavascript;

use Drupal\type_tray\Controller\TypeTrayController;

/**
 * Test the config settings form.
 *
 * @group type_tray
 */
class ConfigSettingsFormTest extends TypeTrayWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type to be used in the test.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic Page']);
  }

  /**
   * Tests the configuration form.
   */
  public function testConfigForm() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Nothing breaks if we visit both the type tray config and the node-add
    // page prior to configuring the module.
    $this->drupalGet('/admin/structure/types/manage/page');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('Will be used to group content types together during the editorial workflow');
    // Visiting this now also warms the cache so we ensure that after saving
    // the config form the cached version of this page is invalidated.
    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    $assert_session->pageTextContains(TypeTrayController::UNCATEGORIZED_LABEL);

    // Visit the settings form and define some new values.
    $this->drupalGet('/admin/config/content/type-tray/settings');
    $assert_session->pageTextContains('Type Tray Settings');
    // Entering just a label in the categories generates the key automatically.
    $categories_value = 'Only Category Value';
    $page->fillField('Categories', $categories_value);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved');
    $categories_element = $assert_session->elementExists('css', '#edit-categories');
    $this->assertSame('Only-Category-Value|Only Category Value', $categories_element->getValue());
    // Set something more normal and test other values.
    $categories_value = "categ1|Category One\ncateg2|Category Two";
    $page->fillField('Categories', $categories_value);
    $page->fillField('Fallback category', 'Miscellaneous');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved');
    $categories_element = $assert_session->elementExists('css', '#edit-categories');
    $this->assertSame($categories_value, $categories_element->getValue());
    $fallback_label_element = $assert_session->elementExists('css', '#edit-fallback-label');
    $this->assertSame('Miscellaneous', $fallback_label_element->getValue());

    // Check these settings are properly reflected elsewhere.
    $this->drupalGet('/admin/structure/types/manage/page');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $assert_session->optionExists('Category', 'categ1');
    $assert_session->optionExists('Category', 'categ2');
    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    $assert_session->pageTextNotContains(TypeTrayController::UNCATEGORIZED_LABEL);
    $assert_session->pageTextContains('Miscellaneous');
  }

}
