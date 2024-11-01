<?php

declare(strict_types=1);

namespace Drupal\aws\Entity\Storage;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\encrypt\EncryptServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the AWS Profile entity.
 */
class ProfileStorage extends ConfigEntityStorage {

  /**
   * The encryption service.
   *
   * @var \Drupal\encrypt\EncryptServiceInterface|null
   */
  protected $encryption;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    /** @var static $instance */
    $instance = parent::createInstance($container, $entity_type);
    $instance->setEncryption($container->get('encryption', ContainerInterface::NULL_ON_INVALID_REFERENCE));

    return $instance;
  }

  /**
   * Set the encryption service.
   *
   * @param \Drupal\encrypt\EncryptServiceInterface|null $encryption
   *   The encryption service.
   *
   * @return $this
   */
  protected function setEncryption(?EncryptServiceInterface $encryption) {
    $this->encryption = $encryption;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    // Set default language to current language if not provided.
    $values += [$this->langcodeKey => $this->languageManager->getCurrentLanguage()->getId()];
    $entity_class = $this->getEntityClass();
    $entity = new $entity_class($values, $this->entityTypeId, $this->encryption);

    return $entity;
  }

  /**
   * Maps from storage records to entity objects.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   *
   * @return \Drupal\aws\Entity\ProfileInterface[]
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records) {
    $entities = [];
    foreach ($records as $record) {
      $entity_class = $this->getEntityClass();
      /** @var \Drupal\aws\Entity\ProfileInterface $entity */
      $entity = new $entity_class($record, $this->entityTypeId, $this->encryption);
      $entities[$entity->id()] = $entity;
    }
    return $entities;
  }

}
