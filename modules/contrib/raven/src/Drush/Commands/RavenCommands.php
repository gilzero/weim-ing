<?php

namespace Drupal\raven\Drush\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\raven\Logger\RavenInterface;
use Drupal\raven\Tracing\TracingTrait;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Psr\Container\ContainerInterface as DrushContainer;
use Sentry\SentrySdk;
use Sentry\Severity;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\TransactionContext;
use Sentry\Tracing\TransactionSource;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides Drush commands for Raven module.
 */
class RavenCommands extends DrushCommands {

  use TracingTrait;

  /**
   * {@inheritdoc}
   */
  final public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EventDispatcherInterface $drushEventDispatcher,
    protected EventDispatcherInterface $eventDispatcher,
    protected RavenInterface $ravenLogger,
    protected TimeInterface $time,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, ?DrushContainer $drush = NULL): static {
    return new static(
      $container->get('config.factory'),
      $drush ? $drush->get('eventDispatcher') : Drush::service('eventDispatcher'),
      $container->get('event_dispatcher'),
      $container->get('logger.raven'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Sets up Drush error handling and performance tracing.
   *
   * @hook pre-command *
   */
  public function preCommand(CommandData $commandData): void {
    if (!$this->ravenLogger->getClient()) {
      return;
    }
    $config = $this->configFactory->get('raven.settings');
    // Add Drush console error event listener.
    if ($config->get('drush_error_handler')) {
      $this->drushEventDispatcher->addListener(ConsoleEvents::ERROR, [
        $this,
        'onConsoleError',
      ]);
    }
    if (!$config->get('drush_tracing')) {
      return;
    }
    $this->drushEventDispatcher->addListener(ConsoleEvents::TERMINATE, [
      $this,
      'onConsoleTerminate',
    ], -222);
    $transactionContext = TransactionContext::make()
      ->setName('drush ' . $commandData->input()->getArgument('command'))
      ->setSource(TransactionSource::task())
      ->setOrigin('auto.console')
      ->setOp('console.command');
    $this->startTransaction($transactionContext);
  }

  /**
   * Console error event listener.
   */
  public function onConsoleError(ConsoleErrorEvent $event): void {
    \Sentry\captureException($event->getError());
  }

  /**
   * Console terminate event listener.
   */
  public function onConsoleTerminate(ConsoleTerminateEvent $event): void {
    if (!$this->transaction) {
      return;
    }
    $this->transaction->setTags(['drush.command.exit_code' => (string) $event->getExitCode()])
      ->finish();
  }

  /**
   * Send a test message to Sentry.
   *
   * Because messages are sent to Sentry asynchronously, there is no guarantee
   * that the message was actually delivered successfully.
   *
   * @param string $message
   *   The message text.
   * @param mixed[] $options
   *   An associative array of options.
   *
   * @option level
   *   The message level (debug, info, warning, error, fatal).
   *
   * @command raven:captureMessage
   * @usage drush raven:captureMessage
   *   Send test message to Sentry.
   * @usage drush raven:captureMessage --level=error
   *   Send error message to Sentry.
   * @usage drush raven:captureMessage 'Mic check.'
   *   Send "Mic check" message to Sentry.
   */
  public function captureMessage(
    string $message = 'Test message from Drush.',
    array $options = [
      'level' => 'info',
    ],
  ): void {
    $logger = $this->logger();
    // Force invalid configuration to throw an exception.
    $client = $this->ravenLogger->getClient(FALSE, TRUE);
    if (!$client) {
      throw new \Exception('Sentry client not available.');
    }
    elseif (!$client->getOptions()->getDsn() && $logger) {
      $logger->warning(dt('Sentry client key is not configured. No events will be sent to Sentry.'));
    }

    if (!is_string($options['level'])) {
      throw new \InvalidArgumentException('Level must be a string.');
    }
    $severity = new Severity($options['level']);

    $start = microtime(TRUE);

    $id = \Sentry\captureMessage($message, $severity);

    $parent = SentrySdk::getCurrentHub()->getSpan();
    if ($parent && $parent->getSampled()) {
      $span = SpanContext::make()
        ->setOrigin('auto.console')
        ->setOp('sentry.capture')
        ->setDescription("$severity: $message")
        ->setStartTimestamp($start)
        ->setEndTimestamp(microtime(TRUE));
      $parent->startChild($span);
    }

    if (!$id) {
      throw new \Exception('Send failed.');
    }
    if ($logger) {
      $logger->success(dt('Message sent as event %id.', ['%id' => $id]));
    }
  }

}
