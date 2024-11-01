<?php

namespace Drupal\danse_webhook;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;

/**
 * The webhook payload class.
 *
 * @package Drupal\danse_webhook
 */
final class Payload extends PayloadBase {

  /**
   * The external ID.
   *
   * @var string
   */
  protected string $extId;

  /**
   * The list of recipients.
   *
   * @var array
   */
  protected array $recipients;

  /**
   * The label.
   *
   * @var string
   */
  protected string $label;

  /**
   * The message.
   *
   * @var string
   */
  protected string $message;

  /**
   * Webhook payload constructor.
   *
   * @param string $extId
   *   The external ID.
   */
  public function __construct(string $extId) {
    $this->extId = $extId;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return $this->label;
  }

  /**
   * Adds a recipient.
   *
   * @param string $recipient
   *   The recipient.
   *
   * @return \Drupal\danse_webhook\Payload
   *   Self.
   */
  public function addRecipient(string $recipient): Payload {
    $this->recipients[] = $recipient;
    return $this;
  }

  /**
   * Sets the label.
   *
   * @param string $label
   *   The label.
   *
   * @return \Drupal\danse_webhook\Payload
   *   Self.
   */
  public function setLabel(string $label): Payload {
    $this->label = $label;
    return $this;
  }

  /**
   * Set the message.
   *
   * @param string $message
   *   The message.
   *
   * @return \Drupal\danse_webhook\Payload
   *   Self.
   */
  public function setMessage(string $message): Payload {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return implode('-', ['webhook', $this->extId]);
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
      'extid' => $this->extId,
      'recipients' => $this->recipients ?? [],
      'label' => $this->label,
      'message' => $this->message,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    $item = new Payload($payload['extid']);
    foreach ($payload['recipients'] as $recipient) {
      $item->addRecipient($recipient);
    }
    $item
      ->setLabel($payload['label'])
      ->setMessage($payload['message']);
    return $item;
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
      'plugin' => 'webhook',
      'topic' => '',
      'label' => $this->label,
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
    if (empty($this->recipients)) {
      return TRUE;
    }

    try {
      // @phpstan-ignore-next-line
      $storage = \Drupal::entityTypeManager()->getStorage('user');
      foreach ($this->recipients as $recipient) {
        $users = $storage->getQuery('OR')
          ->accessCheck(FALSE)
          ->condition('name', $recipient)
          ->condition('mail', $recipient)
          ->execute();
        if (!empty($users)) {
          return TRUE;
        }
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // @todo Log this exception.
    }
    return FALSE;
  }

}
