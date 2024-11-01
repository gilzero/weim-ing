<?php

namespace Drupal\unstructured\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The rules for a string_long field.
 */
#[AiAutomatorType(
  id: 'unstructured_io_string_long',
  label: new TranslatableMarkup('Unstructured API: File to Text'),
  field_rule: 'string_long',
  target: '',
)]
class FileToString extends FileToTextBase {

  /**
   * {@inheritDoc}
   */
  public $title = 'Unstructured API: File to Text';

}
