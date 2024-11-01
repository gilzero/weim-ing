<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Handler;

use Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Handler that uses one handler or another depending on a condition.
 */
class ConditionalHandler extends AbstractProcessingHandler {

  /**
   * ConditionalHandler constructor.
   *
   * @param \Monolog\Handler\AbstractProcessingHandler $first
   *   The handler to use when the condition resolver returns TRUE.
   * @param \Monolog\Handler\AbstractProcessingHandler $second
   *   The handler to use when the condition resolver returns FALSE.
   * @param \Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface $conditionResolver
   *   The condition resolver.
   * @param int|string|\Monolog\Level $level
   *   The minimum logging level at which this handler will be triggered.
   * @param bool $bubble
   *   Whether the messages that are handled can bubble up the stack or not.
   */
  public function __construct(
    private readonly AbstractProcessingHandler $first,
    private readonly AbstractProcessingHandler $second,
    private readonly ConditionResolverInterface $conditionResolver,
    int|string|Level $level = Level::Debug,
    bool $bubble = TRUE,
  ) {
    parent::__construct($level, $bubble);
  }

  /**
   * {@inheritdoc}
   */
  public function write(LogRecord $record): void {
    if ($this->conditionResolver->resolve()) {
      $this->first->write($record);
    }
    else {
      $this->second->write($record);
    }
  }

}
