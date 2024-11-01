<?php

namespace Drupal\ebt_core\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ebt_core\Plugin\Field\FieldWidget\EbtSettingsDefaultWidget;

/**
 * Plugin implementation of the 'ebt_settings_simple' widget.
 *
 * @FieldWidget(
 *   id = "ebt_settings_simple",
 *   label = @Translation("EBT Simple settings"),
 *   field_types = {
 *     "ebt_settings"
 *   }
 * )
 */
class EbtSettingsSimpleWidget extends EbtSettingsDefaultWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    unset($element['ebt_settings']['design_options']['box1']);
    unset($element['ebt_settings']['design_options']['other_settings']['border_color']);
    unset($element['ebt_settings']['design_options']['other_settings']['border_style']);
    unset($element['ebt_settings']['design_options']['other_settings']['border_radius']);
    unset($element['ebt_settings']['design_options']['other_settings']['background_color']);
    unset($element['ebt_settings']['design_options']['other_settings']['background_media']);
    unset($element['ebt_settings']['design_options']['other_settings']['background_image_style']);
    unset($element['ept_settings']['design_options']['other_settings']['background_video_settings']);

    $element['ebt_settings']['design_options']['#open'] = TRUE;
    $element['ebt_settings']['design_options']['other_settings']['spacing'] = [
      '#type' => 'select',
      '#title' => $this->t('Spacing'),
      '#options' => [
        'spacing-none' => $this->t('None'),
        'spacing-sm' => $this->t('Small'),
        'spacing-md' => $this->t('Medium'),
        'spacing-lg' => $this->t('Large'),
        'spacing-xl' => $this->t('Extra Large'),
        'spacing-xxl' => $this->t('Double Extra Large'),
      ],
      '#default_value' => $items[$delta]->ebt_settings['design_options']['other_settings']['spacing'] ?? 'spacing-none',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$value) {
      $value += ['ebt_settings' => []];
    }

    return $values;
  }

}
