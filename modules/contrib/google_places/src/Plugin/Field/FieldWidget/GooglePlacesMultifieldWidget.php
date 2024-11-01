<?php

declare(strict_types=1);

namespace Drupal\google_places\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\BooleanCheckboxWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'google_places_multifield' field widget.
 *
 * @FieldWidget(
 *   id = "google_places_multifield_widget",
 *   label = @Translation("Google Places Multifield"),
 *   field_types = {"google_places_multifield"},
 * )
 */
final class GooglePlacesMultifieldWidget extends BooleanCheckboxWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'hidden' => TRUE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['hidden'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide the field completely.'),
      '#default_value' => $this->getSetting('hidden'),
      '#weight' => -1,
    ];
    return parent::settingsForm($form, $form_state) + $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'radios',
      '#default_value' => !empty($items[0]->value),
      '#options' => [
        TRUE => $this->t('Do not refetch'),
        FALSE => $this->t('Refetch'),
      ],
      '#title' => $this->t('Google Places Multifield'),
      '#description' => 'Uncheck if you want to refetch the Google Places data.',
    ];

    if ($this->getSetting('hidden')) {
      $element['value']['#access'] = FALSE;
    }
    return $element;
  }

}
