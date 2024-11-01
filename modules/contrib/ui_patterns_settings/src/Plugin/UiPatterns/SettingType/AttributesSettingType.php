<?php

namespace Drupal\ui_patterns_settings\Plugin\UIPatterns\SettingType;

use Drupal\Component\Utility\Html;
use Drupal\Core\Template\Attribute;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingTypeBase;

/**
 * Attributes setting type.
 *
 * @UiPatternsSettingType(
 *   id = "attributes",
 *   label = @Translation("Attributes")
 * )
 */
class AttributesSettingType extends PatternSettingTypeBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $value = $this->getValue($value);
    $description = $def->getDescription() != NULL ? $def->getDescription() : $this->t('E.g. role="navigation" class="class-1"');
    $form[$def->getName()] = [
      '#type' => 'textfield',
      '#title' => $def->getLabel(),
      '#description' => $description,
      '#default_value' => $value,
    ];
    $this->handleInput($form[$def->getName()], $def, $form_type);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess($value, array $context) {
    $value = parent::preprocess($value, $context);
    $parse_html = '<div ' . $value . '></div>';
    $attributes = [];
    foreach (Html::load($parse_html)->getElementsByTagName('div') as $div) {
      foreach ($div->attributes as $attr) {
        $attributes[$attr->nodeName] = $attr->nodeValue;
      }
    }
    return new Attribute($attributes);
  }

}
