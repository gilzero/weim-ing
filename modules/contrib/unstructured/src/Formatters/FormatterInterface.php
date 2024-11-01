<?php

namespace Drupal\unstructured\Formatters;

/**
 * Interface for formatters.
 */
interface FormatterInterface {

  /**
   * Formats in the given format.
   *
   * @param array $results
   *   The results to use.
   * @param string $split
   *   The split to use.
   *
   * @return array
   *   The formatted text in an array.
   */
  public function format(array $results, $split): array;

}
