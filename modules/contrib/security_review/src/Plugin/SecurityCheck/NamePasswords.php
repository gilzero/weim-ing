<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Drupal\user\Entity\User;
use Drupal\user\UserAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if a user has the same name and password.
 *
 * @SecurityCheck(
 *   id = "name_passwords",
 *   title = @Translation("Name password check"),
 *   description = @Translation("Checks if a user has the same name and password."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("No users, with matching username and password, found."),
 *   failure_message = @Translation("Users, with matching username and password, found."),
 *   help = {
 *     @Translation("Verifies that users have not set their password to be the same as their username."),
 *   }
 * )
 */
class NamePasswords extends SecurityCheckBase {

  use MessengerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

  /**
   * Drupal's user authentication service.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected UserAuthInterface $userAuth;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->userAuth = $container->get('user.auth');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float {
    if (!isset($sandbox['uids'])) {
      try {
        $uids = $this->entityTypeManager->getStorage('user')
          ->getQuery()
          ->accessCheck()
          ->condition('uid', 0, '<>')
          ->execute();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException) {
        $this->messenger()->addError('Error running name_passwords check.');

        return 1;
      }
      $sandbox = [];
      $sandbox['uids'] = $uids;
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($uids);
      $sandbox['findings'] = [];
    }

    // 100 at a time.
    $ids = array_slice($sandbox['uids'], $sandbox['progress'], 100);
    $users = User::loadMultiple($ids);
    $findings = [];
    foreach ($users as $user) {
      if ($this->userAuth->authenticate($user->getDisplayName(), $user->getDisplayName())) {
        $findings[] = $user->getDisplayName();
      }

      // Update our progress information.
      $sandbox['progress']++;
    }

    if (!empty($findings)) {
      $sandbox['findings'] = array_merge($sandbox['findings'], $findings);
    }

    // Have we finished?
    if ($sandbox['progress'] == $sandbox['max']) {
      $result = CheckResult::SUCCESS;
      if (!empty($sandbox['findings'])) {
        $result = CheckResult::FAIL;
      }
      $this->createResult($result, $sandbox['findings']);

      return 1;
    }

    // Report we are not finished, and provide an estimation of the
    // completion level we reached.
    return $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings = [], array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];
    $paragraphs[] = $this->t('The following user(s) has their password set to be the same as their username');
    $user_list = [];
    foreach ($findings as $user) {
      $user_list[] = Html::escape($user);
    }

    $paragraphs[] = $this->t('Consider installing the <a href="https://www.drupal.org/project/password_policy">Password Policy</a> module, to enforce users to have a stronger password.');

    if ($returnString) {
      $output .= implode("", $paragraphs) . implode("", $user_list);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $user_list,
      ];
    }

    return $output;
  }

}
