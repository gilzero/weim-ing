<?php

namespace Drupal\saml_sp\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\saml_sp\IdpInterface;

/**
 * Provides a listing of Example.
 */
class IdpListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['url'] = $this->t('URL');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    assert($entity instanceof IdpInterface);
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['url'] = $entity->getLoginUrl();
    // You probably want a few more properties here...
    if (!empty($entity->id())) {
      return $row + parent::buildRow($entity);
    }
    return $row;
  }

}
