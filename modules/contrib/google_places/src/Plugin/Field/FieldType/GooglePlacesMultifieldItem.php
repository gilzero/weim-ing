<?php

declare(strict_types=1);

namespace Drupal\google_places\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'google_places_multifield' field type.
 *
 * @FieldType(
 *   id = "google_places_multifield",
 *   label = @Translation("Google Places Multifield"),
 *   description = @Translation("An AI Automator Field, this doesn't do anything by itself."),
 *   default_formatter = "google_places_multifield_formatter",
 *   default_widget = "google_places_multifield_widget",
 *   category = "ai_automator_multi",
 * )
 */
final class GooglePlacesMultifieldItem extends BooleanItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }
}
