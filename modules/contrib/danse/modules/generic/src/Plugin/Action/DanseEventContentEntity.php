<?php

namespace Drupal\danse_generic\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Action plugin to create a new DANSE event with an entity.
 *
 * @Action(
 *   id = "danse_event_content_entity",
 *   label = @Translation("New DANSE event with entity"),
 *   description = @Translation("Creates a new DANSE event with a content entity."),
 *   type = "entity"
 * )
 */
class DanseEventContentEntity extends DanseEventBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::forbidden();
    if ($object instanceof ContentEntityInterface) {
      $result = $object->access('view', $account, $return_as_object);
    }
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function getData(mixed $entity = NULL): ContentEntityInterface {
    if (!($entity instanceof ContentEntityInterface)) {
      throw new \InvalidArgumentException('This action requires a content entity.');
    }
    return $entity;
  }

}
