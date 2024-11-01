<?php

declare(strict_types=1);

namespace Drupal\google_places\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;

/**
 * Plugin implementation of the 'Google Places Multifield' formatter.
 *
 * @FieldFormatter(
 *   id = "google_places_multifield_formatter",
 *   label = @Translation("Google Places Multifield"),
 *   field_types = {
 *     "google_places_multifield"
 *   }
 * )
 */
final class GooglePlacesMultifieldFormatter extends BooleanFormatter {

}
