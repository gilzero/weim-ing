<?php

declare(strict_types=1);

namespace Drupal\monolog\Logger;

use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Adapt Psr\Log\LoggerInterface to Drupal\Core\Logger\LoggerChannelInterface.
 */
class LoggerInterfacesAdapter implements LoggerChannelInterface {

  /**
   * The adapted LoggerInterface logger.
   *
   * A Psr\Log\LoggerInterface logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * Adapter constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The LoggerInterface logger to adapt.
   */
  public function __construct(LoggerInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * Return the adapted LoggerInterface logger.
   *
   * @return \Psr\Log\LoggerInterface
   *   The adapted LoggerInterface logger.
   */
  public function getAdaptedLogger(): LoggerInterface {
    return $this->logger;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestStack(RequestStack $requestStack = NULL) {
    // Do nothing, use a handler for this.
  }

  /**
   * {@inheritdoc}
   */
  public function setCurrentUser(AccountInterface $current_user = NULL) {
    // Do nothing, use a handler for this.
  }

  /**
   * {@inheritdoc}
   */
  public function setLoggers(array $loggers) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function addLogger(LoggerInterface $logger, $priority = 0) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function emergency(\Stringable|string $message, array $context = []): void {
    $this->logger->emergency($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function alert(\Stringable|string $message, array $context = []): void {
    $this->logger->alert($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function critical(\Stringable|string $message, array $context = []): void {
    $this->logger->critical($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function error(\Stringable|string $message, array $context = []): void {
    $this->logger->error($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function warning(\Stringable|string $message, array $context = []): void {
    $this->logger->warning($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function notice(\Stringable|string $message, array $context = []): void {
    $this->logger->notice($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function info(\Stringable|string $message, array $context = []): void {
    $this->logger->info($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function debug(\Stringable|string $message, array $context = []): void {
    $this->logger->debug($message, $context);
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, \Stringable|string $message, array $context = []): void {
    $this->logger->log($level, $message, $context);
  }

}
