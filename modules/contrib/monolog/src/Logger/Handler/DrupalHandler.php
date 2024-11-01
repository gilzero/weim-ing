<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger\Handler;

use Drupal\Core\Logger\RfcLogLevel;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Psr\Log\LoggerInterface;

/**
 * Forwards logs to a Drupal logger.
 */
class DrupalHandler extends AbstractProcessingHandler {

  /**
   * The wrapped Drupal logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a Default object.
   *
   * @param \Psr\Log\LoggerInterface $wrapped
   *   The wrapped Drupal logger.
   * @param int|string|\Monolog\Level $level
   *   The minimum logging level at which this handler will be triggered.
   * @param bool $bubble
   *   Whether the messages that are handled can bubble up the stack or not.
   */
  public function __construct(
    LoggerInterface $wrapped,
    int|string|Level $level = Level::Debug,
    bool $bubble = TRUE,
  ) {
    parent::__construct($level, $bubble);

    $this->logger = $wrapped;
  }

  /**
   * {@inheritdoc}
   */
  public function write(LogRecord $record): void {
    // Set up context with the data Drupal loggers expect.
    // @see Drupal\Core\Logger\LoggerChannel::log()
    $extra = $record->extra;
    $context = $record->context + [
      'uid' => $extra['uid'] ?? 0,
      'channel' => $record->channel,
      'link' => '',
      'request_uri' => $extra['request_uri'] ?? '',
      'referer' => $extra['referer'] ?? '',
      'ip' => $extra['ip'] ?? 0,
      'timestamp' => $record->datetime->format('U'),
    ];

    $this->logger->log(
      $this->fromMonologToRfc5424($record->level),
      $record->message,
      $context,
    );
  }

  /**
   * Convert a Monolog log level to a Drupal log level.
   *
   * @param \Monolog\Level $level
   *   The Monolog log level.
   *
   * @return int
   *   The Drupal log level.
   */
  private function fromMonologToRfc5424(Level $level): int {
    return match ($level) {
      Level::Emergency => RfcLogLevel::EMERGENCY,
      Level::Alert => RfcLogLevel::ALERT,
      Level::Critical => RfcLogLevel::CRITICAL,
      Level::Error => RfcLogLevel::ERROR,
      Level::Warning => RfcLogLevel::WARNING,
      Level::Notice => RfcLogLevel::NOTICE,
      Level::Info => RfcLogLevel::INFO,
      default => RfcLogLevel::DEBUG,
    };
  }

}
