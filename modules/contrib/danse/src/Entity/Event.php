<?php

namespace Drupal\danse\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\danse\PayloadBase;
use Drupal\danse\PayloadInterface;
use Drupal\danse\PluginInterface;

/**
 * Defines the DANSE event entity class.
 *
 * @ContentEntityType(
 *   id = "danse_event",
 *   label = @Translation("Event"),
 *   internal = TRUE,
 *   handlers = {
 *     "view_builder" = "Drupal\danse\Entity\EventView",
 *     "access" = "Drupal\danse\Entity\EventAccess",
 *     "views_data" = "Drupal\danse\Entity\EventViewsData",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "danse_event",
 *   data_table = "danse_event",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "type" = "topic",
 *     "uuid" = "uuid",
 *     "label" = "label",
 *     "status" = "processed"
 *   },
 *   links = {
 *     "canonical" = "/danse/event/{danse_event}",
 *   },
 * )
 */
class Event extends ContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values): void {
    if (!empty($values['payload'])) {
      $values['reference'] = $values['payload']->getEventReference();
      $values['payload'] = json_encode($values['payload']->toArray());
    }
    if (!empty($values['silent'])) {
      $values['processed'] = TRUE;
    }
    $values['uid'] = \Drupal::currentUser()->id();
    parent::preCreate($storage, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    if (mb_strlen($this->label()) > 255) {
      $this->set('label', mb_substr($this->label(), 0, 250) . ' ...');
    }
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return $this->get('plugin')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin(): PluginInterface {
    return \Drupal::service('plugin.manager.danse.plugin')->createInstance($this->getPluginId());
  }

  /**
   * {@inheritdoc}
   */
  public function getPayload(): ?PayloadInterface {
    $payload = $this->get('payload')->value;
    if (is_string($payload)) {
      $payload = json_decode($payload, TRUE);
    }
    return is_array($payload) ?
      PayloadBase::fromArray($payload) :
      NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTopic(): string {
    return $this->get('topic')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isPush(): bool {
    return (bool) $this->get('push')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isForce(): bool {
    return (bool) $this->get('force')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessed(): EventInterface {
    try {
      $this
        ->set('processed', TRUE)
        ->save();
    }
    catch (EntityStorageException $e) {
      // @todo Log this exception.
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['plugin'] = BaseFieldDefinition::create('string')
      ->setLabel('Plugin')
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);
    $fields['topic'] = BaseFieldDefinition::create('string')
      ->setLabel('Topic')
      ->setRequired(TRUE)
      ->setSetting('max_length', 32);
    $fields['reference'] = BaseFieldDefinition::create('string')
      ->setLabel('Reference')
      ->setRequired(TRUE)
      ->setSetting('max_length', 64);
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel('Label')
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);
    $fields['payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Payload')
      ->setRequired(TRUE);
    $fields['push'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Push')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['force'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Force')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['silent'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Silent')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['processed'] = BaseFieldDefinition::create('boolean')
      ->setLabel('Processed')
      ->setRequired(TRUE)
      ->setDefaultValue(FALSE);
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Created')
      ->setRequired(TRUE);
    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel('Change')
      ->setRequired(TRUE);
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('User ID')
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    return $fields;
  }

}
