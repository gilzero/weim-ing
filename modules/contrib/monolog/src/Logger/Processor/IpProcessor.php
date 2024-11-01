<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Processor that adds IP information to the log records.
 */
class IpProcessor extends AbstractRequestProcessor implements ProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function __invoke(LogRecord $record): LogRecord {
    $request = $this->getRequest();

    if ($request != NULL) {
      $record->extra = \array_merge(
        $record->extra,
        [
          'ip' => $request->getClientIp(),
        ],
      );
    }

    return $record;
  }

}
