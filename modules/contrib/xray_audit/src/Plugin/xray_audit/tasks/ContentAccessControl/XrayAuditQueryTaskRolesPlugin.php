<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentAccessControl;

use Drupal\user\Entity\Role;
use Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric\XrayAuditQueryTaskPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_roles",
 *   label = @Translation("Role reports"),
 *   description = @Translation("Reports about roles and permissions."),
 *   group = "content_access_control",
 *   sort = 1,
 *   local_task = 1,
 *   operations = {
 *      "role_list" = {
 *          "label" = "Role list",
 *          "description" = ""
 *      },
 *      "role_permission" = {
 *          "label" = "List of Permissions per role",
 *          "description" = ""
 *       }
 *    },
 *   dependencies = {"user"}
 * )
 */
class XrayAuditQueryTaskRolesPlugin extends XrayAuditQueryTaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'role_list':
        return $this->rolesDescription();

      case 'role_permission':
        return $this->permissionsPerRole();
    }
    return [];
  }

  /**
   * Description roles.
   *
   * @return array
   *   Render array.
   */
  public function rolesDescription() {
    $headerTable = [
      $this->t('Id'),
      $this->t('Label'),
    ];
    $resultTable = [];
    $roles = Role::loadMultiple();
    foreach ($roles as $role) {
      $resultTable[$role->get('id')] = [
        $role->get('id'),
        $role->get('label'),
      ];
    }

    ksort($resultTable);
    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

  /**
   * Permissions and roles.
   *
   * @return array
   *   Render array.
   */
  public function permissionsPerRole() {
    $headerTable = [
      $this->t('Role'),
      $this->t('Permissions'),
    ];
    $resultTable = [];
    $roles = Role::loadMultiple();
    foreach ($roles as $role) {
      $resultTable[$role->id()] = [
        $role->get('label'),
        implode(', ', $role->get('permissions')),
      ];
    }

    ksort($resultTable);

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];
  }

}
