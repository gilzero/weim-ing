<?php

declare(strict_types=1);

namespace Drupal\Tests\simple_block\Functional;

use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests Simple Block module functionality.
 *
 * @group simple_block
 */
class SimpleBlockTest extends BrowserTestBase {

  use AssertBlockAppearsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'simple_block',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the simple block functionality.
   */
  public function testBlockDisplay(): void {
    $this->config('system.site')->set('name', 'Hocus Pocus')->save();
    $this->drupalLogin($this->createUser([
      'administer blocks',
      'access administration pages',
    ]));

    // Add a block.
    $this->drupalGet('/admin/structure/block/simple-block/add');
    $this->submitForm([
      'id' => 'very_simple_block',
      'title' => 'A simple block',
      'content[value]' => 'Just a simple block...[site:name]',
    ], 'Save');
    $this->assertSession()->pageTextContains('Block very_simple_block has been added.');
    // Confirm the page returns to the list/collection page.
    $this->assertSession()->addressEquals('admin/structure/block/simple-block');
    $block = $this->drupalPlaceBlock('simple_block:very_simple_block');
    $this->getSession()->reload();
    $this->assertBlockAppears($block);
    // The token has been replaced.
    $this->assertSession()->pageTextContains('Just a simple block...Hocus Pocus');

    // Update the block.
    $this->drupalGet('/admin/structure/block/simple-block/manage/very_simple_block/edit');
    $this->submitForm([
      'title' => 'A simple block changed',
      'content[value]' => 'Just a simple changed block...',
    ], 'Save');
    $this->assertSession()->pageTextContains('Block very_simple_block has been updated.');
    // Confirm the page returns to the list/collection page.
    $this->assertSession()->addressEquals('admin/structure/block/simple-block');
    $this->assertBlockAppears($block);
    $this->assertSession()->pageTextContains('Just a simple changed block...');

    // Delete the block.
    $this->drupalGet('/admin/structure/block/simple-block/manage/very_simple_block/delete');
    $this->getSession()->getPage()->pressButton('Delete');
    $this->assertSession()->pageTextContains('The simple block A simple block changed has been deleted.');
    $this->assertSession()->pageTextContains('There are no simple block entities yet.');
    $this->assertNoBlockAppears($block);
    $this->assertSession()->pageTextNotContains('Just a simple changed block...');
  }

}
