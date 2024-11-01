<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Link;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Drupal\user\Entity\Role;

/**
 * Checks whether untrusted roles have restricted permissions.
 *
 * @SecurityCheck(
 *   id = "admin_permissions",
 *   title = @Translation("Administrative Permissions"),
 *   description = @Translation("Checks whether untrusted roles have restricted permissions."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Untrusted roles do not have administrative or trusted Drupal permissions."),
 *   failure_message = @Translation("Untrusted roles have been granted administrative or trusted Drupal permissions."),
 *   help = {
 *     @Translation("Drupal's permission system is extensive and allows for varying degrees of control. Certain permissions would allow a user total control, or the ability to escalate their control, over your site and should only be granted to trusted users."),
 *   }
 * )
 */
class AdminPermissions extends SecurityCheckBase {

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Get every permission.
    $all_permissions = $this->securitySettings->permissions(TRUE);
    $all_permission_strings = array_keys($all_permissions);

    // Get permissions for untrusted roles.
    $untrusted_permissions = $this->securitySettings->untrustedPermissions(TRUE);
    foreach ($untrusted_permissions as $rid => $permissions) {
      $intersect = array_intersect($all_permission_strings, $permissions);
      foreach ($intersect as $permission) {
        if (!empty($all_permissions[$permission]['restrict access'])) {
          $findings[$rid][] = $permission;
        }
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];
    foreach ($findings as $rid => $permissions) {
      $role = Role::load($rid);
      /** @var \Drupal\user\Entity\Role $role */
      $paragraphs[] = $this->t(
        "@role has the following restricted permissions:",
        [
          '@role' => Link::createFromRoute(
            $role->label(),
            'entity.user_role.edit_permissions_form',
            ['user_role' => $role->id()]
          )->toString(),
        ]
      );

      if ($returnString) {
        $output .= implode("", $paragraphs);
      }
      else {
        $output[] = [
          '#theme' => 'check_evaluation',
          '#paragraphs' => $paragraphs,
          '#items' => $permissions,
        ];
      }
    }

    return $output;
  }

}
