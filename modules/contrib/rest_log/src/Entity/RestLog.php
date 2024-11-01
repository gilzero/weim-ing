<?php

namespace Drupal\rest_log\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the rest log entity.
 *
 * @ingroup rest_log
 *
 * @ContentEntityType(
 *   id = "rest_log",
 *   label = @Translation("Rest log"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\rest_log\RestLogAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "rest_log",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/rest_log/{rest_log}",
 *     "delete-form" = "/admin/reports/rest_log/{rest_log}/delete",
 *     "delete-multiple-form" = "/admin/reports/rest_log/delete",
 *   }
 * )
 */
class RestLog extends ContentEntityBase implements EntityChangedInterface, EntityOwnerInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritDoc}
   */
  public function label() {
    return implode(' ', [
      $this->get('request_method')->getString(),
      $this->get('request_uri')->getString(),
    ]);
  }

  /**
   * Gets the node creation timestamp.
   *
   * @return int
   *   Creation timestamp of the log.
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * Sets the node creation timestamp.
   *
   * @param int $timestamp
   *   The node creation timestamp.
   *
   * @return $this
   *   The called log entity.
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::ownerBaseFieldDefinitions($entity_type);

    $fields['request_method'] = BaseFieldDefinition::create('string')
      ->setLabel('Request method')
      ->setDescription('Request method');

    $fields['request_header'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request header')
      ->setDescription('Request header');

    $fields['request_uri'] = BaseFieldDefinition::create('string')
      ->setLabel('Request uri')
      ->setDescription('Request uri')
      ->setSetting('max_length', 2048);

    $fields['request_cookie'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request cookie')
      ->setDescription('Request cookie');

    $fields['request_payload'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Request payload')
      ->setDescription('Request payload');

    $fields['response_status'] = BaseFieldDefinition::create('string')
      ->setLabel('Response status')
      ->setDescription('Response status');

    $fields['response_header'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Response header')
      ->setDescription('Response header');

    $fields['response_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel('Response body')
      ->setDescription('Response body');

    $fields['response_time'] = BaseFieldDefinition::create('integer')
      ->setLabel('Response time')
      ->setDescription('Response time');

    $fields[$entity_type->getKey('owner')]
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
