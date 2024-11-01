<?php

declare(strict_types=1);

namespace Drupal\aws\Entity\ListBuilder;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of of AWS Profile entities.
 */
class ProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Profile');
    $header['access_key'] = $this->t('Access Key');
    $header['region'] = $this->t('Region');
    $header['default'] = $this->t('Default');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\aws\Entity\ProfileInterface $entity */

    $row['label'] = $entity->label();
    $row['access_key'] = $entity->getAccessKey();
    $row['description'] = $entity->getRegion();
    $row['default'] = $entity->isDefault() ? $this->t('Yes') : $this->t('No');

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    /** @var \Drupal\aws\Entity\ProfileInterface $entity */

    $operations = parent::getOperations($entity);

    // The default profile should not be able to be deleted.
    if (isset($operations['delete']) && $entity->isDefault()) {
      unset($operations['delete']);
    }

    return $operations;
  }

}
