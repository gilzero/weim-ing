<?php

namespace Drupal\Tests\type_tray\FunctionalJavascript;

/**
 * Functional test.
 *
 * @group type_tray
 */
class FunctionalTest extends TypeTrayWebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Create a few content types to be used in the test.
    $this->drupalCreateContentType(['type' => 'one', 'name' => 'Type One']);
    $this->drupalCreateContentType(['type' => 'two', 'name' => 'Type Two']);
    $this->drupalCreateContentType(['type' => 'three', 'name' => 'Type Three']);
    // Visit the settings form and define some new values.
    $this->drupalGet('/admin/config/content/type-tray/settings');
    $assert_session->pageTextContains('Type Tray Settings');
    $categories_value = "categ1|Category One\ncateg2|Category Two";
    $page->fillField('Categories', $categories_value);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved');
  }

  /**
   * Tests that it's possible to categorize content types.
   */
  public function testCategories() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Place content types into the categories and verify they are displayed
    // as expected in the front-end.
    $this->drupalGet('/admin/structure/types/manage/one');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type One has been updated');
    $this->drupalGet('/admin/structure/types/manage/two');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ2');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Two has been updated');

    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1', 'Type One');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ2', 'Type Two');
    $assert_session->elementTextContains('css', '.type-tray-category.category---none', 'Type Three');

    // Make sure sorting by weight works as expected.
    $this->drupalGet('/admin/structure/types/manage/one');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-type-tray-type-weight')
      ->setValue(3);
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type One has been updated');
    $this->drupalGet('/admin/structure/types/manage/two');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-type-tray-type-weight')
      ->setValue(2);
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Two has been updated');
    $this->drupalGet('/admin/structure/types/manage/three');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-type-tray-type-weight')
      ->setValue(1);
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Three has been updated');
    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    $category_one = $assert_session->elementExists('css', '.type-tray-category.category--categ1');
    $position1 = strpos($category_one->getHtml(), 'Type One');
    $position2 = strpos($category_one->getHtml(), 'Type Two');
    $position3 = strpos($category_one->getHtml(), 'Type Three');
    $this->assertTrue($position3 < $position2);
    $this->assertTrue($position2 < $position1);
  }

  /**
   * Tests the elements displayed on the page, as well as in-page search.
   */
  public function testPageDisplay() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    // Categorize the types and give them some extended description.
    $this->drupalGet('/admin/structure/types/manage/one');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-description')
      ->setValue('Short description for type one');
    $assert_session->elementExists('css', '#edit-type-tray-type-description-value')
      ->setValue('Extended description for type one');
    $assert_session->elementExists('css', '#edit-type-tray-existing-nodes-link-text')
      ->setValue('View existing Type One content');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type One has been updated');
    $this->drupalGet('/admin/structure/types/manage/two');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-type-tray-existing-nodes-link-text')
      ->setValue('View existing Type Two content');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Two has been updated');
    $this->drupalGet('/admin/structure/types/manage/three');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $page->selectFieldOption('Category', 'categ1');
    $assert_session->elementExists('css', '#edit-type-tray-existing-nodes-link-text')
      ->setValue('View existing Type Three content');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Three has been updated');
    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1 a[href*="/node/add/one"]', 'Type One');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1 .type-tray__short-desc', 'Short description for type one');
    $assert_session->pageTextNotContains('Extended description for type one');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1 a[href*="/node/add/two"]', 'Type Two');
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1 a[href*="/node/add/three"]', 'Type Three');

    // Test the existing nodes link feature.
    $assert_session->pageTextContains('View existing Type One content');
    $assert_session->pageTextContains('View existing Type Two content');
    $assert_session->pageTextContains('View existing Type Three content');
    // The text can be changed, and removing it disables the link.
    $this->drupalGet('/admin/structure/types/manage/one');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $assert_session->elementExists('css', '#edit-type-tray-existing-nodes-link-text')
      ->setValue('New link for type one');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type One has been updated');
    $this->drupalGet('/admin/structure/types/manage/two');
    $assert_session->elementExists('css', '.vertical-tabs li a[href="#edit-type-tray"]')
      ->click();
    $this->saveHtmlOutput();
    $assert_session->elementExists('css', '#edit-type-tray-existing-nodes-link-text')
      ->setValue('');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The content type Type Two has been updated');
    $this->drupalGet('/node/add');
    $assert_session->pageTextNotContains('View existing Type One content');
    $assert_session->pageTextContains('New link for type one');
    $assert_session->pageTextNotContains('View existing Type Two content');
    $card_two = $assert_session->elementExists('css', '.type-tray-teaser--two');
    $assert_session->elementNotExists('css', '.type-tray__node-link', $card_two);
    $assert_session->pageTextContains('View existing Type Three content');

    // Check in-page searching.
    $search_box = $assert_session->elementExists('css', '.type-tray__header #header-search');
    $search_box->setValue('thre');
    $session->getDriver()->keyUp($search_box->getXpath(), 'e');
    $session->wait(1000);
    $this->saveHtmlOutput();
    $assert_session->pageTextNotContains('Type One');
    $assert_session->pageTextNotContains('Type Two');
    $assert_session->pageTextContains('Type Three');
  }

}
