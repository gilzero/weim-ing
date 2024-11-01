<?php

namespace Drupal\group\Plugin\Group\RelationHandler;

use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Trait for group relation entity reference handlers.
 */
trait EntityReferenceTrait {

  use RelationHandlerTrait;

  /**
   * The parent entity reference handler in the decorator chain.
   *
   * @var \Drupal\group\Plugin\Group\RelationHandler\EntityReferenceInterface|null
   */
  protected $parent = NULL;

  /**
   * {@inheritdoc}
   */
  public function configureField(BaseFieldDefinition $entity_reference) {
    if (!isset($this->parent)) {
      throw new \LogicException('Using EntityReferenceTrait without assigning a parent or overwriting the methods.');
    }
    return $this->parent->configureField($entity_reference);
  }

}
