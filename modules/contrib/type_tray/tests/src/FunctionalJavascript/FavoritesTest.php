<?php

namespace Drupal\Tests\type_tray\FunctionalJavascript;

/**
 * Covers the "Add to Favorites" functionality.
 *
 * @group type_tray
 */
class FavoritesTest extends TypeTrayWebDriverTestBase {

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
    // Visit the settings form and define some new values.
    $this->drupalGet('/admin/config/content/type-tray/settings');
    $assert_session->pageTextContains('Type Tray Settings');
    $categories_value = "categ1|Category One\ncateg2|Category Two";
    $page->fillField('Categories', $categories_value);
    $page->pressButton('Save configuration');
    $assert_session->pageTextContains('The configuration options have been saved');
  }

  /**
   * Tests that users can select types as favorites.
   */
  public function testFavorites() {
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

    // Log in as a different user and do some favoriting.
    $user1 = $this->createUser([
      'create one content',
      'view the administration theme',
    ]);
    $this->drupalLogout();
    $this->drupalLogin($user1);

    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    // User 1 only has one type visible in there.
    $assert_session->elementTextContains('css', '.type-tray-category.category--categ1', 'Type One');
    $assert_session->pageTextNotContains('Type Two');
    // Favorites category isn't on the page.
    $assert_session->pageTextNotContains('Favorites');
    $assert_session->elementNotExists('css', '.type-tray-category.category--type-tray__favorites');
    // We have the markup to select it as favorite.
    $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="false"]');
    $assert_session->elementNotExists('css', '.type-tray-teaser--one a[aria-checked="true"]');
    $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="false"] .favorite-link__icon');
    $message_element = $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="false"] .favorite-link__message');
    $this->assertStringContainsString('Add Type One to favorites', $message_element->getOuterHtml());
    // Click the favorites on type one.
    $favorite_link = $assert_session->elementExists('css', '.type-tray-teaser--one .favorite-link');
    $favorite_link->click();
    $this->saveHtmlOutput();
    // We now have a favorites category, with type one in there.
    $assert_session->pageTextContains('Favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--one');
    // The element is still at its original category.
    $assert_session->elementExists('css', '.type-tray-category.category--categ1 .type-tray-teaser--one');
    // The markup now offers to remove it from favorites.
    $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="true"]');
    $assert_session->elementNotExists('css', '.type-tray-teaser--one a[aria-checked="false"]');
    $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="true"] .favorite-link__icon');
    $message_element = $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="true"] .favorite-link__message');
    $this->assertStringContainsString('Remove Type One from favorites', $message_element->getOuterHtml());
    // Click to remove from favorites.
    $favorite_link = $assert_session->elementExists('css', '.type-tray-teaser--one .favorite-link');
    $favorite_link->click();
    $this->saveHtmlOutput();
    // We are back to where we started.
    $assert_session->pageTextNotContains('Favorites');
    $assert_session->elementNotExists('css', '.type-tray-category.category--type-tray__favorites');
    $assert_session->elementExists('css', '.type-tray-teaser--one a[aria-checked="false"]');
    $assert_session->elementNotExists('css', '.type-tray-teaser--one a[aria-checked="true"]');
    // Mark it as favorites again.
    $favorite_link = $assert_session->elementExists('css', '.type-tray-teaser--one .favorite-link');
    $favorite_link->click();
    $this->saveHtmlOutput();

    // Log in as a different user and verify the favorites don't mix up.
    $user2 = $this->createUser([
      'create one content',
      'create two content',
      'view the administration theme',
    ]);
    $this->drupalLogout();
    $this->drupalLogin($user2);
    $this->drupalGet('/node/add');
    $assert_session->pageTextContains('GRID | LIST');
    // Favorites category isn't on the page.
    $assert_session->pageTextNotContains('Favorites');
    $assert_session->elementNotExists('css', '.type-tray-category.category--type-tray__favorites');
    // We have links to mark types one and two as favorites. Mark "two" as such.
    $assert_session->elementExists('css', '.type-tray-teaser--one .favorite-link');
    $assert_session->elementExists('css', '.type-tray-category.category--categ2 .type-tray-teaser--two');
    $favorite_link2 = $assert_session->elementExists('css', '.type-tray-teaser--two .favorite-link');
    $favorite_link2->click();
    $this->saveHtmlOutput();
    $assert_session->pageTextContains('Favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--categ2 .type-tray-teaser--two');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--two');

    // Switch users back and forth and check there's no stale cache served.
    $this->drupalLogout();
    $this->drupalLogin($user1);
    $this->drupalGet('/node/add');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--categ1 .type-tray-teaser--one');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--one');
    $assert_session->elementNotExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--two');
    $this->drupalLogout();
    $this->drupalLogin($user2);
    $this->drupalGet('/node/add');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites');
    $assert_session->elementExists('css', '.type-tray-category.category--categ2 .type-tray-teaser--two');
    $assert_session->elementExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--two');
    $assert_session->elementNotExists('css', '.type-tray-category.category--type-tray__favorites .type-tray-teaser--one');
  }

}
