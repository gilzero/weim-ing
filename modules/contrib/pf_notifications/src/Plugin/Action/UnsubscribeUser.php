<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Plugin\Action;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\PermissionCheckerInterface;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Remove push notifications subscriptions action.
 *
 * @Action(
 *   id = "pf_notifications_unsubscribe",
 *   label = @Translation("Unsubscribe from push notifications"),
 *   type = "user",
 *   category = @Translation("Push framework"),
 * )
 * @phpstan-consistent-constructor
 */
class UnsubscribeUser extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected BaseInterface $service,
    private readonly PermissionCheckerInterface $permissionChecker,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('pf_notifications.base'),
      $container->get('permission_checker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface|bool {
    $result = $account !== NULL && $this->permissionChecker->hasPermission('administer push notifications', $account);
    return $return_as_object ? AccessResultAllowed::allowedIf($result) : $result;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(UserInterface $user = NULL): void {
    $uid = (int) $user->id();
    $user_data = $this->service->getUserData()->get('danse', $uid);
    $property = BaseInterface::PROPERTY;
    if (is_array($user_data)) {
      foreach ($user_data as $danse_key => $items) {
        if (isset($items[$property])) {
          unset($items[$property]);
        }
        // Revert back to default DANSE setting for "off".
        if (empty($items)) {
          $items = 0;
        }
        $this->service->getUserData()->set('danse', $uid, $danse_key, $items);
      }
    }
  }

}
