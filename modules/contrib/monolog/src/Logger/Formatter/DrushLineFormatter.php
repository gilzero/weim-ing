<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Formatter;

use Monolog\Formatter\LineFormatter;

/**
 * Formatter suitable to be using with Drush logs.
 */
class DrushLineFormatter extends LineFormatter {

  /**
   * {@inheritdoc}
   */
  protected function convertToString($data): string {
    if (NULL === $data || \is_bool($data)) {
      return \var_export($data, TRUE);
    }

    if (\is_scalar($data)) {
      return (string) $data;
    }

    $result = "";
    \array_walk($data, static function ($val, $key) use (&$result): void {
      if ($val != "" && \is_scalar($val)) {
        $result .= " | $key=$val";
      }
    });

    return \ltrim($result);
  }

}
