<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\ui_patterns_settings\Plugin\RoleCheckboxesSettingTypeBase;

/**
 * Role Checkboxes setting type.
 *
 * Provides an array of:
 * - current_role_selected: True if the
 *   current role is part of the selection
 *   or nothing is selected
 * - current_role: The current role.
 * - selected: Array of selected role.
 *
 * @UiPatternsSettingType(
 *   id = "role_checkboxes",
 *   label = @Translation("Role checkboxes")
 * )
 */
class RoleCheckboxesSettingType extends RoleCheckboxesSettingTypeBase {

}
