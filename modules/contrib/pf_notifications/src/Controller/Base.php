<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\pf_notifications\Service\SubscriptionInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Pf notifications routes.
 */
class Base extends ControllerBase {

  const HEADERS = [
    'Clear-Site-Data' => 'storage',
  ];

  /**
   * Push notifications push service.
   *
   * @var \Drupal\pf_notifications\Service\BaseInterface
   */
  protected BaseInterface $service;

  /**
   * Push notifications service.
   *
   * @var \Drupal\pf_notifications\Service\SubscriptionInterface
   */
  protected SubscriptionInterface $subscription;

  /**
   * Push notifications service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->service = $container->get('pf_notifications.base');
    $instance->subscription = $container->get('pf_notifications.subscription');
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * Install and register service worker.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   The service worker response.
   */
  public function serviceWorkerRegistration(Request $request): CacheableResponse {

    $path = $this->moduleHandler()->getModule('pf_notifications')->getPath();
    $uri = Url::fromUserInput('/' . $path . '/js/pf_notifications.service_worker.js', ['absolute' => TRUE])->toString();
    $service_worker = '';

    try {
      $service_worker = $this->fetchJs($uri);
    }
    catch (GuzzleException $e) {
      $error = $this->t('Push framework notifications Service Worker registration failed: @error', [
        '@error' => $e->getMessage(),
      ]);
      $this->messenger()->addError($error);
      $this->service->getLogger()->error($error);
    }

    $settings = $this->config('pf_notifications.settings');
    $keys = (object) $this->service->getKeys();

    // Initialize a CacheableMetadata object.
    $cacheable_metadata = new CacheableMetadata();
    $cache_tags = Cache::buildTags('pf_notifications', ['subscription']);
    $cacheable_metadata
      ->addCacheableDependency($settings)
      ->addCacheableDependency($keys)
      ->setCacheMaxAge(0)
      ->setCacheContexts(['url.query_args'])
      ->setCacheTags($cache_tags);

    $response = new CacheableResponse($service_worker, 200, [
      'Content-Type' => 'application/javascript',
      'Service-Worker-Allowed' => '/',
    ]);
    $response->addCacheableDependency($cacheable_metadata);

    return $response;
  }

  /**
   * Unregister service worker.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request object.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   A "refreshed" service worker response.
   */
  public function unregisterServiceWorker(Request $request): CacheableResponse {
    $response = $this->serviceWorkerRegistration($request);
    $response->headers->add(static::HEADERS);
    return $response;
  }

  /**
   * Subscription response, overrides danse_content methods.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param string $entity_id
   *   The entity ID.
   * @param string $key
   *   The subscription key.
   * @param string $type
   *   Optional last param in path.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The response with our commands executed.
   *
   * @see \Drupal\pf_notifications\EventSubscriber\DanseRoute
   */
  public function reSubscribe(string $entity_type, string $entity_id, string $key, string $type = 'default'): AjaxResponse {

    $response = new AjaxResponse();
    $entity = NULL;

    try {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      $this->service->getLogger()->error($e->getMessage());
    }

    $uid = (int) $this->service->getCurrentUser()->id();
    $subscriptions = $this->service->getSubscriptions($uid, $key);

    if (is_array($subscriptions)) {
      if (!empty($subscriptions)) {
        $subscriptions = array_filter($subscriptions, function ($subscription) use ($key) {
          return $subscription['danse_key'] == $key;
        }, ARRAY_FILTER_USE_BOTH);
        $op = $subscriptions;
        $subscription = reset($subscriptions);
        $danse_active = $subscription['danse_active'];
      }
      else {
        $op = 0;
        $danse_active = 1;
      }
    }
    else {
      $danse_data = $this->service->getUserData()->get(BaseInterface::DANSE_MODULE, $uid, $key);
      $op = $danse_data == 0 ? 1 : 0;
      $danse_active = $op == 1 ? 0 : 1;
    }
    return $this->subscription->subscriptionResponse($op, $key, $entity, $response, $danse_active);
  }

  /**
   * Fetch JS file containing Service worker.
   *
   * @param string $uri
   *   An absolute url to JS file.
   *
   * @return string|null
   *   Long string containing contents of service worker JS file.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function fetchJs(string $uri): string|NULL {
    $body = NULL;
    $response = $this->httpClient->request('GET', $uri);
    $code = $response->getStatusCode();
    if ($code == 200) {
      $body = $response->getBody()->getContents();
    }
    return $body;
  }

}
