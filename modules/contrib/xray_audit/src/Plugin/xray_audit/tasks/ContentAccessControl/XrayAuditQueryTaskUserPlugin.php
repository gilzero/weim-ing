<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentAccessControl;

use Drupal\Core\Database\StatementInterface;
use Drupal\user\Entity\Role;
use Drupal\xray_audit\Plugin\xray_audit\tasks\ContentMetric\XrayAuditQueryTaskPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "queries_data_user",
 *   label = @Translation("User reports"),
 *   description = @Translation("Reports about the users."),
 *   group = "content_access_control",
 *   local_task = 1,
 *   sort = 5,
 *   operations = {
 *     "number_users_roles" = {
 *           "label" = "Grouped by roles",
 *           "description" = "Number of Users grouped by roles."
 *        },
 *      "number_users_status" = {
 *          "label" = "Grouped by status",
 *          "description" = "Number of Users grouped by status."
 *       },
 *      "number_users_activity" = {
 *          "label" = "Grouped by activity",
 *          "description" = "Number of Users grouped by activity."
 *       }
 *    },
 *   dependencies = {"user"}
 * )
 */
class XrayAuditQueryTaskUserPlugin extends XrayAuditQueryTaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'number_users_status':
        return $this->countUser();

      case 'number_users_activity':
        return $this->userLessActivity();

      case 'number_users_roles':
        return $this->usersPerRole();
    }
    return [];
  }

  /**
   * Get data for operation "count_user".
   *
   * @return array
   *   Data.
   */
  protected function countUser() {

    $headerTable = [$this->t('Status'), $this->t('Number of users')];

    $aggregateQuery = $this->entityTypeManager->getStorage('user')->getAggregateQuery();

    $resultTable = $aggregateQuery->accessCheck(FALSE)
      ->aggregate('uid', 'count')
      ->condition('uid', 0, '!=')
      ->groupBy('status')
      ->execute();

    $total = 0;

    /** @var mixed[] $row */
    foreach ($resultTable as &$row) {
      $row['status'] = $row['status'] == 1 ? $this->t('Active') : $this->t('Blocked');
      $total += $row['uid_count'];
    }

    $resultTable[] = [
      'status' => $this->t('Total'),
      'user_count' => $total,
    ];

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

  /**
   * Activity users.
   *
   * @return array
   *   Data.
   */
  protected function userLessActivity() {
    $headerTable = [$this->t('Last access'), $this->t('Number of users')];
    $userStorage = $this->entityTypeManager->getStorage('user');
    $current_time = time();

    $one_month_seconds = 2592000;

    // Users last access in 6 months.
    $user_count_6_m = $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('access', $current_time - ($one_month_seconds * 6), '>')
      ->count()
      ->execute();

    // Users last access 6 to 12 months.
    $user_count_6_12_m = $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('access', $current_time - ($one_month_seconds * 6), '<')
      ->condition('access', $current_time - ($one_month_seconds * 12), '>')
      ->count()
      ->execute();

    // Users access more than 12 months.
    $user_count_more_12_m = $userStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('access', $current_time - ($one_month_seconds * 12), '<')
      ->count()
      ->execute();

    $resultTable = [
      [$this->t('Last 6 months'), $user_count_6_m],
      [$this->t('Last 6 to 12 months'), $user_count_6_12_m],
      [$this->t('More than 12 months'), $user_count_more_12_m],
    ];

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

  /**
   * Users per role.
   *
   * @return mixed[]
   *   Render array.
   */
  public function usersPerRole() {
    $resultTable = [];
    $roles = Role::loadMultiple();

    foreach ($roles as $role) {
      $resultTable[$role->id()] = [
        'machine_name' => $role->id(),
        'role' => $role->label(),
        'count' => 0,
      ];
    }

    ksort($resultTable);

    $headerTable = [$this->t('ID'), $this->t('Label'), $this->t('Count')];

    $query = $this->database->query("select roles_target_id as role, count(1) as count from user__roles group by role order by role");
    if (!$query instanceof StatementInterface) {
      return [];
    }
    $result = $query->fetchAll();

    foreach ($result as $row) {
      $resultTable[$row->role]['count'] = $row->count;
    }

    return [
      'header_table' => $headerTable,
      'results_table' => $resultTable,
    ];

  }

}
