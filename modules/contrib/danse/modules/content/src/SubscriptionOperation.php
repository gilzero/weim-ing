<?php

namespace Drupal\danse_content;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\danse_content\Topic\TopicInterface;

/**
 * Provides operations to subscribe/unsubscribe to/from content entities.
 *
 * @package Drupal\danse_content
 */
class SubscriptionOperation {

  use StringTranslationTrait;

  public const MODE_SUBSCRIBE = 1;
  public const MODE_UNSUBSCRIBE = 0;

  public const SUBSCRIPTION_MODE_ENTITY_TYPE = '0';
  public const SUBSCRIPTION_MODE_ENTITY = '1';
  public const SUBSCRIPTION_MODE_RELATED_ENTITY = '2';

  /**
   * The mode of the operation.
   *
   * @var int
   */
  protected int $mode;

  /**
   * The content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * The operation key.
   *
   * @var string
   */
  protected string $key;

  /**
   * The topic of the operation.
   *
   * @var \Drupal\danse_content\Topic\TopicInterface
   */
  protected TopicInterface $topic;

  /**
   * The subscription mode of the operation.
   *
   * @var string
   */
  protected string $subscriptionMode;

  /**
   * SubscriptionOption constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param string $key
   *   The operation key.
   * @param \Drupal\danse_content\Topic\TopicInterface $topic
   *   The topic of the operation.
   * @param string $subscription_mode
   *   The subscription mode of the operation.
   */
  public function __construct(ContentEntityInterface $entity, string $key, TopicInterface $topic, string $subscription_mode) {
    $this->mode = self::MODE_SUBSCRIBE;
    $this->entity = $entity;
    $this->key = $key;
    $this->topic = $topic;
    $this->subscriptionMode = $subscription_mode;
  }

  /**
   * Returns whether this is a subscribe or unsubscribe operation.
   *
   * @return bool
   *   TRUE for subscribe, FALSE for unsubscribe.
   */
  public function isSubscribeMode(): bool {
    return $this->mode === self::MODE_SUBSCRIBE;
  }

  /**
   * Set the subscription mode for the operation.
   *
   * @return \Drupal\danse_content\SubscriptionOperation
   *   Self.
   */
  public function setSubscribeMode(): SubscriptionOperation {
    $this->mode = self::MODE_SUBSCRIBE;
    return $this;
  }

  /**
   * Set the unsubscription mode for the operation.
   *
   * @return \Drupal\danse_content\SubscriptionOperation
   *   Self.
   */
  public function setUnsubscribeMode(): SubscriptionOperation {
    $this->mode = self::MODE_UNSUBSCRIBE;
    return $this;
  }

  /**
   * Returns an array with details for the subscription widget.
   *
   * @return array
   *   The details.
   */
  public function operationItem(): array {
    if ($this->isSubscribeMode()) {
      $routeName = 'danse_content.api.subscribe';
      $class = 'subscribe';
    }
    else {
      $routeName = 'danse_content.api.unsubscribe';
      $class = 'unsubscribe';
    }
    return [
      'title' => $this->topic->operationLabel($this->entity, $this->isSubscribeMode(), $this->subscriptionMode),
      'weight' => 0,
      'url' => Url::fromRoute($routeName, [
        'entity_type' => $this->entity->getEntityTypeId(),
        'entity_id' => $this->entity->id(),
        'key' => $this->key,
      ], [
        'attributes' => [
          'class' => [
            'danse-subscription-operation',
            $class,
            $this->key,
            'use-ajax',
          ],
        ],
      ]),
    ];
  }

}
