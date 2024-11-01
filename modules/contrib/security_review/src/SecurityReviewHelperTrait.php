<?php

declare(strict_types=1);

namespace Drupal\security_review;

use Drupal\Core\Language\LanguageInterface;

/**
 * Helper methods for the Security Review module.
 *
 * @ingroup security-review
 */
trait SecurityReviewHelperTrait {

  /**
   * Generates a machine name from a string.
   *
   * @param string $string
   *   String to turn into machine name.
   *
   * @return string
   *   Machine name string.
   */
  public function getMachineName(string $string): string {
    $transliterated = \Drupal::transliteration()->transliterate($string, LanguageInterface::LANGCODE_DEFAULT, '_');
    $transliterated = mb_strtolower($transliterated);

    return preg_replace('@[^a-z0-9_.]+@', '_', $transliterated);
  }

}
