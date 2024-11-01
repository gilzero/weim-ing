<?php

namespace Drupal\Tests\group\Kernel\QueryAlter;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\group\Entity\GroupTypeInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Base class for testing query alters.
 */
abstract class QueryAlterTestBase extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_test_plugin', 'node'];

  /**
   * The entity type ID to use in testing.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Whether the entity type supports publishing.
   *
   * @var bool
   */
  protected $isPublishable = FALSE;

  /**
   * Whether the entity type supports ownership.
   *
   * @var bool
   */
  protected $isOwnable = TRUE;

  /**
   * Whether access might be different if relationships do (not) exist.
   *
   * @var bool
   */
  protected $relationshipsAffectAccess = TRUE;

  /**
   * Tests query access in various scenarios.
   *
   * @param string $operation
   *   The operation to test access for.
   * @param bool $operation_supported
   *   Whether the operation is supported.
   * @param bool $operation_supports_status
   *   Whether the operation supports status checks.
   * @param bool $has_relationships
   *   If relationships exist for the entity type.
   * @param bool $has_access
   *   Whether the user should gain access.
   * @param bool $joins_member_table
   *   Whether the query is expected to join the membership table.
   * @param bool $joins_data_table
   *   Whether the query is expected to join the data table.
   * @param string[] $individual_permissions
   *   The user's group permissions in the individual scope.
   * @param bool $individual_is_admin
   *   Whether the user is a group admin via an individual role.
   * @param bool $individual_simple_check
   *   Whether the individual simple permissions should be checked.
   * @param bool $individual_owner_check
   *   Whether the individual owner permissions should be checked.
   * @param bool $individual_published_simple_check
   *   Whether the individual simple published permissions should be checked.
   * @param bool $individual_published_owner_check
   *   Whether the individual owner published permissions should be checked.
   * @param bool $individual_unpublished_simple_check
   *   Whether the individual simple unpublished permissions should be checked.
   * @param bool $individual_unpublished_owner_check
   *   Whether the individual owner unpublished permissions should be checked.
   * @param string[] $outsider_permissions
   *   The user's group permissions in the outsider scope.
   * @param bool $outsider_is_admin
   *   Whether the user is a group admin via an outsider role.
   * @param bool $outsider_simple_check
   *   Whether the outsider simple permissions should be checked.
   * @param bool $outsider_owner_check
   *   Whether the outsider owner permissions should be checked.
   * @param bool $outsider_published_simple_check
   *   Whether the outsider simple published permissions should be checked.
   * @param bool $outsider_published_owner_check
   *   Whether the outsider owner published permissions should be checked.
   * @param bool $outsider_unpublished_simple_check
   *   Whether the outsider simple unpublished permissions should be checked.
   * @param bool $outsider_unpublished_owner_check
   *   Whether the outsider owner unpublished permissions should be checked.
   * @param string[] $insider_permissions
   *   The user's group permissions in the insider scope.
   * @param bool $insider_is_admin
   *   Whether the user is a group admin via an insider role.
   * @param bool $insider_simple_check
   *   Whether the insider simple permissions should be checked.
   * @param bool $insider_owner_check
   *   Whether the insider owner permissions should be checked.
   * @param bool $insider_published_simple_check
   *   Whether the insider simple published permissions should be checked.
   * @param bool $insider_published_owner_check
   *   Whether the insider owner published permissions should be checked.
   * @param bool $insider_unpublished_simple_check
   *   Whether the insider simple unpublished permissions should be checked.
   * @param bool $insider_unpublished_owner_check
   *   Whether the insider owner unpublished permissions should be checked.
   *
   * @covers ::getConditions
   * @dataProvider queryAccessProvider
   */
  public function testQueryAccess(
    string $operation,
    bool $operation_supported,
    bool $operation_supports_status,
    bool $has_relationships,
    bool $has_access,
    bool $joins_member_table,
    bool $joins_data_table,
    array $individual_permissions,
    bool $individual_is_admin,
    bool $individual_simple_check,
    bool $individual_owner_check,
    bool $individual_published_simple_check,
    bool $individual_published_owner_check,
    bool $individual_unpublished_simple_check,
    bool $individual_unpublished_owner_check,
    array $outsider_permissions,
    bool $outsider_is_admin,
    bool $outsider_simple_check,
    bool $outsider_owner_check,
    bool $outsider_published_simple_check,
    bool $outsider_published_owner_check,
    bool $outsider_unpublished_simple_check,
    bool $outsider_unpublished_owner_check,
    array $insider_permissions,
    bool $insider_is_admin,
    bool $insider_simple_check,
    bool $insider_owner_check,
    bool $insider_published_simple_check,
    bool $insider_published_owner_check,
    bool $insider_unpublished_simple_check,
    bool $insider_unpublished_owner_check,
  ) {
    // Run some sanity checks on the passed in data and aggregate info.
    $checks_status = $checks_owner = $checks_member = FALSE;
    foreach (['individual', 'outsider', 'insider'] as $key) {
      // @todo Ideally isOwnable is passed in as an argument.
      if (!$this->isOwnable) {
        $this->assertFalse(${$key . '_owner_check'}, 'Cannot check owner if owning is not supported.');
      }

      if (!$operation_supports_status) {
        $this->assertFalse(${$key . '_published_simple_check'}, 'Cannot check published status if publishing is not supported.');
        $this->assertFalse(${$key . '_published_owner_check'}, 'Cannot check published owner status if publishing is not supported.');
        $this->assertFalse(${$key . '_unpublished_simple_check'}, 'Cannot check unpublished status if publishing is not supported.');
        $this->assertFalse(${$key . '_unpublished_owner_check'}, 'Cannot check unpublished owner status if publishing is not supported.');
      }

      if (${$key . '_is_admin'}) {
        $this->assertTrue(${$key . '_simple_check'}, 'Admin access always leads to simple checks.');
        $this->assertFalse(${$key . '_owner_check'}, 'Admin access always leads to simple checks.');
        $this->assertFalse(${$key . '_published_simple_check'}, 'Admin access always leads to simple checks.');
        $this->assertFalse(${$key . '_published_owner_check'}, 'Admin access always leads to simple checks.');
        $this->assertFalse(${$key . '_unpublished_simple_check'}, 'Admin access always leads to simple checks.');
        $this->assertFalse(${$key . '_unpublished_owner_check'}, 'Admin access always leads to simple checks.');
      }

      $checks_owner = $checks_owner || ${$key . '_owner_check'};
      $checks_status = $checks_status
      || ${$key . '_published_simple_check'} || ${$key . '_published_owner_check'}
      || ${$key . '_unpublished_simple_check'} || ${$key . '_unpublished_owner_check'};

      if ($key !== 'individual') {
        $checks_member = $checks_member
        || ${$key . '_simple_check'} || ${$key . '_owner_check'}
        || ${$key . '_published_simple_check'} || ${$key . '_published_owner_check'}
        || ${$key . '_unpublished_simple_check'} || ${$key . '_unpublished_owner_check'};
      }
    }

    if ($checks_member) {
      $this->assertTrue($joins_member_table, 'Member table should be checked for (non-)membership based access.');
    }

    if ($checks_status || $checks_owner) {
      $this->assertTrue($joins_data_table, 'Data table should be checked for status or owner.');
    }

    $definition = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $data_table = $definition->getDataTable() ?: $definition->getBaseTable();
    $group_type = $this->createGroupType();

    if ($individual_permissions || $individual_is_admin) {
      $group_role = $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
        'permissions' => $individual_is_admin ? [] : $individual_permissions,
        'admin' => $individual_is_admin,
      ]);
      $group_type->set('creator_roles', [$group_role->id()]);
      $group_type->save();
    }

    if ($outsider_permissions || $outsider_is_admin) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::OUTSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $outsider_is_admin ? [] : $outsider_permissions,
        'admin' => $outsider_is_admin,
      ]);
    }

    if ($insider_permissions || $insider_is_admin) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $insider_is_admin ? [] : $insider_permissions,
        'admin' => $insider_is_admin,
      ]);
    }

    if ($has_relationships) {
      $group = $this->setUpContent($group_type);
    }

    $query = $this->createAlterableQuery($operation);
    $control = $this->createAlterableQuery($operation);

    $this->alterQuery($query);
    if ($operation_supported && $has_relationships) {
      $this->joinExtraTables($control);

      if (!$has_access) {
        $this->addNoAccessConditions($control);
      }
      else {
        if ($definition->getDataTable() && $joins_data_table) {
          $this->joinTargetEntityDataTable($control);
        }

        if ($joins_member_table) {
          $this->joinMemberships($control);
        }

        $scope_conditions = $this->addWrapperConditionGroup($control);

        if ($outsider_simple_check) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, TRUE);
        }
        if ($insider_simple_check) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addSynchronizedConditions([$group_type->id()], $scope_conditions, FALSE);
        }
        if ($individual_simple_check) {
          $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          $this->addIndividualConditions([$group->id()], $scope_conditions);
        }

        if ($this->isOwnable && !$operation_supports_status) {
          if ($individual_owner_check || $outsider_owner_check || $insider_owner_check) {
            $owner_key = $definition->getKey('owner');
            $scope_conditions->condition($owner_group = $control->andConditionGroup());
            $owner_group->condition("$data_table.$owner_key", $this->getCurrentUser()->id());
            $owner_group->condition($owner_conditions = $control->orConditionGroup());
          }

          if ($outsider_owner_check) {
            $this->addSynchronizedConditions([$group_type->id()], $owner_conditions, TRUE);
          }
          if ($insider_owner_check) {
            $this->addSynchronizedConditions([$group_type->id()], $owner_conditions, FALSE);
          }
          if ($individual_owner_check) {
            $this->addIndividualConditions([$group->id()], $owner_conditions);
          }
        }
        elseif ($operation_supports_status) {
          $statuses_to_check = [];

          foreach (['unpublished', 'published'] as $status => $pub_key) {
            foreach (['individual', 'outsider', 'insider'] as $key) {
              if (${$key . '_' . $pub_key . '_simple_check'} || ${$key . '_' . $pub_key . '_owner_check'}) {
                $statuses_to_check[] = $status;
                continue 2;
              }
            }
          }

          if (count($statuses_to_check) > 1) {
            $scope_conditions = $this->ensureOrConjunction($scope_conditions);
          }

          $status_key = $definition->getKey('published');
          foreach ($statuses_to_check as $status) {
            $variable_key = $status ? 'published' : 'unpublished';

            $scope_conditions->condition($status_group = $control->andConditionGroup());
            $status_group->condition("$data_table.$status_key", $status);
            $status_group->condition($status_conditions = $control->orConditionGroup());

            if (${'outsider_' . $variable_key . '_simple_check'}) {
              $this->addSynchronizedConditions([$group_type->id()], $status_conditions, TRUE);
            }
            if (${'insider_' . $variable_key . '_simple_check'}) {
              $this->addSynchronizedConditions([$group_type->id()], $status_conditions, FALSE);
            }
            if (${'individual_' . $variable_key . '_simple_check'}) {
              $this->addIndividualConditions([$group->id()], $status_conditions);
            }

            if (${'individual_' . $variable_key . '_owner_check'}
              || ${'outsider_' . $variable_key . '_owner_check'}
              || ${'insider_' . $variable_key . '_owner_check'}
            ) {
              $owner_key = $definition->getKey('owner');
              $status_conditions->condition($owner_group = $control->andConditionGroup());
              $owner_group->condition("$data_table.$owner_key", $this->getCurrentUser()->id());
              $owner_group->condition($owner_conditions = $control->orConditionGroup());

              if (${'outsider_' . $variable_key . '_owner_check'}) {
                $this->addSynchronizedConditions([$group_type->id()], $owner_conditions, TRUE);
              }
              if (${'insider_' . $variable_key . '_owner_check'}) {
                $this->addSynchronizedConditions([$group_type->id()], $owner_conditions, FALSE);
              }
              if (${'individual_' . $variable_key . '_owner_check'}) {
                $this->addIndividualConditions([$group->id()], $owner_conditions);
              }
            }
          }
        }
      }
    }

    $this->assertEqualsCanonicalizing($control->getTables(), $query->getTables());
    $this->assertEqualsCanonicalizing($control->conditions(), $query->conditions());
    $this->assertSame($control->__toString(), $query->__toString());
  }

  /**
   * Data provider for testQueryAccess().
   *
   * @return array
   *   A list of testQueryAccess method arguments.
   */
  public function queryAccessProvider() {
    foreach (['view', 'update', 'delete', 'unsupported'] as $operation) {
      $operation_supports_status = $this->isPublishable && $operation === 'view';

      if ($this->relationshipsAffectAccess) {
        // Case when there is no relationship for the entity type.
        $cases["no-relationships-$operation"] = [
          'operation' => $operation,
          'operation_supported' => $operation !== 'unsupported',
          'operation_supports_status' => $operation_supports_status,
          'has_relationships' => FALSE,
          'has_access' => FALSE,
          'joins_member_table' => FALSE,
          'joins_data_table' => FALSE,
          'individual_permissions' => [],
          'individual_is_admin' => FALSE,
          'individual_simple_check' => FALSE,
          'individual_owner_check' => FALSE,
          'individual_published_simple_check' => FALSE,
          'individual_published_owner_check' => FALSE,
          'individual_unpublished_simple_check' => FALSE,
          'individual_unpublished_owner_check' => FALSE,
          'outsider_permissions' => [],
          'outsider_is_admin' => FALSE,
          'outsider_simple_check' => FALSE,
          'outsider_owner_check' => FALSE,
          'outsider_published_simple_check' => FALSE,
          'outsider_published_owner_check' => FALSE,
          'outsider_unpublished_simple_check' => FALSE,
          'outsider_unpublished_owner_check' => FALSE,
          'insider_permissions' => [],
          'insider_is_admin' => FALSE,
          'insider_simple_check' => FALSE,
          'insider_owner_check' => FALSE,
          'insider_published_simple_check' => FALSE,
          'insider_published_owner_check' => FALSE,
          'insider_unpublished_simple_check' => FALSE,
          'insider_unpublished_owner_check' => FALSE,
        ];
      }

      // Case when nothing grants access.
      $cases["no-access-$operation"] = [
        'operation' => $operation,
        'operation_supported' => $operation !== 'unsupported',
        'operation_supports_status' => $operation_supports_status,
        'has_relationships' => TRUE,
        'has_access' => FALSE,
        'joins_member_table' => FALSE,
        'joins_data_table' => FALSE,
        'individual_permissions' => [],
        'individual_is_admin' => FALSE,
        'individual_simple_check' => FALSE,
        'individual_owner_check' => FALSE,
        'individual_published_simple_check' => FALSE,
        'individual_published_owner_check' => FALSE,
        'individual_unpublished_simple_check' => FALSE,
        'individual_unpublished_owner_check' => FALSE,
        'outsider_permissions' => [],
        'outsider_is_admin' => FALSE,
        'outsider_simple_check' => FALSE,
        'outsider_owner_check' => FALSE,
        'outsider_published_simple_check' => FALSE,
        'outsider_published_owner_check' => FALSE,
        'outsider_unpublished_simple_check' => FALSE,
        'outsider_unpublished_owner_check' => FALSE,
        'insider_permissions' => [],
        'insider_is_admin' => FALSE,
        'insider_simple_check' => FALSE,
        'insider_owner_check' => FALSE,
        'insider_published_simple_check' => FALSE,
        'insider_published_owner_check' => FALSE,
        'insider_unpublished_simple_check' => FALSE,
        'insider_unpublished_owner_check' => FALSE,
      ];

      // Single any vs own access for outsider, insider and individual.
      $single_base = [
        'operation' => $operation,
        'operation_supported' => $operation !== 'unsupported',
        'operation_supports_status' => $operation_supports_status,
        'has_relationships' => TRUE,
        'has_access' => TRUE,
        'joins_member_table' => FALSE,
        'joins_data_table' => FALSE,
        'individual_permissions' => [],
        'individual_is_admin' => FALSE,
        'individual_simple_check' => FALSE,
        'individual_owner_check' => FALSE,
        'individual_published_simple_check' => FALSE,
        'individual_published_owner_check' => FALSE,
        'individual_unpublished_simple_check' => FALSE,
        'individual_unpublished_owner_check' => FALSE,
        'outsider_permissions' => [],
        'outsider_is_admin' => FALSE,
        'outsider_simple_check' => FALSE,
        'outsider_owner_check' => FALSE,
        'outsider_published_simple_check' => FALSE,
        'outsider_published_owner_check' => FALSE,
        'outsider_unpublished_simple_check' => FALSE,
        'outsider_unpublished_owner_check' => FALSE,
        'insider_permissions' => [],
        'insider_is_admin' => FALSE,
        'insider_simple_check' => FALSE,
        'insider_owner_check' => FALSE,
        'insider_published_simple_check' => FALSE,
        'insider_published_owner_check' => FALSE,
        'insider_unpublished_simple_check' => FALSE,
        'insider_unpublished_owner_check' => FALSE,
      ];

      // Add the own permission (if applicable) to prove it's never checked.
      $single_permissions = [$this->getPermission($operation, 'any')];
      if ($this->isOwnable) {
        $single_permissions[] = $this->getPermission($operation, 'own');
      }
      $single_permissions = array_filter($single_permissions);

      // Do the same for unpublished, if applicable.
      if ($operation_supports_status) {
        $unpublished_permissions = [$this->getPermission($operation, 'any', TRUE)];
        if ($this->isOwnable) {
          $unpublished_permissions[] = $this->getPermission($operation, 'own', TRUE);
        }
        $unpublished_permissions = array_filter($unpublished_permissions);
      }

      // Check if there is an admin permission.
      $admin_permission = $this->getAdminPermission();

      // Single scope cases.
      foreach (['outsider', 'insider', 'individual'] as $copy_key) {
        $scope_base = $single_base;
        $scope_base['joins_member_table'] = $copy_key !== 'individual';

        if (!$operation_supports_status) {
          $cases["single-$copy_key-any-$operation"] = $scope_base;
          $cases["single-$copy_key-any-$operation"]["{$copy_key}_permissions"] = $single_permissions;
          $cases["single-$copy_key-any-$operation"]["{$copy_key}_simple_check"] = TRUE;

          if ($this->isOwnable) {
            if ($own_permission = $this->getPermission($operation, 'own')) {
              $cases["single-$copy_key-own-$operation"] = $scope_base;
              $cases["single-$copy_key-own-$operation"]["{$copy_key}_permissions"] = [$own_permission];
              $cases["single-$copy_key-own-$operation"]["{$copy_key}_owner_check"] = TRUE;
              $cases["single-$copy_key-own-$operation"]['joins_data_table'] = TRUE;
            }
          }
        }
        else {
          $status_base = $scope_base;
          $status_base['joins_data_table'] = TRUE;

          $cases["single-$copy_key-any-published-$operation"] = $status_base;
          $cases["single-$copy_key-any-published-$operation"]["{$copy_key}_permissions"] = $single_permissions;
          $cases["single-$copy_key-any-published-$operation"]["{$copy_key}_published_simple_check"] = TRUE;

          $cases["single-$copy_key-any-unpublished-$operation"] = $status_base;
          $cases["single-$copy_key-any-unpublished-$operation"]["{$copy_key}_permissions"] = $unpublished_permissions;
          $cases["single-$copy_key-any-unpublished-$operation"]["{$copy_key}_unpublished_simple_check"] = TRUE;

          $cases["single-$copy_key-any-mixed_published-$operation"] = $status_base;
          $cases["single-$copy_key-any-mixed_published-$operation"]["{$copy_key}_permissions"] = array_merge($single_permissions, $unpublished_permissions);
          $cases["single-$copy_key-any-mixed_published-$operation"]["{$copy_key}_published_simple_check"] = TRUE;
          $cases["single-$copy_key-any-mixed_published-$operation"]["{$copy_key}_unpublished_simple_check"] = TRUE;

          if ($this->isOwnable) {
            $pub_permission = $this->getPermission($operation, 'own');
            $unpublished_permission = $this->getPermission($operation, 'own', TRUE);

            if ($pub_permission) {
              $cases["single-$copy_key-own-published-$operation"] = $status_base;
              $cases["single-$copy_key-own-published-$operation"]["{$copy_key}_permissions"] = [$pub_permission];
              $cases["single-$copy_key-own-published-$operation"]["{$copy_key}_published_owner_check"] = TRUE;
            }

            if ($unpublished_permission) {
              $cases["single-$copy_key-own-unpublished-$operation"] = $status_base;
              $cases["single-$copy_key-own-unpublished-$operation"]["{$copy_key}_permissions"] = [$unpublished_permission];
              $cases["single-$copy_key-own-unpublished-$operation"]["{$copy_key}_unpublished_owner_check"] = TRUE;
            }

            if ($pub_permission && $unpublished_permission) {
              $cases["single-$copy_key-own-mixed_published-$operation"] = $status_base;
              $cases["single-$copy_key-own-mixed_published-$operation"]["{$copy_key}_permissions"] = [
                $pub_permission,
                $unpublished_permission,
              ];
              $cases["single-$copy_key-own-mixed_published-$operation"]["{$copy_key}_published_owner_check"] = TRUE;
              $cases["single-$copy_key-own-mixed_published-$operation"]["{$copy_key}_unpublished_owner_check"] = TRUE;
            }
          }
        }

        // Single admin access for outsider, insider and individual. Please note
        // admin access does not need to check for status nor ownership.
        $cases["single-admin-$copy_key-$operation"] = $scope_base;
        $cases["single-admin-$copy_key-$operation"]['joins_data_table'] = FALSE;
        $cases["single-admin-$copy_key-$operation"]["{$copy_key}_is_admin"] = TRUE;
        $cases["single-admin-$copy_key-$operation"]["{$copy_key}_simple_check"] = TRUE;

        // Admin permission access for outsider, insider and individual. Behaves
        // the same as the admin flag, but only when permission is supported.
        if ($admin_permission) {
          $cases["single-admin_permission-$copy_key-$operation"] = $cases["single-admin-$copy_key-$operation"];
          $cases["single-admin_permission-$copy_key-$operation"]["{$copy_key}_is_admin"] = FALSE;
          $cases["single-admin_permission-$copy_key-$operation"]["{$copy_key}_permissions"] = array_merge([$admin_permission], $single_permissions);
        }
      }

      // Mixed scope cases.
      if (!$operation_supports_status) {
        $cases["mixed-outsider-insider-any-" . $operation] = $cases["single-outsider-any-$operation"];
        $cases["mixed-outsider-insider-any-" . $operation]['insider_permissions'] = $single_permissions;
        $cases["mixed-outsider-insider-any-" . $operation]['insider_simple_check'] = TRUE;
        $cases["mixed-outsider-individual-any-" . $operation] = $cases["single-outsider-any-$operation"];
        $cases["mixed-outsider-individual-any-" . $operation]['individual_permissions'] = $single_permissions;
        $cases["mixed-outsider-individual-any-" . $operation]['individual_simple_check'] = TRUE;
        $cases["mixed-insider-individual-any-" . $operation] = $cases["single-insider-any-$operation"];
        $cases["mixed-insider-individual-any-" . $operation]['individual_permissions'] = $single_permissions;
        $cases["mixed-insider-individual-any-" . $operation]['individual_simple_check'] = TRUE;

        $cases["mixed-outsider-insider_admin-any-" . $operation] = $cases["single-outsider-any-$operation"];
        $cases["mixed-outsider-insider_admin-any-" . $operation]['insider_is_admin'] = TRUE;
        $cases["mixed-outsider-insider_admin-any-" . $operation]['insider_simple_check'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-" . $operation] = $cases["single-outsider-any-$operation"];
        $cases["mixed-outsider-individual_admin-any-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-" . $operation]['individual_simple_check'] = TRUE;
        $cases["mixed-insider-individual_admin-any-" . $operation] = $cases["single-insider-any-$operation"];
        $cases["mixed-insider-individual_admin-any-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-insider-individual_admin-any-" . $operation]['individual_simple_check'] = TRUE;

        if ($admin_permission) {
          // Add in regular permissions to prove they aren't checked.
          $admin_permissions = array_merge([$admin_permission], $single_permissions);

          $cases["mixed-outsider-insider_admin_permission-any-" . $operation] = $cases["mixed-outsider-insider_admin-any-" . $operation];
          $cases["mixed-outsider-insider_admin_permission-any-" . $operation]['insider_is_admin'] = FALSE;
          $cases["mixed-outsider-insider_admin_permission-any-" . $operation]['insider_permissions'] = $admin_permissions;
          $cases["mixed-outsider-individual_admin_permission-any-" . $operation] = $cases["mixed-outsider-individual_admin-any-" . $operation];
          $cases["mixed-outsider-individual_admin_permission-any-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-outsider-individual_admin_permission-any-" . $operation]['individual_permissions'] = $admin_permissions;
          $cases["mixed-insider-individual_admin_permission-any-" . $operation] = $cases["mixed-insider-individual_admin-any-" . $operation];
          $cases["mixed-insider-individual_admin_permission-any-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-insider-individual_admin_permission-any-" . $operation]['individual_permissions'] = $admin_permissions;
        }

        if ($this->isOwnable && ($own_permission = $this->getPermission($operation, 'own'))) {
          $cases["mixed-outsider-insider-own-" . $operation] = $cases["single-outsider-own-$operation"];
          $cases["mixed-outsider-insider-own-" . $operation]['insider_permissions'] = [$own_permission];
          $cases["mixed-outsider-insider-own-" . $operation]['insider_owner_check'] = TRUE;
          $cases["mixed-outsider-individual-own-" . $operation] = $cases["single-outsider-own-$operation"];
          $cases["mixed-outsider-individual-own-" . $operation]['individual_permissions'] = [$own_permission];
          $cases["mixed-outsider-individual-own-" . $operation]['individual_owner_check'] = TRUE;
          $cases["mixed-insider-individual-own-" . $operation] = $cases["single-insider-own-$operation"];
          $cases["mixed-insider-individual-own-" . $operation]['individual_permissions'] = [$own_permission];
          $cases["mixed-insider-individual-own-" . $operation]['individual_owner_check'] = TRUE;

          $cases["mixed-outsider-insider_admin-own-" . $operation] = $cases["single-outsider-own-$operation"];
          $cases["mixed-outsider-insider_admin-own-" . $operation]['insider_is_admin'] = TRUE;
          $cases["mixed-outsider-insider_admin-own-" . $operation]['insider_simple_check'] = TRUE;
          $cases["mixed-outsider-individual_admin-own-" . $operation] = $cases["single-outsider-own-$operation"];
          $cases["mixed-outsider-individual_admin-own-" . $operation]['individual_is_admin'] = TRUE;
          $cases["mixed-outsider-individual_admin-own-" . $operation]['individual_simple_check'] = TRUE;
          $cases["mixed-insider-individual_admin-own-" . $operation] = $cases["single-insider-own-$operation"];
          $cases["mixed-insider-individual_admin-own-" . $operation]['individual_is_admin'] = TRUE;
          $cases["mixed-insider-individual_admin-own-" . $operation]['individual_simple_check'] = TRUE;

          if ($admin_permission) {
            // Add in regular permissions to prove they aren't checked.
            $admin_permissions = array_merge([$admin_permission], $single_permissions);

            $cases["mixed-outsider-insider_admin_permission-own-" . $operation] = $cases["mixed-outsider-insider_admin-own-" . $operation];
            $cases["mixed-outsider-insider_admin_permission-own-" . $operation]['insider_is_admin'] = FALSE;
            $cases["mixed-outsider-insider_admin_permission-own-" . $operation]['insider_permissions'] = $admin_permissions;
            $cases["mixed-outsider-individual_admin_permission-own-" . $operation] = $cases["mixed-outsider-individual_admin-own-" . $operation];
            $cases["mixed-outsider-individual_admin_permission-own-" . $operation]['individual_is_admin'] = FALSE;
            $cases["mixed-outsider-individual_admin_permission-own-" . $operation]['individual_permissions'] = $admin_permissions;
            $cases["mixed-insider-individual_admin_permission-own-" . $operation] = $cases["mixed-insider-individual_admin-own-" . $operation];
            $cases["mixed-insider-individual_admin_permission-own-" . $operation]['individual_is_admin'] = FALSE;
            $cases["mixed-insider-individual_admin_permission-own-" . $operation]['individual_permissions'] = $admin_permissions;
          }
        }
      }
      else {
        $cases["mixed-outsider-insider-any-published-" . $operation] = $cases["single-outsider-any-published-$operation"];
        $cases["mixed-outsider-insider-any-published-" . $operation]['insider_permissions'] = $single_permissions;
        $cases["mixed-outsider-insider-any-published-" . $operation]['insider_published_simple_check'] = TRUE;
        $cases["mixed-outsider-individual-any-published-" . $operation] = $cases["single-outsider-any-published-$operation"];
        $cases["mixed-outsider-individual-any-published-" . $operation]['individual_permissions'] = $single_permissions;
        $cases["mixed-outsider-individual-any-published-" . $operation]['individual_published_simple_check'] = TRUE;
        $cases["mixed-insider-individual-any-published-" . $operation] = $cases["single-insider-any-published-$operation"];
        $cases["mixed-insider-individual-any-published-" . $operation]['individual_permissions'] = $single_permissions;
        $cases["mixed-insider-individual-any-published-" . $operation]['individual_published_simple_check'] = TRUE;

        $cases["mixed-outsider-insider-any-unpublished-" . $operation] = $cases["single-outsider-any-unpublished-$operation"];
        $cases["mixed-outsider-insider-any-unpublished-" . $operation]['insider_permissions'] = $unpublished_permissions;
        $cases["mixed-outsider-insider-any-unpublished-" . $operation]['insider_unpublished_simple_check'] = TRUE;
        $cases["mixed-outsider-individual-any-unpublished-" . $operation] = $cases["single-outsider-any-unpublished-$operation"];
        $cases["mixed-outsider-individual-any-unpublished-" . $operation]['individual_permissions'] = $unpublished_permissions;
        $cases["mixed-outsider-individual-any-unpublished-" . $operation]['individual_unpublished_simple_check'] = TRUE;
        $cases["mixed-insider-individual-any-unpublished-" . $operation] = $cases["single-insider-any-unpublished-$operation"];
        $cases["mixed-insider-individual-any-unpublished-" . $operation]['individual_permissions'] = $unpublished_permissions;
        $cases["mixed-insider-individual-any-unpublished-" . $operation]['individual_unpublished_simple_check'] = TRUE;

        $cases["mixed-outsider-insider-any-mixed_published-" . $operation] = $cases["single-outsider-any-mixed_published-$operation"];
        $cases["mixed-outsider-insider-any-mixed_published-" . $operation]['insider_permissions'] = array_merge($single_permissions, $unpublished_permissions);
        $cases["mixed-outsider-insider-any-mixed_published-" . $operation]['insider_published_simple_check'] = TRUE;
        $cases["mixed-outsider-insider-any-mixed_published-" . $operation]['insider_unpublished_simple_check'] = TRUE;
        $cases["mixed-outsider-individual-any-mixed_published-" . $operation] = $cases["single-outsider-any-mixed_published-$operation"];
        $cases["mixed-outsider-individual-any-mixed_published-" . $operation]['individual_permissions'] = array_merge($single_permissions, $unpublished_permissions);
        $cases["mixed-outsider-individual-any-mixed_published-" . $operation]['individual_published_simple_check'] = TRUE;
        $cases["mixed-outsider-individual-any-mixed_published-" . $operation]['individual_unpublished_simple_check'] = TRUE;
        $cases["mixed-insider-individual-any-mixed_published-" . $operation] = $cases["single-insider-any-mixed_published-$operation"];
        $cases["mixed-insider-individual-any-mixed_published-" . $operation]['individual_permissions'] = array_merge($single_permissions, $unpublished_permissions);
        $cases["mixed-insider-individual-any-mixed_published-" . $operation]['individual_published_simple_check'] = TRUE;
        $cases["mixed-insider-individual-any-mixed_published-" . $operation]['individual_unpublished_simple_check'] = TRUE;

        $cases["mixed-outsider-insider_admin-any-published-" . $operation] = $cases["single-outsider-any-published-$operation"];
        $cases["mixed-outsider-insider_admin-any-published-" . $operation]['insider_is_admin'] = TRUE;
        $cases["mixed-outsider-insider_admin-any-published-" . $operation]['insider_simple_check'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-published-" . $operation] = $cases["single-outsider-any-published-$operation"];
        $cases["mixed-outsider-individual_admin-any-published-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-published-" . $operation]['individual_simple_check'] = TRUE;
        $cases["mixed-insider-individual_admin-any-published-" . $operation] = $cases["single-insider-any-published-$operation"];
        $cases["mixed-insider-individual_admin-any-published-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-insider-individual_admin-any-published-" . $operation]['individual_simple_check'] = TRUE;

        $cases["mixed-outsider-insider_admin-any-unpublished-" . $operation] = $cases["single-outsider-any-unpublished-$operation"];
        $cases["mixed-outsider-insider_admin-any-unpublished-" . $operation]['insider_is_admin'] = TRUE;
        $cases["mixed-outsider-insider_admin-any-unpublished-" . $operation]['insider_simple_check'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-unpublished-" . $operation] = $cases["single-outsider-any-unpublished-$operation"];
        $cases["mixed-outsider-individual_admin-any-unpublished-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-unpublished-" . $operation]['individual_simple_check'] = TRUE;
        $cases["mixed-insider-individual_admin-any-unpublished-" . $operation] = $cases["single-insider-any-unpublished-$operation"];
        $cases["mixed-insider-individual_admin-any-unpublished-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-insider-individual_admin-any-unpublished-" . $operation]['individual_simple_check'] = TRUE;

        $cases["mixed-outsider-insider_admin-any-mixed_published-" . $operation] = $cases["single-outsider-any-mixed_published-$operation"];
        $cases["mixed-outsider-insider_admin-any-mixed_published-" . $operation]['insider_is_admin'] = TRUE;
        $cases["mixed-outsider-insider_admin-any-mixed_published-" . $operation]['insider_simple_check'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-mixed_published-" . $operation] = $cases["single-outsider-any-mixed_published-$operation"];
        $cases["mixed-outsider-individual_admin-any-mixed_published-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-outsider-individual_admin-any-mixed_published-" . $operation]['individual_simple_check'] = TRUE;
        $cases["mixed-insider-individual_admin-any-mixed_published-" . $operation] = $cases["single-insider-any-mixed_published-$operation"];
        $cases["mixed-insider-individual_admin-any-mixed_published-" . $operation]['individual_is_admin'] = TRUE;
        $cases["mixed-insider-individual_admin-any-mixed_published-" . $operation]['individual_simple_check'] = TRUE;

        if ($admin_permission) {
          // Add in regular permissions to prove they aren't checked.
          $admin_permissions = array_merge([$admin_permission], $single_permissions);

          $cases["mixed-outsider-insider_admin_permission-any-published-" . $operation] = $cases["mixed-outsider-insider_admin-any-published-" . $operation];
          $cases["mixed-outsider-insider_admin_permission-any-published-" . $operation]['insider_is_admin'] = FALSE;
          $cases["mixed-outsider-insider_admin_permission-any-published-" . $operation]['insider_permissions'] = $admin_permissions;
          $cases["mixed-outsider-individual_admin_permission-any-published-" . $operation] = $cases["mixed-outsider-individual_admin-any-published-" . $operation];
          $cases["mixed-outsider-individual_admin_permission-any-published-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-outsider-individual_admin_permission-any-published-" . $operation]['individual_permissions'] = $admin_permissions;
          $cases["mixed-insider-individual_admin_permission-any-published-" . $operation] = $cases["mixed-insider-individual_admin-any-published-" . $operation];
          $cases["mixed-insider-individual_admin_permission-any-published-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-insider-individual_admin_permission-any-published-" . $operation]['individual_permissions'] = $admin_permissions;

          $cases["mixed-outsider-insider_admin_permission-any-unpublished-" . $operation] = $cases["mixed-outsider-insider_admin-any-unpublished-" . $operation];
          $cases["mixed-outsider-insider_admin_permission-any-unpublished-" . $operation]['insider_is_admin'] = FALSE;
          $cases["mixed-outsider-insider_admin_permission-any-unpublished-" . $operation]['insider_permissions'] = $admin_permissions;
          $cases["mixed-outsider-individual_admin_permission-any-unpublished-" . $operation] = $cases["mixed-outsider-individual_admin-any-unpublished-" . $operation];
          $cases["mixed-outsider-individual_admin_permission-any-unpublished-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-outsider-individual_admin_permission-any-unpublished-" . $operation]['individual_permissions'] = $admin_permissions;
          $cases["mixed-insider-individual_admin_permission-any-unpublished-" . $operation] = $cases["mixed-insider-individual_admin-any-unpublished-" . $operation];
          $cases["mixed-insider-individual_admin_permission-any-unpublished-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-insider-individual_admin_permission-any-unpublished-" . $operation]['individual_permissions'] = $admin_permissions;

          $cases["mixed-outsider-insider_admin_permission-any-mixed_published-" . $operation] = $cases["mixed-outsider-insider_admin-any-mixed_published-" . $operation];
          $cases["mixed-outsider-insider_admin_permission-any-mixed_published-" . $operation]['insider_is_admin'] = FALSE;
          $cases["mixed-outsider-insider_admin_permission-any-mixed_published-" . $operation]['insider_permissions'] = $admin_permissions;
          $cases["mixed-outsider-individual_admin_permission-any-mixed_published-" . $operation] = $cases["mixed-outsider-individual_admin-any-mixed_published-" . $operation];
          $cases["mixed-outsider-individual_admin_permission-any-mixed_published-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-outsider-individual_admin_permission-any-mixed_published-" . $operation]['individual_permissions'] = $admin_permissions;
          $cases["mixed-insider-individual_admin_permission-any-mixed_published-" . $operation] = $cases["mixed-insider-individual_admin-any-mixed_published-" . $operation];
          $cases["mixed-insider-individual_admin_permission-any-mixed_published-" . $operation]['individual_is_admin'] = FALSE;
          $cases["mixed-insider-individual_admin_permission-any-mixed_published-" . $operation]['individual_permissions'] = $admin_permissions;
        }

        if ($this->isOwnable) {
          $own_published_permission = $this->getPermission($operation, 'own');
          $own_unpublished_permission = $this->getPermission($operation, 'own', TRUE);

          if ($own_published_permission) {
            $cases["mixed-outsider-insider-own-published-" . $operation] = $cases["single-outsider-own-published-$operation"];
            $cases["mixed-outsider-insider-own-published-" . $operation]['insider_permissions'] = [$own_published_permission];
            $cases["mixed-outsider-insider-own-published-" . $operation]['insider_published_owner_check'] = TRUE;
            $cases["mixed-outsider-individual-own-published-" . $operation] = $cases["single-outsider-own-published-$operation"];
            $cases["mixed-outsider-individual-own-published-" . $operation]['individual_permissions'] = [$own_published_permission];
            $cases["mixed-outsider-individual-own-published-" . $operation]['individual_published_owner_check'] = TRUE;
            $cases["mixed-insider-individual-own-published-" . $operation] = $cases["single-insider-own-published-$operation"];
            $cases["mixed-insider-individual-own-published-" . $operation]['individual_permissions'] = [$own_published_permission];
            $cases["mixed-insider-individual-own-published-" . $operation]['individual_published_owner_check'] = TRUE;

            $cases["mixed-outsider-insider_admin-own-published-" . $operation] = $cases["single-outsider-own-published-$operation"];
            $cases["mixed-outsider-insider_admin-own-published-" . $operation]['insider_is_admin'] = TRUE;
            $cases["mixed-outsider-insider_admin-own-published-" . $operation]['insider_simple_check'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-published-" . $operation] = $cases["single-outsider-own-published-$operation"];
            $cases["mixed-outsider-individual_admin-own-published-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-published-" . $operation]['individual_simple_check'] = TRUE;
            $cases["mixed-insider-individual_admin-own-published-" . $operation] = $cases["single-insider-own-published-$operation"];
            $cases["mixed-insider-individual_admin-own-published-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-insider-individual_admin-own-published-" . $operation]['individual_simple_check'] = TRUE;

            if ($admin_permission) {
              // Add in regular permissions to prove they aren't checked.
              $admin_permissions = [$admin_permission, $own_published_permission];

              $cases["mixed-outsider-insider_admin_permission-own-published-" . $operation] = $cases["mixed-outsider-insider_admin-own-published-" . $operation];
              $cases["mixed-outsider-insider_admin_permission-own-published-" . $operation]['insider_is_admin'] = FALSE;
              $cases["mixed-outsider-insider_admin_permission-own-published-" . $operation]['insider_permissions'] = $admin_permissions;
              $cases["mixed-outsider-individual_admin_permission-own-published-" . $operation] = $cases["mixed-outsider-individual_admin-own-published-" . $operation];
              $cases["mixed-outsider-individual_admin_permission-own-published-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-outsider-individual_admin_permission-own-published-" . $operation]['individual_permissions'] = $admin_permissions;
              $cases["mixed-insider-individual_admin_permission-own-published-" . $operation] = $cases["mixed-insider-individual_admin-own-published-" . $operation];
              $cases["mixed-insider-individual_admin_permission-own-published-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-insider-individual_admin_permission-own-published-" . $operation]['individual_permissions'] = $admin_permissions;
            }
          }

          if ($own_unpublished_permission) {
            $cases["mixed-outsider-insider-own-unpublished-" . $operation] = $cases["single-outsider-own-unpublished-$operation"];
            $cases["mixed-outsider-insider-own-unpublished-" . $operation]['insider_permissions'] = [$own_unpublished_permission];
            $cases["mixed-outsider-insider-own-unpublished-" . $operation]['insider_unpublished_owner_check'] = TRUE;
            $cases["mixed-outsider-individual-own-unpublished-" . $operation] = $cases["single-outsider-own-unpublished-$operation"];
            $cases["mixed-outsider-individual-own-unpublished-" . $operation]['individual_permissions'] = [$own_unpublished_permission];
            $cases["mixed-outsider-individual-own-unpublished-" . $operation]['individual_unpublished_owner_check'] = TRUE;
            $cases["mixed-insider-individual-own-unpublished-" . $operation] = $cases["single-insider-own-unpublished-$operation"];
            $cases["mixed-insider-individual-own-unpublished-" . $operation]['individual_permissions'] = [$own_unpublished_permission];
            $cases["mixed-insider-individual-own-unpublished-" . $operation]['individual_unpublished_owner_check'] = TRUE;

            $cases["mixed-outsider-insider_admin-own-unpublished-" . $operation] = $cases["single-outsider-own-unpublished-$operation"];
            $cases["mixed-outsider-insider_admin-own-unpublished-" . $operation]['insider_is_admin'] = TRUE;
            $cases["mixed-outsider-insider_admin-own-unpublished-" . $operation]['insider_simple_check'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-unpublished-" . $operation] = $cases["single-outsider-own-unpublished-$operation"];
            $cases["mixed-outsider-individual_admin-own-unpublished-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-unpublished-" . $operation]['individual_simple_check'] = TRUE;
            $cases["mixed-insider-individual_admin-own-unpublished-" . $operation] = $cases["single-insider-own-unpublished-$operation"];
            $cases["mixed-insider-individual_admin-own-unpublished-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-insider-individual_admin-own-unpublished-" . $operation]['individual_simple_check'] = TRUE;

            if ($admin_permission) {
              // Add in regular permissions to prove they aren't checked.
              $admin_permissions = [$admin_permission, $own_unpublished_permission];

              $cases["mixed-outsider-insider_admin_permission-own-unpublished-" . $operation] = $cases["mixed-outsider-insider_admin-own-unpublished-" . $operation];
              $cases["mixed-outsider-insider_admin_permission-own-unpublished-" . $operation]['insider_is_admin'] = FALSE;
              $cases["mixed-outsider-insider_admin_permission-own-unpublished-" . $operation]['insider_permissions'] = $admin_permissions;
              $cases["mixed-outsider-individual_admin_permission-own-unpublished-" . $operation] = $cases["mixed-outsider-individual_admin-own-unpublished-" . $operation];
              $cases["mixed-outsider-individual_admin_permission-own-unpublished-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-outsider-individual_admin_permission-own-unpublished-" . $operation]['individual_permissions'] = $admin_permissions;
              $cases["mixed-insider-individual_admin_permission-own-unpublished-" . $operation] = $cases["mixed-insider-individual_admin-own-unpublished-" . $operation];
              $cases["mixed-insider-individual_admin_permission-own-unpublished-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-insider-individual_admin_permission-own-unpublished-" . $operation]['individual_permissions'] = $admin_permissions;
            }
          }

          if ($own_published_permission && $own_unpublished_permission) {
            $own_mixed_published_permissions = [$own_published_permission, $own_unpublished_permission];
            $cases["mixed-outsider-insider-own-mixed_published-" . $operation] = $cases["single-outsider-own-mixed_published-$operation"];
            $cases["mixed-outsider-insider-own-mixed_published-" . $operation]['insider_permissions'] = $own_mixed_published_permissions;
            $cases["mixed-outsider-insider-own-mixed_published-" . $operation]['insider_published_owner_check'] = TRUE;
            $cases["mixed-outsider-insider-own-mixed_published-" . $operation]['insider_unpublished_owner_check'] = TRUE;
            $cases["mixed-outsider-individual-own-mixed_published-" . $operation] = $cases["single-outsider-own-mixed_published-$operation"];
            $cases["mixed-outsider-individual-own-mixed_published-" . $operation]['individual_permissions'] = $own_mixed_published_permissions;
            $cases["mixed-outsider-individual-own-mixed_published-" . $operation]['individual_published_owner_check'] = TRUE;
            $cases["mixed-outsider-individual-own-mixed_published-" . $operation]['individual_unpublished_owner_check'] = TRUE;
            $cases["mixed-insider-individual-own-mixed_published-" . $operation] = $cases["single-insider-own-mixed_published-$operation"];
            $cases["mixed-insider-individual-own-mixed_published-" . $operation]['individual_permissions'] = $own_mixed_published_permissions;
            $cases["mixed-insider-individual-own-mixed_published-" . $operation]['individual_published_owner_check'] = TRUE;
            $cases["mixed-insider-individual-own-mixed_published-" . $operation]['individual_unpublished_owner_check'] = TRUE;

            $cases["mixed-outsider-insider_admin-own-mixed_published-" . $operation] = $cases["single-outsider-own-mixed_published-$operation"];
            $cases["mixed-outsider-insider_admin-own-mixed_published-" . $operation]['insider_is_admin'] = TRUE;
            $cases["mixed-outsider-insider_admin-own-mixed_published-" . $operation]['insider_simple_check'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-mixed_published-" . $operation] = $cases["single-outsider-own-mixed_published-$operation"];
            $cases["mixed-outsider-individual_admin-own-mixed_published-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-outsider-individual_admin-own-mixed_published-" . $operation]['individual_simple_check'] = TRUE;
            $cases["mixed-insider-individual_admin-own-mixed_published-" . $operation] = $cases["single-insider-own-mixed_published-$operation"];
            $cases["mixed-insider-individual_admin-own-mixed_published-" . $operation]['individual_is_admin'] = TRUE;
            $cases["mixed-insider-individual_admin-own-mixed_published-" . $operation]['individual_simple_check'] = TRUE;

            if ($admin_permission) {
              // Add in regular permissions to prove they aren't checked.
              $admin_permissions = array_merge([$admin_permission], $own_mixed_published_permissions);

              $cases["mixed-outsider-insider_admin_permission-own-mixed_published-" . $operation] = $cases["mixed-outsider-insider_admin-own-mixed_published-" . $operation];
              $cases["mixed-outsider-insider_admin_permission-own-mixed_published-" . $operation]['insider_is_admin'] = FALSE;
              $cases["mixed-outsider-insider_admin_permission-own-mixed_published-" . $operation]['insider_permissions'] = $admin_permissions;
              $cases["mixed-outsider-individual_admin_permission-own-mixed_published-" . $operation] = $cases["mixed-outsider-individual_admin-own-mixed_published-" . $operation];
              $cases["mixed-outsider-individual_admin_permission-own-mixed_published-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-outsider-individual_admin_permission-own-mixed_published-" . $operation]['individual_permissions'] = $admin_permissions;
              $cases["mixed-insider-individual_admin_permission-own-mixed_published-" . $operation] = $cases["mixed-insider-individual_admin-own-mixed_published-" . $operation];
              $cases["mixed-insider-individual_admin_permission-own-mixed_published-" . $operation]['individual_is_admin'] = FALSE;
              $cases["mixed-insider-individual_admin_permission-own-mixed_published-" . $operation]['individual_permissions'] = $admin_permissions;
            }
          }
        }
      }
      // @todo Mixed any-own published-unpublished.
      //   E.g.: view group + view own unpublished group.
    }

    return $cases;
  }

  /**
   * Gets the permission name for the given operation and scope.
   *
   * @param string $operation
   *   The operation.
   * @param string $scope
   *   The operation scope (any or own).
   * @param bool $unpublished
   *   Whether to check for the unpublished permission. Defaults to FALSE.
   *
   * @return string
   *   The permission name.
   */
  abstract protected function getPermission($operation, $scope, $unpublished = FALSE);

  /**
   * Gets the admin permission name.
   *
   * @return string|false
   *   The admin permission name or FALSE if there is none.
   */
  abstract protected function getAdminPermission();

  /**
   * Builds and returns a query that will be altered.
   *
   * @param string $operation
   *   The operation for the query.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   *   The alterable query.
   */
  protected function createAlterableQuery($operation) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $query = \Drupal::database()->select($entity_type->getBaseTable());
    $query->addMetaData('op', $operation);
    $query->addMetaData('entity_type', $this->entityTypeId);
    return $query;
  }

  /**
   * Alters the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to alter.
   */
  protected function alterQuery(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    \Drupal::service('class_resolver')
      ->getInstanceFromDefinition($this->getAlterClass())
      ->alter($query, $entity_type);
  }

  /**
   * Retrieves the namespaced alter class name.
   *
   * @return string
   *   The namespaced alter class name.
   */
  abstract protected function getAlterClass();

  /**
   * Makes sure a ConditionInterface has the OR conjunction.
   *
   * @param \Drupal\Core\Database\Query\ConditionInterface $parent
   *   The parent ConditionInterface to potentially add the OR group to.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   An OR condition group attached to the parent in case the parent did not
   *   already use said conjunction or the passed in parent if it did.
   */
  protected function ensureOrConjunction(ConditionInterface $parent) {
    $conditions_array = $parent->conditions();
    if ($conditions_array['#conjunction'] === 'OR') {
      return $parent;
    }

    $parent->condition($scope_conditions = $parent->orConditionGroup());
    return $scope_conditions;
  }

  /**
   * Joins any extra tables required for access checks.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join(s) to.
   */
  protected function joinExtraTables(SelectInterface $query) {}

  /**
   * Joins the target entity data table.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinTargetEntityDataTable(SelectInterface $query) {
    $entity_type = $this->entityTypeManager->getDefinition($this->entityTypeId);
    $base_table = $entity_type->getBaseTable();
    $data_table = $entity_type->getDataTable();
    $id_key = $entity_type->getKey('id');
    $query->join(
      $data_table,
      $data_table,
      "$base_table.$id_key=$data_table.$id_key",
    );
  }

  /**
   * Joins the relationship field data table for memberships.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the join to.
   */
  protected function joinMemberships(SelectInterface $query) {
    $table = $this->getMembershipJoinTable();
    $l_field = $this->getMembershipJoinLeftField();

    $query->leftJoin(
      'group_relationship_field_data',
      'gcfd',
      "$table.$l_field=%alias.gid AND %alias.plugin_id='group_membership' AND %alias.entity_id=:account_id",
      [':account_id' => $this->getCurrentUser()->id()]
    );
  }

  /**
   * Retrieves the name of the table to join the memberships against.
   *
   * @return string
   *   The table name.
   */
  abstract protected function getMembershipJoinTable();

  /**
   * Retrieves the name of the field to join the memberships against.
   *
   * This should represent the  group IDs to check for membership against.
   *
   * @return string
   *   The field name.
   */
  abstract protected function getMembershipJoinLeftField();

  /**
   * Sets up the content for testing.
   *
   * @param \Drupal\group\Entity\GroupTypeInterface $group_type
   *   The group type to create a group with content for.
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group containing the content.
   */
  abstract protected function setUpContent(GroupTypeInterface $group_type);

  /**
   * Adds a no access conditions to the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the access check to.
   */
  abstract protected function addNoAccessConditions(SelectInterface $query);

  /**
   * Adds and returns a wrapper condition group if necessary.
   *
   * This method allows subclasses to make more complex groups at the top level
   * of the query conditions.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query to add the condition group to.
   *
   * @return \Drupal\Core\Database\Query\ConditionInterface
   *   The query or wrapper condition group.
   */
  protected function addWrapperConditionGroup(SelectInterface $query) {
    return $query;
  }

  /**
   * Adds conditions for the synchronized outsider scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *   The condition group to add the access checks to.
   * @param bool $outsider
   *   Whether the synchronized scope is outsider (TRUE) or insider (FALSE).
   */
  abstract protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $conditions, $outsider);

  /**
   * Adds conditions for the individual scope.
   *
   * @param array $allowed_ids
   *   The IDs to grant access to.
   * @param \Drupal\Core\Database\Query\ConditionInterface $conditions
   *   The condition group to add the access checks to.
   */
  abstract protected function addIndividualConditions(array $allowed_ids, ConditionInterface $conditions);

}
