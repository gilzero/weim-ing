<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Site\Settings;

/**
 * Defines the DANSE notification entity class.
 *
 * @ContentEntityType(
 *   id = "danse_notification",
 *   label = @Translation("Notification"),
 *   internal = TRUE,
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "danse_notification",
 *   data_table = "danse_notification",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {},
 * )
 */
class Notification extends ContentEntityBase implements NotificationInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function postCreate(EntityStorageInterface $storage): void {
    parent::postCreate($storage);
    if (!Settings::get('danse_notification_delivery', TRUE)) {
      $this->set('delivered', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function uid(): int {
    return (int) $this->get('uid')->getValue()[0]['target_id'];
  }

  /**
   * {@inheritdoc}
   */
  public function event(): EventInterface {
    return Event::load($this->get('event')->getValue()[0]['target_id']);
  }

  /**
   * {@inheritdoc}
   */
  public function markSeen(): NotificationInterface {
    if (!$this->get('seen')->value) {
      try {
        $this
          ->set('seen', TRUE)
          ->save();
      }
      catch (EntityStorageException $e) {
        // @todo Log this exception.
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function markDelivered(): NotificationInterface {
    if (!$this->get('delivered')->value) {
      try {
        $this
          ->set('delivered', TRUE)
          ->save();
      }
      catch (EntityStorageException $e) {
        // @todo Log this exception.
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSuccessor(NotificationInterface $notification): NotificationInterface {
    if (!$this->get('redundant')->value) {
      try {
        $this
          ->set('redundant', TRUE)
          ->set('successor', $notification)
          ->save();
      }
      catch (EntityStorageException $e) {
        // @todo Log this exception.
      }
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['event'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Event')
      ->setRequired(TRUE)
      ->setSetting('target_type', 'danse_event');
    $fields['trigger'] = BaseFieldDefinition::create('string')
      ->setLabel('Trigger')
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('User')
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user');
    $fields['delivered'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Delivered')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['seen'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Seen')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['redundant'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Redundant')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['successor'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Successor')
      ->setRequired(FALSE)
      ->setSetting('target_type', 'danse_notification');
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setRequired(TRUE);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel('Change')
      ->setRequired(TRUE);

    return $fields;
  }

}
