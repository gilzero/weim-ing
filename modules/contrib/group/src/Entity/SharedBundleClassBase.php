<?php

namespace Drupal\group\Entity;

/**
 * Base class for shared bundle classes.
 *
 * This class takes care of the static methods that would otherwise crash when
 * you have one class serving multiple bundles.
 */
class SharedBundleClassBase extends GroupRelationship {

  /**
   * {@inheritdoc}
   */
  public static function create(array $values = []) {
    return \Drupal::entityTypeManager()->getStorage('group_relationship')->create($values);
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id) {
    return \Drupal::entityTypeManager()->getStorage('group_relationship')->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public static function loadMultiple(?array $ids = NULL) {
    return \Drupal::entityTypeManager()->getStorage('group_relationship')->loadMultiple($ids);
  }

}
