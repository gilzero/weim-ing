<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Role Checkboxes setting type.
 *
 * Provides an array of:
 * - current_role_selected: True if the current role is part of the selection
 *   or nothing is selected
 * - current_role: The current role.
 * - selected: Array of selected roles.
 *
 * @UiPatternsSettingType(
 *   id = "role_checkboxes",
 *   label = @Translation("Role checkboxes")
 * )
 */
class RoleCheckboxesSettingTypeBase extends EnumerationSettingTypeBase {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  protected function emptyOption() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getValue($value) {
    if ($value === NULL) {
      return !is_array($this->getPatternSettingDefinition()
        ->getDefaultValue()) ? [
        $this->getPatternSettingDefinition()
          ->getDefaultValue(),
      ] : $this->getPatternSettingDefinition()->getDefaultValue();
    }
    else {
      return $value ?? "";
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getOptions(PatternDefinitionSetting $def) {
    $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
    $options = [];
    foreach ($roles as $role) {
      $options[$role->id()] = $role->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEnumerationType(PatternDefinitionSetting $def) {
    return $def->getValue('enumeration_type') ?? 'checkboxes';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    $selected_options = [];
    $defined_options = $this->getOptions($def);

    if (is_array($value)) {
      foreach ($value as $checkbox_key => $checkbox_value) {
        if ($checkbox_value != "0") {
          $selected_options[$checkbox_key] = $defined_options[$checkbox_key] ?? $checkbox_value;
        }
      }
    }

    $current_user_roles = $this->currentUser->getRoles();
    $selected_options_keys = array_keys($selected_options);
    $settings = [
      'current_role_selected' => count($selected_options) === 0 || !empty(array_intersect($current_user_roles, $selected_options_keys)),
      'selected' => $selected_options,
    ];
    foreach ($current_user_roles as $current_role_id) {
      $settings['current_role'][$current_role_id] = $defined_options[$current_role_id];
    }
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

}
