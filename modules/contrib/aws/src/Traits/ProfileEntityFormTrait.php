<?php

namespace Drupal\aws\Traits;

/**
 * Provides a trait for entity forms that use the AWS profile entity type.
 */
trait ProfileEntityFormTrait {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\aws\Entity\ProfileInterface
   */
  protected $entity;

}
