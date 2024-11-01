<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger;

use Monolog\Logger as BaseLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Extends the Monolog logger to add the ability to collect logs for debug.
 *
 * @phpstan-ignore-next-line
 */
class Logger extends BaseLogger implements DebugLoggerInterface, ResetInterface {

  /**
   * {@inheritdoc}
   */
  public function getLogs(Request $request = NULL): array {
    return $this->getDebugLogger()?->getLogs($request) ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function countErrors(Request $request = NULL): int {
    return $this->getDebugLogger()?->countErrors($request) ?? 0;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): void {
    $this->getDebugLogger()?->clear();
  }

  /**
   * Return the processor or handler that can debug logger, if any.
   *
   * @return \Symfony\Component\HttpKernel\Log\DebugLoggerInterface|null
   *   The debug logger or null.
   */
  private function getDebugLogger(): ?DebugLoggerInterface {
    foreach ($this->processors as $processor) {
      if ($processor instanceof DebugLoggerInterface) {
        return $processor;
      }
    }

    foreach ($this->handlers as $handler) {
      if ($handler instanceof DebugLoggerInterface) {
        return $handler;
      }
    }

    return NULL;
  }

}
