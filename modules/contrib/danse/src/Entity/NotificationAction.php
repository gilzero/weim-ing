<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the DANSE notification action entity class.
 *
 * @ContentEntityType(
 *   id = "danse_notification_action",
 *   label = @Translation("Notification action"),
 *   internal = TRUE,
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "danse_notification_action",
 *   data_table = "danse_notification_action",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {},
 * )
 */
class NotificationAction extends ContentEntityBase implements NotificationActionInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    if (!empty($values['payload']) && !is_string($values['payload'])) {
      $values['payload'] = json_encode($values['payload']);
    }
    parent::preCreate($storage, $values);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['notification'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Notification')
      ->setRequired(TRUE)
      ->setSetting('target_type', 'danse_notification');
    $fields['success'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Success')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Payload')
      ->setRequired(TRUE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setRequired(TRUE);

    return $fields;
  }

}
