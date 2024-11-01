<?php

namespace Drupal\danse_log;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Logger\RfcLoggerTrait;
use Drupal\danse\PluginManager;
use Drupal\danse_log\Event\LogEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Logger service to write messages and exceptions to an external service.
 */
class Logger extends LoggerChannel {

  use RfcLoggerTrait;

  /**
   * The message's placeholders parser.
   *
   * @var \Drupal\Core\Logger\LogMessageParserInterface
   */
  protected LogMessageParserInterface $parser;

  /**
   * The DANSE plugin manager.
   *
   * @var \Drupal\danse\PluginManager
   */
  protected PluginManager $pluginManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructs a logger service.
   *
   * @param \Drupal\Core\Logger\LogMessageParserInterface $parser
   *   The message's placeholders parser.
   * @param \Drupal\danse\PluginManager $plugin_manager
   *   The DANSE plugin manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(LogMessageParserInterface $parser, PluginManager $plugin_manager, EventDispatcherInterface $event_dispatcher) {
    parent::__construct('danse');
    $this->parser = $parser;
    $this->pluginManager = $plugin_manager;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, $message, array $context = []): void {
    try {
      /**
       * @var \Drupal\danse_log\Plugin\Danse\Log $plugin
       */
      $plugin = $this->pluginManager->createInstance('log');
    }
    catch (PluginException $e) {
      // This happens during installation, when the new plugin will not be
      // known yet and then we can ignore.
      return;
    }
    unset($context['backtrace']);
    $context['placeholders'] = $this->parser->parseMessagePlaceholders($message, $context);
    $context['backtrace'] = explode("\n",
      $context['placeholders']['@backtrace_string'] ?? '');
    unset($context['placeholders']['@backtrace_string']);
    foreach ($context['placeholders'] as $key => $placeholder) {
      unset($context[$key]);
    }
    $payload = new Payload($level, $message, $context);

    $event = new LogEvent($payload);
    $this->eventDispatcher->dispatch($event, LogEvents::LOG);
    if ($event->isRelevant()) {
      try {
        $plugin->createLogEvent($context['channel'], $message, $payload);
      }
      catch (\Exception $e) {
        // We have to ignore any error here to avoid running in circles.
      }
    }
  }

}
