<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks whether untrusted roles have restricted permissions.
 *
 * @SecurityCheck(
 *   id = "admin_user",
 *   title = @Translation("Blocked Admin account"),
 *   description = @Translation("Checks whether Admin user 1 is blocked."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("The administrative account is disabled - protected."),
 *   failure_message = @Translation("The administrative account is enabled - dangerous!"),
 *   info_message = @Translation("The 'enable_super_user' parameter is set to false."),
 *   help = {
 *     @Translation("The administrative account, uid 1, is commonly targeted by attackers because this account has superuser privileges which cannot be blocked or limited.  Attacks that do things like change the administrator password, or even brute force or social engineering attacks could compromise the administrator password.  Because the administrative account has such wide privileges it is a good idea to create a role for administrators and explicitly create these less privileged accounts.  The administrative account can be unblocked by users with the ""administer users"" permission if you need to use the account at a later time."),
 *   }
 * )
 */
class AdminUser extends SecurityCheckBase {

  /**
   * The container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected ContainerInterface $container;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->container = $container;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $blocked = FALSE;
    if ($this->container->hasParameter('security.enable_super_user') && $this->container->getParameter('security.enable_super_user') === FALSE) {
      $result = CheckResult::INFO;
      $blocked = TRUE;
    }
    else {
      $result = CheckResult::FAIL;
      $admin = User::load(1);
      if ($admin->isBlocked()) {
        $result = CheckResult::SUCCESS;
        $blocked = TRUE;
      }
    }

    $this->createResult($result, ['admin' => $blocked]);
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
    if (isset($findings['admin']) && !$findings['admin']) {
      $admin_user = User::load(1);
      $paragraphs[] = $this->t(
        "User 1 account with name '@admin_name' is enabled.",
        [
          '@admin_name' => $admin_user->getAccountName(),
        ]);
    }

    if ($returnString) {
      $output .= implode("", $paragraphs);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
      ];
    }

    return $output;
  }

}
