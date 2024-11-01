<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks whether a group membership's roles are valid.
 */
class GroupMembershipRolesValidator extends ConstraintValidator implements ContainerInjectionInterface {

  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    $group_relationship = $value;
    assert($group_relationship instanceof GroupRelationshipInterface);
    assert($constraint instanceof GroupMembershipRoles);

    // This constraint only applies to memberships.
    if ($group_relationship->getPluginId() !== 'group_membership') {
      return;
    }

    // If someone for some reason removed the roles field, we can't do anything.
    if (!$group_relationship->hasField('group_roles')) {
      return;
    }

    $group_role_storage = $this->entityTypeManager->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);

    $existing_roles = $group_role_storage->loadMultiple();
    foreach ($group_relationship->get('group_roles') as $group_role_ref) {
      assert($group_role_ref instanceof EntityReferenceItem);
      $group_role_id = $group_role_ref->target_id;

      if (!array_key_exists($group_role_id, $existing_roles)) {
        $this->context->buildViolation($constraint->roleDoesNotExistMessage)
          ->setParameter('@role_id', $group_role_id)
          ->atPath('group_roles')
          ->addViolation();
      }
      else {
        assert($existing_roles[$group_role_id] instanceof GroupRoleInterface);
        // @todo 4.x.x: This overlaps with ValidReferenceConstraint, remove when
        //   we validate all constraints on a group relationship in preSave().
        if ($existing_roles[$group_role_id]->getGroupTypeId() !== $group_relationship->getGroupTypeId()) {
          $this->context->buildViolation($constraint->roleDifferentGroupTypeMessage)
            ->setParameter('@role_id', $group_role_id)
            ->atPath('group_roles')
            ->addViolation();
        }
        if ($existing_roles[$group_role_id]->getScope() !== PermissionScopeInterface::INDIVIDUAL_ID) {
          $this->context->buildViolation($constraint->roleNonIndividualScopeMessage)
            ->setParameter('@role_id', $group_role_id)
            ->atPath('group_roles')
            ->addViolation();
        }
      }
    }
  }

}
