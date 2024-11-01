<?php

namespace Drupal\moderation_dashboard\Routing;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber to redirect user login to the Moderation Dashboard.
 */
class ResponseSubscriber implements EventSubscriberInterface {

  /**
   * ResponseSubscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   *   The condition plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   */
  public function __construct(
    protected AccountProxyInterface $currentUser,
    protected ConditionManager $conditionManager,
    protected ConfigFactoryInterface $configFactory,
  ) {
  }

  /**
   * Redirects user login to the Moderation Dashboard, when appropriate.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The response event.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    $request = $event->getRequest();

    $should_redirect = $this->configFactory
      ->get('moderation_dashboard.settings')
      ->get('redirect_on_login');

    if ($should_redirect && $response instanceof RedirectResponse) {
      $response_url_components = UrlHelper::parse($response->getTargetUrl());
      $has_destination = isset($response_url_components['query']['destination']);

      $is_login = $request->request->get('form_id') === 'user_login_form';
      $has_permission = $this->currentUser->hasPermission('use moderation dashboard');
      $has_moderated_content_type = $this->conditionManager->createInstance('has_moderated_content_type')->execute();

      if ($has_permission && $is_login && $has_moderated_content_type && !$has_destination) {
        $url = Url::fromRoute('view.moderation_dashboard.page_1', ['user' => $this->currentUser->id()]);
        $response->setTargetUrl($url->toString());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onResponse', 100];
    return $events;
  }

}
