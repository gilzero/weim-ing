<?php

namespace Drupal\raven\Tracing;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Event\StatementExecutionEndEvent;
use Drupal\Core\Database\Event\StatementExecutionFailureEvent;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;

/**
 * Provides helper methods for performance tracing.
 */
trait TracingTrait {

  /**
   * The transaction for this request or command.
   *
   * @var \Sentry\Tracing\Transaction|null
   */
  protected $transaction;

  /**
   * Whether or not query arguments should be included in traces.
   */
  protected static bool $queryArgs = FALSE;

  /**
   * Starts a transaction, and optionally database tracing.
   */
  protected function startTransaction(TransactionContext $transactionContext): void {
    $config = $this->configFactory->get('raven.settings');
    $transactionContext->setStartTimestamp($this->time->getRequestMicroTime());
    $transaction = \Sentry\startTransaction($transactionContext);
    SentrySdk::getCurrentHub()->setSpan($transaction);
    // If this transaction is not sampled, we can stop here.
    if (!$transaction->getSampled()) {
      return;
    }
    $this->transaction = $transaction;
    if ($config->get('database_tracing')) {
      static::$queryArgs = (bool) $config->get('database_tracing_args');
      foreach (Database::getAllConnectionInfo() as $key => $info) {
        Database::startLog('raven', $key);
      }
      $this->eventDispatcher->addListener(StatementExecutionEndEvent::class, [
        static::class,
        'onStatementExecutionEnd',
      ]);
      // @phpstan-ignore-next-line Class does not yet exist in 10.2.x branch.
      $this->eventDispatcher->addListener(StatementExecutionFailureEvent::class, [
        static::class,
        'onStatementExecutionEnd',
      ]);
    }
  }

  /**
   * Create a span for each database statement.
   */
  public static function onStatementExecutionEnd(StatementExecutionEndEvent $event): void {
    $parent = SentrySdk::getCurrentHub()->getSpan();
    if (!$parent) {
      return;
    }
    $data = [];
    if (static::$queryArgs) {
      $data['db.sql.bindings'] = $event->args;
    }
    if (isset($event->caller['file'])) {
      $data['code.filepath'] = $event->caller['file'];
      $data['code.function'] = $event->caller['function'];
      $data['code.lineno'] = $event->caller['line'];
    }
    if ($databaseInfo = Database::getConnectionInfo($event->key)) {
      $connectionInfo = $databaseInfo[$event->target];
      $data['db.system'] = $connectionInfo['driver'];
      $data['db.name'] = $connectionInfo['database'];
      if (isset($connectionInfo['host'])) {
        $data['server.address'] = $connectionInfo['host'];
      }
      if (isset($connectionInfo['port'])) {
        $data['server.port'] = $connectionInfo['port'];
      }
    }
    // @phpstan-ignore-next-line Class does not yet exist in 10.2.x branch.
    if ($event instanceof StatementExecutionFailureEvent) {
      // @phpstan-ignore-next-line Class does not yet exist in 10.2.x branch.
      $data['exception.message'] = $event->exceptionMessage;
      // @phpstan-ignore-next-line Class does not yet exist in 10.2.x branch.
      $data['exception.type'] = $event->exceptionClass;
    }
    $context = SpanContext::make()
      ->setOrigin('auto.db')
      ->setOp('db.sql.query')
      ->setDescription($event->queryString)
      ->setTags([
        'db.key' => $event->key,
        'db.target' => $event->target,
      ])
      ->setData($data)
      ->setStartTimestamp($event->startTime)
      ->setEndTimestamp($event->time);
    $parent->startChild($context);
  }

}
