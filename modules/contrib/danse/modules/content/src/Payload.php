<?php

namespace Drupal\danse_content;

use Drupal\comment\CommentInterface;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\danse\Entity\Event;
use Drupal\danse\Entity\EventInterface;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;
use Drupal\danse_content\Topic\TopicInterface;
use Drupal\user\Entity\User;

/**
 * The content payload class.
 *
 * @package Drupal\danse_content
 */
final class Payload extends PayloadBase {

  /**
   * The content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected ContentEntityInterface $entity;

  /**
   * Status of the entity if OK for full operations.
   *
   * @var bool
   */
  protected bool $entityOkStatus;

  /**
   * Content payload constructor.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The content entity.
   * @param bool $entityOkStatus
   *   TRUE, if the entity is OK for all operation, FALSE otherwise, e.g. if
   *   this is a deleted entity or an entity whose entity type doesn't exist
   *   anymore.
   */
  public function __construct(ContentEntityInterface $entity, bool $entityOkStatus = TRUE) {
    $this->entity = $entity;
    $this->entityOkStatus = $entityOkStatus;
  }

  /**
   * {@inheritdoc}
   */
  public function label(EventInterface $event): string {
    return $this->entity->label() ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getEventReference(): string {
    return implode('-', [$this->entity->getEntityTypeId(), $this->entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionReferences(EventInterface $event): array {
    if (!$this->entityOkStatus) {
      return [];
    }
    $plugin = $event->getPlugin();
    $topic = $event->getTopic();
    $references = [];

    // Subscription to entity type.
    $references[] = $plugin->subscriptionKey($this->entity->getEntityTypeId(), $this->entity->bundle(), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE, $topic);

    // Subscription to individual entity.
    $references[] = $plugin->subscriptionKey($this->entity->getEntityTypeId(), $this->entity->id(), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY, $topic);

    if ($this->entity instanceof CommentInterface) {

      // Subscription to comments of individual entity.
      $references[] = $plugin->subscriptionKey($this->entity->getEntityTypeId(), strval($this->entity->getCommentedEntityId()), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY, $topic);

      // Subscriptions to comment parents.
      $comment = $this->entity;
      while ($comment = $comment->getParentComment()) {
        $references[] = $plugin->subscriptionKey($comment->getEntityTypeId(), $comment->id(), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY, $topic);
      }
      // Subscriptions to commented entity.
      $comment = $this->entity;
      $references[] = $plugin->subscriptionKey($comment->getCommentedEntityTypeId(), strval($comment->getCommentedEntityId()), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY, TopicInterface::COMMENT);
      $references[] = $plugin->subscriptionKey($comment->getCommentedEntityTypeId(), strval($comment->getCommentedEntityId()), SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE, TopicInterface::COMMENT);
    }

    // Subscription to related content.
    foreach ($this->entity->getFieldDefinitions() as $fieldName => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'entity_reference') {
        foreach ($this->entity->get($fieldName)->referencedEntities() as $referencedEntity) {
          $references[] = $plugin->subscriptionKey($referencedEntity->getEntityTypeId(), $referencedEntity->id(), SubscriptionOperation::SUBSCRIPTION_MODE_RELATED_ENTITY, $topic);
        }
      }
    }

    return $references;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareArray(): array {
    return [
      'entity' => [
        'type' => $this->entity->getEntityTypeId(),
        'bundle' => $this->entity->bundle(),
        'id' => $this->entity->id(),
        'label' => $this->entity->label(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromArray(array $payload): ?PayloadInterface {
    $entityOkStatus = TRUE;
    try {
      $storage = \Drupal::entityTypeManager()->getStorage($payload['entity']['type']);
      /**
       * @var \Drupal\Core\Entity\ContentEntityInterface|null $entity
       */
      $entity = $storage->load($payload['entity']['id']);
      if ($entity === NULL) {
        // Entity got deleted, created a dummy entity and don't save it.
        $entity = $storage->create([
          $storage->getEntityType()->getKey('bundle') => $payload['entity']['bundle'],
          $storage->getEntityType()->getKey('id') => $payload['entity']['id'],
          $storage->getEntityType()->getKey('label') => $payload['entity']['label'],
        ]);
        $entityOkStatus = FALSE;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $entity = Event::create([
        'id' => 0,
        'label' => 'Content type ' . $payload['entity']['type'] . ' no longer exists.',
      ]);
      $entityOkStatus = FALSE;
    }
    // @phpstan-ignore-next-line
    return new Payload($entity, $entityOkStatus);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ContentEntityInterface {
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function hasAccess(int $uid): bool {
    if (!$this->entityOkStatus) {
      return FALSE;
    }
    /**
     * @var \Drupal\user\UserInterface $user
     */
    $user = User::load($uid);
    return $this->getEntity()->access('view', $user);
  }

}
