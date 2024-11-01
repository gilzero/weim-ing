<?php

namespace Drupal\Tests\collapsiblock\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Entity\LayoutEntityDisplayInterface;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\collapsiblock\Traits\LayoutBuilderInstanceSettingsTrait;

/**
 * Test that Layout Builder blocks can be affected by Collapsiblock.
 *
 * @group collapsiblock
 */
class LayoutBuilderTest extends CollapsiblockJavaScriptTestBase {
  use LayoutBuilderInstanceSettingsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'collapsiblock',
    'field_layout',
    'field_ui',
    'layout_builder',
    'node',
  ];

  /**
   * User with permissions to administer a node type's fields w/ layout builder.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $collapsiblockAdminUser;

  /**
   * A node whose "full" display's fields are managed by layout builder.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $collapsiblockTestNode;

  /**
   * A node type whose "full" display's fields are managed by layout builder.
   *
   * @var \Drupal\node\Entity\NodeType
   */
  protected $collapsiblockTestNodeType;

  /**
   * The "full" display whose fields are managed by layout builder.
   *
   * @var \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface
   */
  protected $collapsiblockTestNodeTypeDisplay;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a content type.
    $this->collapsiblockTestNodeType = $this->drupalCreateContentType();
    $this->collapsiblockTestNodeTypeDisplay
      = $this->createLayoutBuilderDisplayForNodeType($this->collapsiblockTestNodeType, 'full');

    // Create a user with permissions to administer blocks, node fields/display,
    // and enable layout builder.
    $this->collapsiblockAdminUser = $this->createUser([
      'configure any layout',
      'create and edit custom blocks',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]);
  }

  /**
   * Verify the layout builder block config form has Collapsiblock controls.
   */
  public function testConfigureForm() {
    // Prepare a URL for a component configuration page. Note we are testing the
    // form placed into the off-canvas dialog by core/drupal.dialog.off_canvas.
    $delta = 0;
    $components = $this->collapsiblockTestNodeTypeDisplay->getSection($delta)->getComponents();
    $url = Url::fromRoute('layout_builder.update_block', [
      'section_storage_type' => 'defaults',
      'section_storage' => $this->collapsiblockTestNodeTypeDisplay->id(),
      'delta' => 0,
      'region' => $this->getDefaultRegionFromDisplay($this->collapsiblockTestNodeTypeDisplay),
      'uuid' => reset($components)->getUuid(),
    ]);

    // Log in as the administrator; and go to a Layout Builder page.
    $this->drupalLogin($this->collapsiblockAdminUser);
    $this->drupalGet($url);

    // Test that the form controls are present.
    $this->assertSession()->checkboxChecked('edit-settings-collapsiblock-settings-collapse-action-0');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-1');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-2');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-3');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-4');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-5');

    // Submit the form with updated values.
    $configFormValues = [];
    $configFormValues['settings[collapsiblock_settings][collapse_action]'] = '2';
    $this->drupalGet($url);
    $this->submitForm($configFormValues, 'Update');

    // Test that the form controls now show the updated configuration.
    $this->drupalGet($url);
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-0');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-1');
    $this->assertSession()->checkboxChecked('edit-settings-collapsiblock-settings-collapse-action-2');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-3');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-4');
    $this->assertSession()->checkboxNotChecked('edit-settings-collapsiblock-settings-collapse-action-5');
  }

  /**
   * Verify that a LB block configured to collapse actually does so.
   */
  public function testLayoutBuilderBlockWillCollapse() {
    // Enable the Layout builder.
    LayoutBuilderEntityViewDisplay::load('node.' . $this->collapsiblockTestNodeType->id() . '.full')
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();

    // Add field Body as block to the section in Layout builder.
    $this->drupalLogin($this->collapsiblockAdminUser);
    $nid = $this->collapsiblockTestNodeType->id();
    $this->drupalGet('/layout_builder/add/block/defaults/node.' . $nid . '.full/0/content/field_block%3Anode%3A' . $nid . '%3A' . 'body');
    $configFormValues = [];
    $configFormValues['settings[label_display]'] = '1';
    $configFormValues['settings[collapsiblock_settings][collapse_action]'] = '2';
    $this->submitForm($configFormValues, 'Add block');
    $this->getSession()->getPage()->pressButton('Save layout');

    // Create a node of the test content type.
    $this->collapsiblockTestNode = $this->drupalCreateNode([
      'type' => $this->collapsiblockTestNodeType->id(),
      'title' => 'Test node',
    ]);

    $this->drupalLogout();

    $this->drupalLogout();
    $this->drupalLogin($this->getCollapsiblockUnprivilegedUser());

    // Navigate to the node.
    $this->drupalGet($this->collapsiblockTestNode->toUrl('canonical'));

    $collapsiblockTestBlockTitleXpath = $this->assertSession()
      ->buildXPathQuery('//div[contains(@class,"collapsiblockTitle")]//h2');
    $collapsiblockTestBlockContentXpath = $this->assertSession()
      ->buildXPathQuery('//div[contains(@class,"collapsiblockContent")]//div[2]/p');

    $beforeTitle = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath);
    $this->assertNotNull($beforeTitle);
    $this->assertTrue($beforeTitle->isVisible());
    $beforeContent = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockContentXpath);
    $this->assertNotNull($beforeContent);
    $this->assertTrue($beforeContent->isVisible());

    // Click on the block title.
    $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath)->click();
    sleep(2);

    // Check that the block title is visible but the contents are not visible
    // after the click.
    $afterTitle = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockTitleXpath);
    $this->assertNotNull($afterTitle);
    $this->assertTrue($afterTitle->isVisible());
    $afterContent = $this->getSession()->getPage()->find('xpath', $collapsiblockTestBlockContentXpath);
    $this->assertNotNull($afterContent);
    $this->assertFalse($afterContent->isVisible());
  }

  /**
   * Get the default region from a Layout Builder Section.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display
   *   The display to get the default region from.
   * @param int $sectionDelta
   *   The delta of the Layout Builder Section to get the default region from.
   *
   * @return string
   *   The name of the default region for the given display and section.
   */
  protected function getDefaultRegionFromDisplay(LayoutEntityDisplayInterface $display, $sectionDelta = 0) {
    return $display->getSection($sectionDelta)->getDefaultRegion();
  }

  /**
   * Create a new Layout Builder-enabled display from an existing display.
   *
   * @param \Drupal\node\Entity\NodeType $nodeType
   *   The content type for both the existing and new displays.
   * @param string $newDisplayId
   *   The machine name of the view mode for the new display.
   * @param string $existingDisplayId
   *   The machine name of the view mode for the existing display to be cloned
   *   from. Defaults to 'default'.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   *   The new display.
   */
  protected function createLayoutBuilderDisplayForNodeType(NodeType $nodeType, $newDisplayId, $existingDisplayId = 'default') {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $existingDisplay */
    $existingDisplay = $this->container->get('entity_display.repository')
      ->getViewDisplay(
        'node',
        $nodeType->id(),
        $existingDisplayId
      );
    $newDisplay = $existingDisplay->createCopy($newDisplayId);
    $newDisplay->enableLayoutBuilder()
      ->save();

    return $newDisplay;
  }

  /**
   * Get a Layout Builder SectionComponent from a entity display.
   *
   * @param \Drupal\layout_builder\Entity\LayoutEntityDisplayInterface $display
   *   The display to get the Layout Builder Component from.
   * @param int $sectionDelta
   *   The delta of the Layout Builder Section to get the Component from.
   * @param string $pluginId
   *   The plugin ID of the Layout Builder Component to get.
   *
   * @return null|\Drupal\layout_builder\SectionComponent
   *   The Layout Builder Section Component in the given display at the given
   *   delta, with the given qualifier; or NULL if one cannot be found.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Throws a Plugin exception if the Component's plugin ID cannot be found.
   */
  protected function getLayoutBuilderComponentFromDisplay(LayoutEntityDisplayInterface $display, $sectionDelta, $pluginId) {
    $section = $display->getSection($sectionDelta);

    $components = $section->getComponents();
    foreach ($components as $component) {
      if ($component->getPluginId() === $pluginId) {
        return $section->getComponent($component->getUuid());
      }
    }

    return NULL;
  }

}
