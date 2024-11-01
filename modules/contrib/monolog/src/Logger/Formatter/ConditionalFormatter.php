<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Formatter;

use Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * Formatter that uses one formatter or another depending on a condition.
 */
class ConditionalFormatter implements FormatterInterface {

  /**
   * ConditionalFormatter constructor.
   *
   * @param \Monolog\Formatter\FormatterInterface $first
   *   The formatter to use when the condition resolver returns TRUE.
   * @param \Monolog\Formatter\FormatterInterface $second
   *   The formatter to use when the condition resolver returns FALSE.
   * @param \Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface $conditionResolver
   *   The condition resolver.
   */
  public function __construct(
    private readonly FormatterInterface $first,
    private readonly FormatterInterface $second,
    private readonly ConditionResolverInterface $conditionResolver,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function format(LogRecord $record) {
    if ($this->conditionResolver->resolve()) {
      return $this->first->format($record);
    }
    else {
      return $this->second->format($record);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatBatch(array $records) {
    if ($this->conditionResolver->resolve()) {
      return $this->first->formatBatch($records);
    }
    else {
      return $this->second->formatBatch($records);
    }
  }

}
