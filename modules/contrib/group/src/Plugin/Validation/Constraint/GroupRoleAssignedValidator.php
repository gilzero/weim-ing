<?php

namespace Drupal\group\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;
use Drupal\group\PermissionScopeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks whether a group role is assigned when it shouldn't be.
 */
class GroupRoleAssignedValidator extends ConstraintValidator implements ContainerInjectionInterface {

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
    $group_role = $value;
    assert($group_role instanceof GroupRoleInterface);
    assert($constraint instanceof GroupRoleAssigned);

    $scope = $group_role->getScope();
    if ($scope !== PermissionScopeInterface::INDIVIDUAL_ID) {
      $role_storage = $this->entityTypeManager->getStorage('group_role');
      assert($role_storage instanceof GroupRoleStorageInterface);

      if ($group_role->id() && $role_storage->hasMembershipReferences([$group_role->id()])) {
        $this->context->buildViolation($constraint->alreadyAssignedMessage)
          ->setParameter('%scope', $scope)
          ->atPath('scope')
          ->addViolation();
      }
    }
  }

}
