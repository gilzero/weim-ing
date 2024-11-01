<?php

namespace Drupal\ui_patterns_settings\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines ui_patterns_settings_data_provider annotation object.
 *
 * @Annotation
 */
class UiPatternsSettingDataProvider extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The setting type plugin.
   *
   * @var string
   */
  public $settingType;
}
