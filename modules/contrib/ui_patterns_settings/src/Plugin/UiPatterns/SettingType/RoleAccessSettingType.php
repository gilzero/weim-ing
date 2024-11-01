<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Plugin\RoleCheckboxesSettingTypeBase;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;

/**
 * Hides render element for unchecked roles.
 *
 * Setting type to hide the render element if:
 * Elements are checked and the current role is
 * not part of the selection.
 *
 * @UiPatternsSettingType(
 *   id = "role_access",
 *   label = @Translation("Role Access")
 * )
 */
class RoleAccessSettingType extends RoleCheckboxesSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    if ($this->isLayoutBuilderRoute() === FALSE && $value['current_role_selected'] === FALSE) {
      hide($element);
    }
  }

}
