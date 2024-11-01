<?php

namespace Drupal\Tests\subpathauto\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Basic functional tests.
 *
 * @group subpathauto
 */
class SubPathautoFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'subpathauto',
    'node',
    'user',
    'block',
    'text',
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('local_tasks_block');

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $this->drupalCreateNode();

    ConfigurableLanguage::create(['id' => 'fi'])->save();

    // In order to reflect the changes for a multilingual site in the container
    // we have to rebuild it.
    $this->rebuildContainer();

    $aliasStorage = \Drupal::entityTypeManager()
      ->getStorage('path_alias');

    $path_alias = $aliasStorage->create([
      'path' => '/node/1',
      'alias' => '/kittens',
    ]);
    $path_alias->save();

    $alias_white_list = $this->container->get('path_alias.whitelist');
    $alias_white_list->set('node', TRUE);

    $admin_user = $this->drupalCreateUser([
      'bypass node access',
    ]);
    $this->drupalLogin($admin_user);
  }

  /**
   * Ensures that inbound and outbound paths are converted correctly.
   */
  public function testBasicIntegration(): void {
    $this->drupalGet('/kittens');
    $this->assertSession()->linkByHrefExists('/kittens/edit', 0, 'Local task link path that is subpath for an alias lead to correct URL.');

    $this->clickLink('Edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/kittens', 0, 'Local task link with alias lead to correct URL.');
    $this->assertSession()->linkByHrefExists('/kittens/delete', 0, 'Local task link path that is subpath for an alias lead to correct URL.');

    // Confirm that multiple aliases work together.
    $this->drupalCreateNode();
    \Drupal::entityTypeManager()
      ->getStorage('path_alias')
      ->create([
        'path' => '/node/2',
        'alias' => '/node/1/are-playing',
      ])->save();
    $this->drupalGet('/kittens/are-playing');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Ensures that language prefix is handled correctly.
   */
  public function testWithLanguagePrefix(): void {
    $this->drupalGet('/fi/kittens');
    $this->assertSession()->linkByHrefExists('/fi/kittens/edit', 0, 'Local task link path that is subpath for an alias lead to correct URL when language prefix exists.');

    $this->clickLink('Edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkByHrefExists('/fi/kittens', 0, 'Local task link with alias lead to correct URL when language prefix exists..');
    $this->assertSession()->linkByHrefExists('/fi/kittens/delete', 0, 'Local task link path that is subpath for an alias lead to correct URL when language prefix exists..');
  }

  /**
   * Ensures that non-existing paths are returning 404 page.
   */
  public function testNonExistingPath(): void {
    $this->drupalGet('/kittens/are-fake');
    $this->assertSession()->statusCodeEquals(404);
  }

}
