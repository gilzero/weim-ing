<?php

/**
 * @file
 * The DANSE Content API file.
 */

use Drupal\comment\CommentInterface;
use Drupal\danse_content\SubscriptionOperation;
use Drupal\danse_content\Topic\TopicInterface;

/**
 * Alter labels for subscription operations.
 *
 * @param string $label
 *   The label as computed from the modules logic which can be modified by
 *   implementing this alter hook.
 * @param array $args
 *   Contains a number of already translated arguments for the new translation
 *   of the altered label:
 *   - "@entityType": the human readable label of the entity type of the entity
 *        for which the operations will be provided.
 *   - "@bundle": the human readable label of the bundle of the entity
 *        for which the operations will be provided.
 *   - "@action": the verb in past tense for the topic that may be operated on
 *        the entity, e.g. "created" or "published".
 *   - "@title": the label of the entity for which the operations will be
 *        provided.
 * @param array $context
 *   Contains a number of objects known to the context in which the operation
 *   label gets calculated:
 *   - "topic": the topic for the operation on the entity.
 *        @see Drupal\danse_content\Topic\TopicInterface
 *   - "entity": The entity for which the operation gets provided.
 *        @see Drupal\danse_content\Topic\TopicInterface
 *   - "subscribeMode": A boolean indicating if the operation is to subscribe
 *        (set to TRUE) to or to unsubscribe (set to FALSE) from the events.
 *   - "subscriptionMode": An integer indicating the subscription mode, i.e.
 *        whether to subscribe/unsubscribe to/from entity type, individual
 *        entity or related entities. Allowed values are
 *        - SUBSCRIPTION_MODE_ENTITY_TYPE
 *        - SUBSCRIPTION_MODE_ENTITY
 *        - SUBSCRIPTION_MODE_RELATED_ENTITY
 *        @see SubscriptionOperation
 *   - "entityHasBundle": A boolean indicating if this entity's entity-type
 *        supports bundles or not. Nodes for example support bundles, but
 *        user entities don't.
 *
 * @example See https://www.drupal.org/project/danse_label_override
 */
function hook_danse_content_topic_operation_label_alter(string &$label, array $args, array $context): void {

  // Extract all context variables.
  /**
   * @var \Drupal\danse_content\Topic\TopicInterface $topic
   */
  $topic = $context['topic'];
  /**
   * @var \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  $entity = $context['entity'];
  /**
   * @var bool $subscribeMode
   */
  $subscribeMode = $context['subscribeMode'];
  /**
   * @var string $subscriptionMode
   */
  $subscriptionMode = $context['subscriptionMode'];
  /**
   * @var bool $entityHasBundle
   */
  $entityHasBundle = $context['entityHasBundle'];

  // Add a new translation argument which will be used later depending on
  // the exception that needs to be covered.
  $args['@verb'] = $subscribeMode ? (string) t('Subscribe to') : (string) t('Unsubscribe from');

  if ($entity instanceof CommentInterface) {
    // We want to change the default label for the publish and update topics
    // on comment entities.
    switch ($topic->id()) {
      case TopicInterface::PUBLISH:
        $label = (string) t('@verb all responses to this comment "@title"', $args);
        break;

      case TopicInterface::UPDATE:
        $label = (string) t('@verb updates on all responses', $args);
        break;

    }
  }
  elseif ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_ENTITY_TYPE) {
    // Let's use much shorter labels for operations on entity types.
    if ($entityHasBundle) {
      $label = (string) t('@verb all events on @bundle entities', $args);
    }
    else {
      $label = (string) t('@verb all events on @entityType @bundle', $args);
    }
  }
  elseif ($subscriptionMode === SubscriptionOperation::SUBSCRIPTION_MODE_RELATED_ENTITY) {
    $label = (string) t('@verb all events on related content to @title', $args);
  }
}
