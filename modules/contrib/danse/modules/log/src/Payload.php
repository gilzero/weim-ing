<?php

namespace Drupal\danse_log;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;

/**
 * The log payload class.
 *
 * @package Drupal\danse_log
 */
final class Payload extends PayloadBase {

  /**
   * The log level.
   *
   * @var int
   */
  private int $level;

  /**
   * The log message.
   *
   * @var string
   */
  protected string $message;

  /**
   * The lof context.
   *
   * @var array
   */
  protected array $context;

  /**
   * Log payload constructor.
   *
   * @param int $level
   *   The log level.
   * @param string $message
   *   The log message.
   * @param array $context
   *   The log context.
   */
  public function __construct(int $level, string $message, array $context) {
    $this->level = $level;
    $this->message = $message;
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    $args = $this->context;
    unset($args['backtrace'], $args['placeholders']);
    $message = $this->message . ' @dansedetail';
    $context = $this->context['placeholders'];
    try {
      $context['@dansedetail'] = json_encode($args, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      // We can not log this exception, that would lead to log loop.
      $context['@dansedetail'] = serialize($args);
    }
    return trim(PlainTextOutput::renderFromHtml(new FormattableMarkup($message, $context)));
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'level' => $this->level,
      'message' => $this->message,
      'context' => $this->context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    return new Payload(
      $payload['level'],
      $payload['message'],
      $payload['context']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    /**
     * @var \Drupal\danse\Entity\Event $entity
     */
    $entity = Event::create([
      'id' => 0,
      'plugin' => 'log',
      'topic' => '',
      'label' => '',
      'payload' => $this,
      'push' => TRUE,
      'force' => TRUE,
      'silent' => FALSE,
    ]);
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAccess(int $uid): bool {
    return FALSE;
  }

  /**
   * Get the log level.
   *
   * @return int
   *   The log level.
   */
  public function getLevel(): int {
    return $this->level;
  }

  /**
   * Get the log message.
   *
   * @return string
   *   The log message.
   */
  public function getMessage(): string {
    return $this->message;
  }

  /**
   * Get the log context.
   *
   * @return array
   *   The log context.
   */
  public function getContext(): array {
    return $this->context ?? [];
  }

}
