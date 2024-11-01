<?php

namespace Drupal\group\QueryAccess;

use Drupal\Core\Database\Query\ConditionInterface;
use Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface;
use Drupal\group\PermissionScopeInterface;

/**
 * Base class for query alter classes that rely on plugin based access.
 *
 * @internal
 */
abstract class PluginBasedQueryAlterBase extends QueryAlterBase {

  /**
   * Retrieves the relationship table that want to filter by plugin on.
   *
   * @return string
   *   The table name.
   */
  abstract protected function getPluginDataTable();

  /**
   * {@inheritdoc}
   */
  protected function addSynchronizedConditions(array $allowed_ids, ConditionInterface $scope_conditions, $scope) {
    $data_table = $this->getPluginDataTable();
    $membership_alias = $this->ensureMembershipJoin();

    // A list of plugin IDs and group types can be optimized into a list of
    // group relationship type IDs. This to avoid having to add an IN query per
    // plugin ID as seen in ::addIndividualConditions().
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    assert($storage instanceof GroupRelationshipTypeStorageInterface);

    $group_relationship_type_ids = [];
    foreach ($allowed_ids as $plugin_id => $group_type_ids) {
      foreach (array_unique($group_type_ids) as $group_type_id) {
        $group_relationship_type_ids[] = $storage->getRelationshipTypeId($group_type_id, $plugin_id);
      }
    }

    $scope_conditions->condition($sub_condition = $this->query->andConditionGroup());
    $sub_condition->condition("$data_table.type", $group_relationship_type_ids, 'IN');
    if ($scope === PermissionScopeInterface::OUTSIDER_ID) {
      $sub_condition->isNull("$membership_alias.entity_id");
    }
    else {
      $sub_condition->isNotNull("$membership_alias.entity_id");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function addIndividualConditions(array $allowed_ids, ConditionInterface $scope_conditions) {
    $data_table = $this->getPluginDataTable();

    foreach ($allowed_ids as $plugin_id => $identifiers) {
      $sub_condition = $this->query->andConditionGroup();
      $sub_condition->condition("$data_table.gid", array_unique($identifiers), 'IN');
      $sub_condition->condition("$data_table.plugin_id", $plugin_id);
      $scope_conditions->condition($sub_condition);
    }
  }

}
