<?php

namespace Drupal\Tests\ui_patterns_settings\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_patterns\Definition\PatternDefinition;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\UiPatternsSettings;

/**
 * Test ComplexSettingType.
 *
 * @group ui_patterns_settings
 */
class ComplexSettingTypeTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ui_patterns_settings',
    'ui_patterns_settings_data_provider',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Mock required services here.
  }

  private function getTestSettingType() {
    /** @var \Drupal\ui_patterns_settings_data_provider\Plugin\UIPatterns\SettingType\SampleComplexSettingType $sample_complex_setting_type */
    $definition = new PatternDefinition([
      [
        'sample' => [
          'id' => 'sample',
        ],
      ],
    ], 'sample');
    return UiPatternsSettings::createSettingType($definition, $this->getTestSettingDefinition());
  }

  private function getTestSettingDefinition() {
    return new PatternDefinitionSetting('sample_complex_setting_type', [
      'name' => 'sample_complex_setting_type',
      'type' => 'sample_complex_setting_type',
      'label' => 'sample_complex_setting_type',
    ]);
  }

  /**
   * Test testSettingsForm.
   */
  public function testSettingsForm() {
    $sample_definition = $this->getTestSettingDefinition();
    $sample_complex_setting_type = $this->getTestSettingType();
    $fieldset = $sample_complex_setting_type->settingsForm([],  '', $sample_definition, 'layout');
    self::assertArrayHasKey('sample_complex_setting_type', $fieldset);
    self::assertArrayHasKey('provider', $fieldset['sample_complex_setting_type']);
    self::assertArrayHasKey('foo', $fieldset['sample_complex_setting_type']['configuration']);
    self::assertArrayHasKey('#type', $fieldset['sample_complex_setting_type']['configuration']['foo']['config']);
  }

  public function testPreprocess() {
    $sample_complex_setting_type = $this->getTestSettingType();
    $context = [];
    $ary = $sample_complex_setting_type->preprocess(['provider' => 'foo'], $context);
    self::assertArrayHasKey('data', $ary);
  }
}
