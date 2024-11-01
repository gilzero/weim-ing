<?php

namespace Drupal\Tests\module_builder\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the component edit form.
 *
 * This uses a test component entity which gets its data definition from a
 * custom Generate class in order to get a standardised set of form elements.
 *
 * For manual debugging, enable the module_builder_test_component_type module
 * and go to admin/config/development/test_component. Note that menu tasks and
 * actions are not defined, so manual manipulation of URLs will be needed to
 * create a component.
 *
 * @see \Drupal\module_builder_test_component_type\TestGenerateTask
 *
 * @group module_builder
 */
class ComponentFormTest extends BrowserTestBase {

  /**
   * Disable strict config schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'block',
    'module_builder',
    'module_builder_test_component_type',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Enable error output to the browser.
    $settings = [];
    $settings['config']['system.logging']['error_level'] = (object) [
      'value' => ERROR_REPORTING_DISPLAY_VERBOSE,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);

    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');

    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->testComponentStorage = $this->entityTypeManager->getStorage('test_component');

    // Add permissions to the anonymous role so we don't need to log in.
    user_role_change_permissions(RoleInterface::ANONYMOUS_ID, [
      'create modules' => TRUE,
    ]);

    $component_entity = $this->testComponentStorage->create([
      'id' => 'my_component',
      'name' => 'My Component',
    ]);
    $component_entity->save();
  }

  /**
   * Tests the handling of property default values.
   */
  public function testPropertyDefaults() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Get to the misc tab where our stuff is.
    $this->drupalGet('/admin/config/development/test_component/manage/my_component/misc');

    // Check all the elements have their default values.
    $web_assert->fieldValueEquals('module[string_empty]', '');
    $web_assert->fieldValueEquals('module[string_default]', 'default value');
    $web_assert->fieldValueEquals('module[checkbox_empty]', FALSE);
    $web_assert->fieldValueEquals('module[checkbox_default]', TRUE);
    $web_assert->fieldValueEquals('module[array_empty]', '');
    $web_assert->fieldValueEquals('module[array_default]', "value 1\nvalue 2");

    // Submit the form with no changes: defaults should remain the same.
    $page->pressButton('Save');

    $web_assert->fieldValueEquals('module[string_empty]', '');
    $web_assert->fieldValueEquals('module[string_default]', 'default value');
    $web_assert->fieldValueEquals('module[checkbox_empty]', FALSE);
    $web_assert->fieldValueEquals('module[checkbox_default]', TRUE);
    $web_assert->fieldValueEquals('module[array_empty]', '');
    $web_assert->fieldValueEquals('module[array_default]', "value 1\nvalue 2");

    $component = $this->testComponentStorage->load('my_component');

    // The component has the values from the defaults.
    $this->assertEquals('default value', $component->data['string_default']);
    $this->assertEquals(TRUE, $component->data['checkbox_default']);
    $this->assertEquals([
      0 => "value 1",
      1 => "value 2",
    ], $component->data['array_default']);
    // Empty properties stayed empty.
    $this->assertEquals('', $component->data['string_empty']);
    $this->assertEquals(FALSE, $component->data['checkbox_empty']);
    $this->assertEquals([], $component->data['array_empty']);

    // Submit the form with empty values for everything: defaults should be
    // zapped.
    $page->fillField('module[string_empty]', '');
    $page->fillField('module[string_default]', '');
    $page->uncheckField('module[checkbox_empty]');
    $page->uncheckField('module[checkbox_default]');
    $page->fillField('module[array_empty]', '');
    $page->fillField('module[array_default]', '');
    $page->pressButton('Save');

    // The updated form shows the correct values.
    $web_assert->fieldValueEquals('module[string_empty]', '');
    $web_assert->fieldValueEquals('module[string_default]', '');
    $web_assert->fieldValueEquals('module[checkbox_empty]', FALSE);
    $web_assert->fieldValueEquals('module[checkbox_default]', FALSE);
    $web_assert->fieldValueEquals('module[array_empty]', '');
    $web_assert->fieldValueEquals('module[array_default]', "");

    // Aaaaaaaaaaaaaargh.
    // Having to reach directly into config storage here, because doing:
    // $component = $this->testComponentStorage->load('my_component');
    // gets a stale version of the entity. WHY???
    // Extra weirdness: if we load the edit form first, then the entity load
    // works ok:
    // $this->drupalGet('/admin/config/development/test_component/manage/my_component/misc');
    // $component = $this->testComponentStorage->load('my_component');
    // WTF?
    $configs = $this->container->get('config.factory')->loadMultiple(['module_builder_test_component_type.test_component.my_component']);
    $config = reset($configs);
    $read = $config->getStorage()->read('module_builder_test_component_type.test_component.my_component');
    // $component = new \Drupal\module_builder_test_component_type\Entity\($read, $this->entityTypeId);
    $entity_class = $this->entityTypeManager->getDefinition('test_component')->getClass();
    $component = new $entity_class($read, 'test_component');

    // The component's values are now all empty.
    $this->assertEquals('', $component->data['string_empty']);
    $this->assertEquals('', $component->data['string_default']);
    $this->assertEquals(FALSE, $component->data['checkbox_empty']);
    $this->assertEquals(FALSE, $component->data['checkbox_default']);
    $this->assertEquals([], $component->data['array_empty']);
    $this->assertEquals([], $component->data['array_default']);
  }

  /**
   * Tests the handling of compound properties.
   */
  public function testCompoundProperties() {
    $web_assert = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('/admin/config/development/test_component/manage/my_component/misc');

    $page->pressButton('Add a Compound empty item');
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][0][one]']);
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][0][two]']);

    $page->pressButton('Add another Compound empty item');
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][1][one]']);
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][1][two]']);

    $page->pressButton('Remove last item');
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][0][one]']);
    $web_assert->elementExists('named_exact', ['id_or_name', 'module[compound_empty][0][two]']);
    $web_assert->elementNotExists('named_exact', ['id_or_name', 'module[compound_empty][1][one]']);
    $web_assert->elementNotExists('named_exact', ['id_or_name', 'module[compound_empty][1][two]']);

    // Fill in the one compound item and submit the form.
    $page->fillField('module[compound_empty][0][one]', 'value one');
    $page->fillField('module[compound_empty][0][two]', 'value two');
    $page->pressButton('Save');

    $this->testComponentStorage->resetCache();
    $component = $this->testComponentStorage->load('my_component');

    $this->assertEquals('value one', $component->data['compound_empty'][0]['one']);
    $this->assertEquals('value two', $component->data['compound_empty'][0]['two']);
    $this->assertArrayNotHasKey(1, $component->data['compound_empty']);

    // Remove the item we saved, and save again: it should be removed from the
    // module.
    $page->pressButton('Remove last item');
    $page->pressButton('Save');

    // ARRRRRGH. See same problem above.
    $configs = $this->container->get('config.factory')->loadMultiple(['module_builder_test_component_type.test_component.my_component']);
    $config = reset($configs);
    $read = $config->getStorage()->read('module_builder_test_component_type.test_component.my_component');
    // $component = new \Drupal\module_builder_test_component_type\Entity\($read, $this->entityTypeId);
    $entity_class = $this->entityTypeManager->getDefinition('test_component')->getClass();
    $component = new $entity_class($read, 'test_component');

    $this->assertArrayNotHasKey('compound_empty', $component->data);
  }

  // TODO: test mutable data.

}
