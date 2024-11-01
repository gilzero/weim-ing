<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Plugin\rest\resource;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Link;
use Drupal\Core\Session\PermissionCheckerInterface;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\pf_notifications\Service\SubscriptionInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents SubscriptionResource records as resources.
 *
 * @RestResource (
 *   id = "pf_notifications_subscription",
 *   label = @Translation("Push notification subscription"),
 *   uri_paths = {
 *     "canonical" = "/api/pf-notifications-subscription-resource/{id}",
 *     "create" = "/api/pf-notifications-subscription-resource"
 *   }
 * )
 *
 * @phpstan-consistent-constructor
 */
class SubscriptionResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    protected PermissionCheckerInterface $permissionChecker,
    protected FloodInterface $flood,
    protected BaseInterface $service,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('permission_checker'),
      $container->get('flood'),
      $container->get('pf_notifications.base')
    );
  }

  /**
   * Saves new or updates subscription record.
   *
   * @param array<string, string|array<string, mixed>> $data
   *   An array retrieved from payload.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   Modified resource response object.
   */
  public function post(array $data): ModifiedResourceResponse {

    // Flood control, exit.
    $config = $this->service->getConfig();
    if ($config->get('enable') && ($response = $this->exit($config, $data))) {
      return $response;
    }

    if (!$this->permissionChecker->hasPermission(BaseInterface::REST_PERMISSION, $this->service->getCurrentUser())) {

      $this->messenger()->addWarning($this->t('You do not have permission to subscribe to this content.'));
      $user_link = Link::createFromRoute('User: ' . $data['uid'], 'entity.user.canonical', [
        'user' => $data['uid'],
      ]);
      $content_link = Link::createFromRoute('User: ' . $data['uid'], 'entity.user.canonical', [
        $data['entity_type'] => $data['parent_id'] ?? $data['entity_id'],
      ]);
      $this->service->getLogger()->error($this->t('@user_link has no permission <code>@permission</code> and was denied subscribing to @content_link', [
        '@user_link' => $user_link,
        '@permission' => BaseInterface::REST_PERMISSION,
        '@content_link' => $content_link,
      ]));
      return new ModifiedResourceResponse('Status: Access denied', 403);
    }

    if (!empty($data['key']) && !empty($data['token']) && !empty($data['endpoint'])) {

      // This is a Test subscription.
      if ($data['danse_key'] == BaseInterface::TEST_ID) {
        // Subscribe or un-subscribe.
        $this->op($data, 'pf_notifications');
      }
      // Regular subscription.
      else {
        // Subscribe or un-subscribe.
        $this->op($data);
      }
    }
    else {
      $error = $this->t('Something went wrong, subscription data is missing. Consult site admin or developer.');
      $this->messenger()->addError($error);
      $this->service->getLogger()->error($error);
    }

    // Return response.
    return new ModifiedResourceResponse('Status: Ok', 201);
  }

  /**
   * Add or remove unique subscription data to DANSE's array in user_data.
   *
   * @param array<string, string|array<string, mixed>> $data
   *   An array retrieved from payload.
   * @param string $module
   *   User data module field.
   */
  private function op(array $data, string $module = 'danse'): void {

    try {
      $uid = (int) $data['uid'];
      $property = BaseInterface::PROPERTY;
      $danse_active = isset($data['danse_active']) ? (int) $data['danse_active'] : 0;

      if ($data['subscribe'] == 'unsubscribed') {
        $test = $module == 'pf_notifications' && $data['danse_key'] == BaseInterface::TEST_ID;
        $this->service->deleteSubscription($uid, $data['danse_key'], $data['token'], $danse_active, $test);
      }
      else {
        // Test notification.
        if ($module == 'pf_notifications' && $data['danse_key'] == BaseInterface::TEST_ID) {
          $update_data[$data['token']] = $data;
        }
        // Update user data - add to DANSE property (key).
        else {
          $data['danse_active'] = $danse_active;
          $subscriptions = $this->service->getSubscriptions($uid, $data['danse_key']);
          $update_data = is_array($subscriptions) ? [$property => $subscriptions] : [];
          $update_data[$property][$data['token']] = $data;
        }
        $this->service->getUserData()->set($module, $uid, $data['danse_key'], $update_data);
      }

      // Clear some cache now.
      $this->service->invalidateCacheTags();
      $this->service->invalidateCacheTags('danse');
    }
    catch (\Exception $e) {
      $this->service->getLogger()->error($e->getMessage());
    }
  }

  /**
   * Implements Flood control.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   This module's configuration object.
   * @param array<string, string|array<string, mixed>> $data
   *   An array retrieved from payload.
   *
   * @return \Drupal\rest\ModifiedResourceResponse|null
   *   Modified response if not allowed, or null if passed.
   */
  private function exit(ImmutableConfig $config, array $data): ModifiedResourceResponse|NULL {

    $response = NULL;

    // Register attempt.
    $ip = $this->service->getRequest()->getCurrentRequest()->getClientIp();
    $identifier = 'pf_notifications-' . $ip;
    $this->flood->register('pf_notifications.rest.post', $config->get('window'), $identifier);

    // Blocked.
    if (!$this->flood->isAllowed(SubscriptionInterface::FLOOD_ID, $config->get('threshold'), $config->get('window'))) {
      $message_params = [
        '%ip' => $ip,
      ];
      if ((int) $data['uid'] > 0) {
        $message_params['@user_link'] = $this->service->userLink($data)->toString();
        $alert = $this->t('Flood control alert. User @user_link, client: %ip is temporarily blocked.', $message_params);
      }
      else {
        $alert = $this->t('Flood control alert. Client: %ip is temporarily blocked', $message_params);
      }
      $this->service->getLogger()->warning($alert);
      $response = new ModifiedResourceResponse('Status: Too many attempts, exiting.', 403);
    }
    return $response;
  }

}
