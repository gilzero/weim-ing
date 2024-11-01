<?php

namespace Drupal\Tests\collapsiblock\Functional;

use Behat\Mink\Element\DocumentElement;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\node\NodeInterface;
use Drupal\Tests\collapsiblock\FunctionalJavascript\CollapsiblockJavaScriptTestBase;

/**
 * Test the Collapsiblock for the content type configuration.
 *
 * @group collapsiblock
 */
class LayoutBuilderUITest extends CollapsiblockJavaScriptTestBase {
  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'layout_builder',
    'block_content',
    'block',
    'node',
    'field_ui',
    'user',
  ];

  /**
   * The body field uuid.
   *
   * @var string
   */
  protected $bodyFieldBlockUuid;

  /**
   * The custom default block uuid.
   *
   * @var string
   */
  protected $customDefaultBlockUuid;

  /**
   * The editor block UUID.
   *
   * @var string
   */
  protected $customEditorBlockUuid;


  /**
   * The default theme to use.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * A user with all permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable Layout Builder for landing page.
    $this->createContentType(['type' => 'test_node']);

    // Create custom block.
    $block = BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic',
      'revision' => FALSE,
    ]);
    $block->save();
    block_content_add_body_field($block->id());

    // Enable the Layout builder.
    LayoutBuilderEntityViewDisplay::load('node.test_node.default')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'configure any layout',
      'create and edit custom blocks',
      'access contextual links',
      'administer node display',
    ]);

  }

  /**
   * Test block is collapsing in the Layout Builder UI.
   */
  public function testLayoutBuilderBlockIsCollapsed() {
    $page = $this->getSession()->getPage();

    // Create first node.
    $node = $this->drupalCreateNode([
      'type' => 'test_node',
      'title' => 'Homepage 1',
    ]);

    // Check as editor.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('node/' . $node->id() . '/layout');

    // Add block to default node display.
    $this->addBlock($node, $page);

    // Visit the node page.
    $this->drupalGet('/node/' . $node->id());

    $collapsiblockTestBlockTitleXpath = $this->assertSession()
      ->buildXPathQuery('//div[contains(@class,"collapsiblockTitle")]');
    $collapsiblockTestBlockContentXpath = $this->assertSession()
      ->buildXPathQuery('//div[contains(@class,"collapsiblockContent")]');

    // We expected that content is hidden.
    $beforeTitle = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath);
    $this->assertNotNull($beforeTitle);
    $this->assertTrue($beforeTitle->isVisible());
    $beforeContent = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockContentXpath);
    $this->assertNotNull($beforeContent);
    $this->assertFalse($beforeContent->isVisible());

    // Click by toggle button (the block title).
    $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath)->click();
    sleep(1);

    // We expected that content is displayed.
    $beforeTitle = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath);
    $this->assertNotNull($beforeTitle);
    $this->assertTrue($beforeTitle->isVisible());
    $beforeContent = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockContentXpath);
    $this->assertNotNull($beforeContent);
    $this->assertTrue($beforeContent->isVisible());

  }

  /**
   * Adds custom inline block to default section.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node object.
   * @param \Behat\Mink\Element\DocumentElement $page
   *   The Layout builder page.
   */
  public function addBlock(NodeInterface $node, DocumentElement $page): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/layout_builder/configure/section/defaults/node.' . $node->bundle() . '.default/0');
    $edit = [];
    $this->submitForm($edit, 'Update');
    $this->drupalGet('/layout_builder/add/block/defaults/node.' . $node->bundle() . '.default/0/content/inline_block:basic');
    $edit = [];

    // Add custom block.
    $edit['settings[label]'] = 'Default custom block title';
    $edit['settings[block_form][body][0][value]'] = 'Default custom block content';
    $edit['settings[collapsiblock_settings][collapse_action]'] = '3';
    $this->submitForm($edit, 'Add block');

    // Save Layout configuration.
    $page->pressButton('Save layout');
  }

}
