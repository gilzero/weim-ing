<?php

namespace Drupal\unique_entity_title\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the UniqueEntityTitle constraint.
 */
class UniqueEntityTitleValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    if ($item->isEmpty()) {
      return;
    }
    $entity = $item->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    switch ($entity_type_id) {
      case 'node':
        $entity_bundle = $entity->getType();
        $unique_entity_title_enabled = !empty($entity->type->entity->getThirdPartySetting('unique_entity_title', 'enabled')) ? TRUE : FALSE;
        $unique_entity_title_label = \Drupal::config('core.base_field_override.node.' . $entity_bundle . '.title')->get('label') ? : 'Title';
        $unique_field = 'title';
        $bundle_field = 'type';
        $id_field = 'nid';
        break;

      case 'taxonomy_term':
        $entity_bundle = $entity->bundle();
        $unique_entity_title_enabled = !empty(\Drupal::config('unique_entity_title.settings')
          ->get($entity_bundle . '_taxonomy_unique')) ? TRUE : FALSE;
        $unique_entity_title_label = 'Name';
        $unique_field = 'name';
        $bundle_field = 'vid';
        $id_field = 'tid';
        break;

      default:
        break;
    }

    // Add the violation only if the entity has the unique title enabled.
    if ($unique_entity_title_enabled) {
      $value = $item->getValue()[0]['value'];
      if ($this->isNotUnique($unique_field, $value, $entity_type_id, $bundle_field, $entity_bundle, $id_field)) {
        $this->context->addViolation($constraint->notUnique, ['%label' => $unique_entity_title_label, '%value' => $value]);
      }
    }
  }

  /**
   * Is Not unique?
   *
   * @param string $unique_field
   *   The name of unique field.
   * @param string $value
   *   Value of the field to check for uniqueness.
   * @param string $entity_type_id
   *   Id of the Entity Type.
   * @param string $bundle_field
   *   Field of the Entity type.
   * @param string $bundle
   *   Bundle of the entity.
   * @param string $id_field
   *   Id field of the entity.
   *
   * @return bool
   *   Whether the entity is unique or not
   */
  private function isNotUnique($unique_field, $value, $entity_type_id, $bundle_field, $bundle, $id_field) {
    if (!empty($entity_type_id)) {
      $query = \Drupal::entityQuery($entity_type_id)
        ->accessCheck(FALSE)
        ->condition($unique_field, $value)
        ->condition($bundle_field, $bundle)
        ->range(0, 1);

      // Exclude the current entity.
      if (!empty($id = $this->context->getRoot()->getEntity()->id())) {
        $query->condition($id_field, $id, '!=');
      }
      $entities = $query->execute();
    }
    if (!empty($entities)) {
      return TRUE;
    }
    return FALSE;
  }

}
