<?php

namespace Drupal\danse_content\Topic;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The topic interface.
 *
 * @package Drupal\danse_content\Topic
 */
interface TopicInterface {

  public const CREATE = 'create';

  public const UPDATE = 'update';

  public const DELETE = 'delete';

  public const PUBLISH = 'publish';

  public const UNPUBLISH = 'unpublish';

  public const COMMENT = 'comment';

  /**
   * Get the topic ID.
   *
   * @return string
   *   The topic ID.
   */
  public function id(): string;

  /**
   * Get the topic setting key.
   *
   * @param string $key
   *   The setting key.
   *
   * @return string
   *   The setting key.
   */
  public function getSettingKey(string $key): string;

  /**
   * Get the label for the settings form.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The label.
   */
  public function settingsFormLabel(): TranslatableMarkup;

  /**
   * Determine if this topic should provide default settings.
   *
   * @return bool
   *   TRUE, if this topic provides default settings, FALSE otherwise.
   */
  public function accessToDefaultSettings(): bool;

  /**
   * List of content types, where this topic should not be available.
   *
   * @return array
   *   List of content types.
   */
  public function excludedOnEntityTypes(): array;

  /**
   * List of dependent modules to enable this topic.
   *
   * @return array
   *   List of modules.
   */
  public function dependencies(): array;

  /**
   * Assert, if the topic is available.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return bool
   *   TRUE, if this topic is available for the given entity type ID, FALSE
   *   otherwise.
   */
  public function assert(string $entity_type_id): bool;

  /**
   * Builds a label for a topic related operation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param bool $subscribe
   *   TRUE, if the operation is to subscribe, FALSE for unsubscribe.
   * @param string $subscription_mode
   *   One of SubscriptionOperation::SUBSCRIPTION_MODE_*.
   *
   * @return string
   *   The label.
   */
  public function operationLabel(ContentEntityInterface $entity, bool $subscribe, string $subscription_mode): string;

  /**
   * Get the topic action label.
   *
   * @return string
   *   The label.
   */
  public function actionForLabel(): string;

}
