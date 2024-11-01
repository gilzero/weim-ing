<?php

namespace Drupal\Tests\manage_display\Functional;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests node display is configurable.
 *
 * @group field
 */
class NodeManageDisplayTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'manage_display',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Test nodes in page and teaser view modes.
   */
  public function testNodeTeaserAndPage() {
    // Configure display.
    $display = EntityViewDisplay::load('node.page.default');
    $display->setComponent('uid', ['type' => 'submitted'])
      ->setComponent('created', ['type' => 'timestamp'])
      ->save();

    // Create user and node.
    $user = $this->drupalCreateUser();
    $node = $this->drupalCreateNode(['uid' => $user->id()]);
    $assert = $this->assertSession();

    // Check page display.
    $this->drupalGet($node->toUrl());
    $assert->elementTextContains('css', 'div.node__content footer', 'Submitted by');
    $assert->elementTextContains('css', 'footer span.field--name-uid', $user->getAccountName());
    $assert->elementNotExists('css', 'footer span.field--name-uid a');
    $assert->elementTextContains('css', 'div.node__content footer', 'on');
    $assert->elementExists('css', 'footer span.field--name-created');
    $assert->elementTextContains('css', 'h1.page-title span', $node->getTitle());

    // Check teaser display.
    $this->drupalGet('node');
    $assert->elementTextContains('css', 'div.field--name-title h2 a[href="' . $node->toUrl()->toString() . '"]', $node->getTitle());
  }

}
