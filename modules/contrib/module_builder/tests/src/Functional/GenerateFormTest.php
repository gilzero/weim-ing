<?php

namespace Drupal\Tests\module_builder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the generate code form.
 *
 * @group module_builder
 */
class GenerateFormTest extends BrowserTestBase {

  /**
   * Disable strict config schema checking.
   *
   * This is needed because the 'data' property on module entities can't have
   * a defined schema.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'module_builder',
    // Can't enable here because of https://www.drupal.org/project/drupal/issues/3190255.
    // 'test_dummy_module_write_location',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container->get('module_installer')->install(['test_dummy_module_write_location']);
  }

  /**
   * Tests writing module files.
   *
   * Note that warnings about existing files won't work because
   * ModuleFileWriterDummyLocation writes to a different location that the form
   * doesn't know about.
   */
  public function testMyTest() {
    $page = $this->getSession()->getPage();

    $account = $this->createUser(['create modules']);
    $this->drupalLogin($account);

    $module = $this->container->get('entity_type.manager')->getStorage('module_builder_module')->create([
      'id' => 'my_module',
      'name' => 'My Module',
      'data' => [],
    ]);
    $module->save();

    $this->drupalGet('admin/config/development/module_builder/manage/my_module/generate');

    $page->pressButton('Write all files');

    $site_path = \Drupal::service('site.path');
    $this->assertFileExists($site_path . '/my_module/my_module.info.yml');

    $this->assertSession()->pageTextMatches('@Written 1 files to folder sites/simpletest/\d+/my_module@');

    $module->data = [
      'hooks' => [
        'hook_help',
        'hook_install',
      ],
    ];
    $module->save();

    $this->drupalGet('admin/config/development/module_builder/manage/my_module/generate');

    $page->checkField('filename_list[my_module.module]');
    $page->pressButton('Write selected files');

    $this->assertFileExists($site_path . '/my_module/my_module.module');
    $this->assertFileDoesNotExist($site_path . '/my_module/my_module.install');
    $this->assertSession()->pageTextMatches('@Written 1 files to folder sites/simpletest/\d+/my_module@');

    // Put some junk in the existing files, so we can check that 'Write new files'
    // doesn't clobber them.
    file_put_contents($site_path . '/my_module/my_module.info.yml', 'CAKE');
    file_put_contents($site_path . '/my_module/my_module.module', 'CAKE');

    $page->pressButton('Write new files');
    $this->assertSession()->pageTextMatches('@Written 1 files to folder sites/simpletest/\d+/my_module@');

    $this->assertFileExists($site_path . '/my_module/my_module.install');
    $this->assertEquals('CAKE', file_get_contents($site_path . '/my_module/my_module.info.yml'));
    $this->assertEquals('CAKE', file_get_contents($site_path . '/my_module/my_module.module'));
  }

}
