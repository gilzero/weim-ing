<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for setting types with data providers.
 */
abstract class ComplexSettingTypeBase extends PatternSettingTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\ui_patterns_settings\UiPatternsSettingsDataProviderManager
   */
  protected $dataProviderManger;

  /**
   * The data provider plugin.
   *
   * @var \Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderInterface
   */
  protected $provider;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->dataProviderManger = $container->get('plugin.manager.ui_patterns_settings_data_provider');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $def = $this->getPatternSettingDefinition();
    $data_provider_definitions = $this->dataProviderManger->getDefinitionsBySettingType($this->pluginId);
    $provider_plugins = [];
    $provider_options = [];
    foreach ($data_provider_definitions as $data_provider_definition) {
      $data_provider_id = $data_provider_definition['id'];
      $provider_plugins[$data_provider_id] = $this->dataProviderManger->createInstance($data_provider_id, []);
      $provider_options[$data_provider_id] = $data_provider_definition['label'];

    }
    $form[$def->getName()] = [
      '#type' => 'fieldset',
      '#title' => $def->getLabel(),
    ];

    $provider_select_id = $def->getName(). '_provider';
    $form[$def->getName()]['provider'] = [
      '#type' => 'select',
      '#title' => $this->t('Dataprovider'),
      '#default_value' => $this->getValue($value['provider'] ?? NULL),
      '#options' => $provider_options,
      '#attributes'=> ['id' => $provider_select_id]
    ];

    $form[$def->getName()]['configuration'] = [];

    foreach ($data_provider_definitions as $data_provider_definition) {
      $data_provider_id = $data_provider_definition['id'];
      $provider_settings_form = $provider_plugins[$data_provider_id]->settingsForm($value['configuration'][$data_provider_id]['config'] ?? []);
      if ($provider_settings_form) {
        $form[$def->getName()]['configuration'][$data_provider_id] = ['#type' => 'container',
          '#states' => [
            'visible' => [
              'select[id="' . $provider_select_id . '"]' => ['value' => $data_provider_id],
            ],
          ],
        ];
        $form[$def->getName()]['configuration'][$data_provider_id]['config'] = $provider_settings_form;
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess(
    $value,
    array $context,
    PatternDefinitionSetting $def
  ) {
    $provider_id = $value['provider'] ?? NULL;
    if ($provider_id) {
      $instance = $this->dataProviderManger->createInstance($provider_id, []);
      $this->provider = $instance;
      return $instance->getData($value['configuration'][$provider_id]['config'] ?? []);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    if ($this->provider) {
      $instance = $this->provider;
      $instance->alterElement($value, $def, $element);
    }
  }

}
