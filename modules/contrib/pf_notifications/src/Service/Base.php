<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Service;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\PermissionCheckerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\danse_content\Service;
use Drupal\user\UserDataInterface;
use Drupal\views\Views;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Push notifications base service class.
 */
class Base implements BaseInterface {

  use StringTranslationTrait;

  /**
   * Constructs Push service object.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RequestStack $requestStack,
    protected AccountProxyInterface $currentUser,
    protected MessengerInterface $messenger,
    protected UserDataInterface $userData,
    protected LoggerChannelFactoryInterface $loggerFactory,
    protected ModuleHandlerInterface $moduleHandler,
    protected Token $token,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PermissionCheckerInterface $permissionChecker,
    protected FloodInterface $flood,
    protected KeysManagerInterface $keysManager,
    protected Service $danseService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUser(): AccountProxyInterface {
    return $this->currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserData(): UserDataInterface {
    return $this->userData;
  }

  /**
   * {@inheritdoc}
   */
  public function getLogger(): LoggerChannelInterface {
    return $this->loggerFactory->get('Push framework notifications');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(): ImmutableConfig {
    return $this->configFactory->get('pf_notifications.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest(): RequestStack {
    return $this->requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(): array {
    $keys = [];
    $count_public = $this->keysManager->getKey('vapid_public', 'value', TRUE);
    $count_private = $this->keysManager->getKey('vapid_private', 'value', TRUE);
    if ($count_public) {
      $keys['public_key'] = $this->keysManager->getKey('vapid_public', 'value');
    }
    if ($count_private) {
      $keys['private_key'] = $this->keysManager->getKey('vapid_private', 'value');
    }
    return $keys;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityData(int $uid, string $name, ContentEntityInterface $entity): array {
    $parents = $this->getParents($entity);
    return [
      'uid' => $uid,
      'name' => $name,
      'entity_id' => $entity->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'entity_uid' => $entity->get('uid')->getValue()[0]['target_id'],
      'entity_label' => $entity->label(),
      'parent_id' => isset($parents['parent_entity']) ? $parents['parent_entity']->id() : NULL,
      'parent_type' => isset($parents['parent_entity']) ? $parents['parent_entity']->getEntityTypeId() : NULL,
      'parent_label' => isset($parents['parent_entity']) ? $parents['parent_entity']->label() : NULL,
      'parent_entity_uid' => isset($parents['parent_entity']) ? $parents['parent_entity']->get('uid')->getValue()[0]['target_id'] : NULL,
      'parent_comment_id' => isset($parents['parent_comment']) ? $parents['parent_comment']->id() : NULL,
      'parent_comment_type' => isset($parents['parent_comment']) ? $parents['parent_comment']->getEntityTypeId() : NULL,
      'parent_comment_uid' => isset($parents['parent_comment']) ? $parents['parent_comment']->get('uid')->getValue()[0]['target_id'] : NULL,
      'parent_comment_label' => isset($parents['parent_comment']) ? $parents['parent_comment']->label() : NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function userLink(array $entity_data, array $default_options = []): Link {
    $user_link_text = $this->t('@user_name', ['@user_name' => $entity_data['name']]);
    $options = array_merge($default_options, [
      'attributes' => [
        'target' => '_blank',
      ],
    ]);
    return Link::createFromRoute($user_link_text, 'entity.user.canonical', [
      'user' => $entity_data['uid'],
    ], $options);
  }

  /**
   * {@inheritdoc}
   */
  public function entityLinks(array $entity_data, string $endpoint = NULL): array {

    $links = [];
    $entity_type = $entity_data['entity_type'] ?? NULL;
    $parent_id = $entity_data['parent_id'] ?? NULL;
    $parent_type = $entity_data['parent_type'] ?? NULL;
    $parent_label = $entity_data['parent_label'] ?? NULL;
    $parent_comment_id = $entity_data['parent_comment_id'] ?? NULL;
    $parent_comment_type = $entity_data['parent_comment_type'] ?? NULL;
    $parent_comment_label = $entity_data['parent_comment_label'] ?? NULL;

    $options = [
      'absolute' => TRUE,
      'attributes' => [
        'target' => '_blank',
      ],
      'entity_type' => $entity_type,
    ];

    $text_params = [
      '@entity_type' => $entity_type,
    ];

    // Client info.
    if ($endpoint) {
      foreach (SubscriptionInterface::PUSH_SERVICES as $key => $label) {
        $match = str_replace('*.', '', $key);
        if (str_contains($endpoint, $match)) {
          $url = Url::fromUri($endpoint, $options);
          $links['client'] = Link::fromTextAndUrl($label, $url);
        }
      }
    }

    // Link to user.
    $links['user'] = $this->userLink($entity_data);

    // Link to "Manage subscriptions" admin view.
    $view_link_text = $this->t('Manage subscriptions');
    $view_link_args = [
      'arg_0' => NULL,
      'arg_1' => NULL,
    ];
    $links['manage'] = Link::createFromRoute($view_link_text, static::REDIRECT_ROUTE, $view_link_args);

    $route_params = [];
    $route_name = '<front>';
    $link_text = '';
    if ($entity_type) {
      $text_params['@label'] = $entity_data['entity_label'];
      $link_text = $this->t('@label', $text_params);
      $route_name = 'entity.' . $entity_type . '.canonical';
      $route_params = [
        $entity_type => $entity_data['entity_id'],
      ];
    }

    if ($parent_id && $parent_type) {
      // Link to parent entity.
      $text_params['@label'] = $parent_label;
      $parent_link_text = $this->t('@label', $text_params);
      $route_name = 'entity.' . $parent_type . '.canonical';
      $route_params = [
        $parent_type => $parent_id,
      ];
      $options['parent_entity_type'] = $parent_type;
      $links['parent'] = Link::createFromRoute($parent_link_text, $route_name, $route_params, $options);

      // This comes later, so that it applies to 'entity' link.
      $options['fragment'] = $entity_type . '-' . $entity_data['entity_id'];

      // Link to parent comment.
      if ($parent_comment_id) {
        // Comment and its parent are always the same bundle.
        $text_params['@label'] = $parent_comment_label;
        $parent_comment_text = $this->t('@label', $text_params);
        $options['parent_comment_type'] = $parent_comment_type;
        $options['fragment'] = $entity_type . '-' . $parent_comment_id;
        $links['parent_comment'] = Link::createFromRoute($parent_comment_text, $route_name, $route_params, $options);
      }
    }

    // Link to entity, or parent entity, in case entity is child,
    // without own route.
    $links['entity'] = Link::createFromRoute($link_text, $route_name, $route_params, $options);
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function markSeen(): void {

    if ($oid = $this->getRequest()->getCurrentRequest()->query->get('oid')) {

      if (!$this->permissionChecker->hasPermission(static::REST_PERMISSION, $this->getCurrentUser())) {
        return;
      }

      try {
        $notifications = $this->entityTypeManager->getStorage('danse_notification')->loadByProperties([
          'id' => $oid,
          'uid' => $this->getCurrentUser()->id(),
          'delivered' => 1,
        ]);
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
        $this->getLogger()->error($e->getMessage());
        return;
      }

      if (!empty($notifications)) {
        /** @var \Drupal\danse\Entity\Notification $notification */
        $notification = reset($notifications);
        $notification->markSeen();
        if ($this->getConfig()->get('debug')) {
          $this->getLogger()->notice('The notification @oid marked as seen.', ['@oid' => $oid]);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptions(int $uid, string $key = NULL, string $module = 'danse'): int|array {
    if ($key) {
      $data = $this->userData->get($module, $uid, $key);
      if (is_array($data) && isset($data[static::PROPERTY])) {
        return $data[static::PROPERTY];
      }
      else {
        return (int) $data;
      }
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSubscription(int $uid, string $danse_key, string $token, int $danse_active, bool $test = FALSE, bool $reset_cache = TRUE): bool {

    // Test subscription from config form.
    if ($test) {
      $this->getUserData()->delete(static::PROPERTY, $uid);
      return FALSE;
    }
    $user_data = $this->getUserData()->get('danse', $uid, $danse_key);
    $subscriptions = $this->getSubscriptions($uid, $danse_key);

    if (is_numeric($subscriptions) && $subscriptions == 2) {
      $this->getUserData()->set('danse', $uid, $danse_key, 0);
      // Clear some cache now.
      if ($reset_cache) {
        $this->invalidateCacheTags();
        $this->invalidateCacheTags('danse');
      }
      return TRUE;
    }

    $property = static::PROPERTY;
    if (!empty($subscriptions) && isset($subscriptions[$token])) {
      try {
        if (count($subscriptions) > 1) {
          $set_data = array_filter($subscriptions, function ($item) use ($token) {
            return $item['token'] != $token;
          }, ARRAY_FILTER_USE_BOTH);
          if (count($user_data) > 1) {
            if (empty($set_data)) {
              $update_data = $user_data;
            }
            else {
              $update_data = $user_data + $set_data;
            }
          }
          else {
            if (empty($set_data)) {
              $update_data[$danse_key] = $danse_active;
            }
            else {
              $update_data[$property] = $set_data;
            }
          }
          $this->getUserData()->set('danse', $uid, $danse_key, $update_data);
        }
        else {
          // Preserve some existing data for DANSE key. Other than int 0.
          if (count($user_data) > 1) {
            $update_data = $user_data;
            if (isset($update_data[$property])) {
              unset($update_data[$property]);
              $update_data[$danse_key] = $danse_active;
            }
          }
          else {
            // Revert back to default DANSE setting for "off".
            $update_data = $danse_active;
          }
          $this->getUserData()->set('danse', $uid, $danse_key, $update_data);
        }
        // Clear some cache now.
        if ($reset_cache) {
          $this->invalidateCacheTags();
          $this->invalidateCacheTags('danse');
        }
        return TRUE;
      }
      catch (\Exception $e) {
        $this->getLogger()->error($e->getMessage());
        $this->messenger->addError($e->getMessage());
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll(): void {
    $user_data = $this->getUserData()->get('danse');
    if (is_array($user_data)) {
      foreach ($user_data as $uid => $items) {
        foreach ($items as $danse_key => $item) {
          if (is_array($item) && isset($item[static::PROPERTY])) {
            foreach ($item[static::PROPERTY] as $token => $data) {
              $danse_active = isset($data['danse_active']) ? (int) $data['danse_active'] : 0;
              $this->deleteSubscription($uid, $danse_key, $token, $danse_active);
            }
          }
        }
      }
      $this->invalidateCacheTags();
      $this->invalidateCacheTags('danse');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function invalidateCacheTags(string $type = 'view'): void {
    switch ($type) {
      case 'view':
        foreach (static::CACHED_VIEWS as $views_id) {
          if ($view = Views::getView($views_id)) {
            $cache_tags = $view->getCacheTags();
            Cache::invalidateTags(array_unique($cache_tags));
          }
        }
        break;

      case 'danse':
        Cache::invalidateTags(['pf_notifications:subscription']);
        break;
    }
  }

  /**
   * Get subscribed entity's parents.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface<object> $entity
   *   Subscribed entity (e.q. node or comment).
   *
   * @return array<string, \Drupal\Core\Entity\FieldableEntityInterface|null>
   *   An array with (any) parent entities.
   */
  protected function getParents(ContentEntityInterface $entity): array {
    $parents = [];
    switch ($entity->getEntityTypeId()) {
      case 'comment':
        /** @var \Drupal\comment\Entity\Comment $entity */
        $parents['parent_comment'] = $entity->getParentComment();
        $parents['parent_entity'] = $entity->getCommentedEntity();
        break;

      case 'paragraph':
        /** @var \Drupal\paragraphs\ParagraphInterface $entity */
        $parents['parent_entity'] = $entity->getParentEntity();
        break;
    }
    return $parents;
  }

}
