<?php

namespace Drupal\Tests\ui_patterns_settings\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ui_patterns\Traits\TwigDebugTrait;

/**
 * Test pattern preview rendering.
 *
 * @group ui_patterns_setting
 * @name ui_patterns_setting
 */
class UiPatternsSettingsRenderTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stable9';

  /**
   * Disable schema validation when running tests.
   *
   * @var bool
   *
   * @todo Fix this by providing actual schema validation.
   */
  protected $strictConfigSchema = FALSE;

  use TwigDebugTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_ds',
    'ui_patterns_library',
    'ui_patterns_layouts',
    'ui_patterns_settings',
    'field_ui',
    'token',
    'ds',
    'ui_patterns_settings_render_test',
  ];

  /**
   * Tests pattern preview suggestions.
   */
  public function testRender() {

    $assert_session = $this->assertSession();

    $this->drupalCreateContentType(['type' => 'article']);
    $created_node = $this->drupalCreateNode([
      'title' =>
      t('Hello Settings'),
      'type' => 'article',
    ]);
    $this->enableTwigDebugMode();

    $user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($user);

    // Define mapping for each setting type.
    $mappings = [
      '[textfield]' => ['input' => 'Text', 'result' => 'Textfield: Text'],
      '[number]' => ['input' => '10', 'result' => 'Number: 10'],
      '[token][input]' => ['input' => '[node:nid]', 'result' => 'Token: 1'],
      '[boolean]' => ['input' => '1', 'result' => 'Boolean: 1'],
      '[select]' => ['input' => 'key', 'result' => 'Select: key'],
      '[select_config]' => ['input' => 'key', 'result' => 'select_config: config key'],
      '[enumeration]' => ['input' => 'key', 'result' => 'Enumeration: key'],
      /*'[colorwidget][colorwidget]' => [
        'input' => 'key',
        'result' => 'Colorwidget: key',
      ],*/
      '[checkboxes][box1]' => ['input' => TRUE, 'result' => 'Checkboxes: Box1'],
      '[attributes]' => [
        'input' => 'class="class"',
        'result' => 'Attributes:  class="class"',
      ],
      '[group_sub]' => [
        'input' => 'group_sub',
        'result' => 'Group sub: group_sub',
      ],
      '[machine_name]' => ['input' => 'key', 'result' => 'Machine name: key'],
    ];

    // Select the layout.
    $edit = [
      'ds_layout' => 'pattern_foo_settings',
    ];
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $this->submitForm($edit, 'Save');

    // Fill settings.
    $edit = [];
    foreach ($mappings as $key => $mapping) {
      $edit['layout_configuration[pattern][settings]' . $key] = $mapping['input'];
    }
    $this->drupalGet('/admin/structure/types/manage/article/display');
    $this->submitForm($edit, 'Save');

    // Check values.
    $this->drupalGet('/node/' . $created_node->id());
    foreach ($mappings as $key => $mapping) {
      $assert_session->responseContains($mapping['result']);
    }
    $assert_session->responseContains('Configuration: config_value');
  }

}
