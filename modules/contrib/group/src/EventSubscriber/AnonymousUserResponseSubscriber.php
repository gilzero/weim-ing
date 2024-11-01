<?php

namespace Drupal\group\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Access\GroupPermissionCalculatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to handle finished responses for the anonymous user.
 */
class AnonymousUserResponseSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * Constructs an AnonymousUserResponseSubscriber object.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\group\Access\GroupPermissionCalculatorInterface $permission_calculator
   *   The group permission calculator.
   */
  public function __construct(AccountInterface $current_user, GroupPermissionCalculatorInterface $permission_calculator) {
    $this->currentUser = $current_user;
    $this->groupPermissionCalculator = $permission_calculator;
  }

  /**
   * Adds a cache tag if the 'user.permissions' cache context is present.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onRespond(ResponseEvent $event) {
    if (!$event->isMainRequest()) {
      return;
    }

    if (!$this->currentUser->isAnonymous()) {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    // The 'user.group_permissions' cache context ensures that if the group
    // permissions for a user are modified, users are not served stale render
    // cache content. But, when entire responses are cached in reverse proxies,
    // the value for the cache context is never calculated, causing the stale
    // response to not be invalidated. Therefore, when varying by permissions
    // and the current user is the anonymous user, also add the cache tags for
    // whatever was used to calculate the 'anonymous' group permissions.
    if (in_array('user.group_permissions', $response->getCacheableMetadata()->getCacheContexts())) {
      $anonymous_permissions = $this->groupPermissionCalculator->calculateFullPermissions($this->currentUser);
      $response->addCacheableDependency($anonymous_permissions);
    }
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    // Priority 5, so that it runs before FinishResponseSubscriber, but after
    // event subscribers that add the associated cacheability metadata (which
    // have priority 10). This one is conditional, so must run after those.
    $events[KernelEvents::RESPONSE][] = ['onRespond', 5];
    return $events;
  }

}
