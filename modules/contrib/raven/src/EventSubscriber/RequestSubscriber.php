<?php

namespace Drupal\raven\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\raven\Logger\RavenInterface;
use Drupal\raven\Tracing\TracingTrait;
use Sentry\Tracing\TransactionSource;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Initializes Raven logger so Sentry functions can be called.
 */
class RequestSubscriber implements EventSubscriberInterface, TrustedCallbackInterface {

  use TracingTrait;

  /**
   * Constructs the request subscriber.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RavenInterface $logger,
    protected TimeInterface $time,
    protected EventDispatcherInterface $eventDispatcher,
    protected ContainerInterface $container,
    protected AccountInterface $currentUser,
  ) {
  }

  /**
   * Starts a transaction if performance tracing is enabled.
   */
  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }
    $config = $this->configFactory->get('raven.settings');
    if (!$this->logger->getClient()) {
      return;
    }
    $request = $event->getRequest();
    $trace = $request->headers->get('sentry-trace') ?? $request->headers->get('traceparent') ?? '';
    $baggage = $request->headers->get('baggage') ?? '';
    $transactionContext = \Sentry\continueTrace($trace, $baggage);
    if (!$config->get('request_tracing') || !$this->currentUser->hasPermission('send performance traces to sentry')) {
      return;
    }
    // This name will later be replaced with the route path, if possible.
    $transactionContext->setName($request->getMethod() . ' ' . $request->getUri())
      ->setSource(TransactionSource::url())
      ->setOrigin('auto.http.server')
      ->setOp('http.server')
      ->setData([
        'http.request.method' => $request->getMethod(),
        'http.url' => $request->getUri(),
      ]);
    $this->startTransaction($transactionContext);
  }

  /**
   * Performance tracing.
   */
  public function onTerminate(TerminateEvent $event): void {
    if (!$this->transaction) {
      return;
    }
    // Clean up the transaction name if we have a route path.
    if ($this->transaction->getMetadata()->getSource() === TransactionSource::url() && $this->container->initialized('current_route_match')) {
      if ($route = $this->container->get('current_route_match')->getRouteObject()) {
        $this->transaction->setName($event->getRequest()->getMethod() . ' ' . $route->getPath())
          ->getMetadata()->setSource(TransactionSource::route());
      }
    }
    $config = $this->configFactory->get('raven.settings');
    $statusCode = $event->getResponse()->getStatusCode();
    $this->transaction->setHttpStatus($statusCode);
    if ($statusCode === Response::HTTP_NOT_FOUND && !$config->get('404_tracing')) {
      $this->transaction->setSampled(FALSE);
    }
    $this->transaction->finish();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return mixed[]
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 222];
    $events[KernelEvents::TERMINATE][] = ['onTerminate', 222];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['getBaggage', 'getTraceparent', 'getW3cTraceparent'];
  }

  /**
   * Sanitize baggage before sending as a header or rendering.
   */
  public function sanitizeBaggage(): void {
    if ($this->transaction && $this->transaction->getMetadata()->getSource() === TransactionSource::url() && $this->container->initialized('current_route_match')) {
      if ($request = $this->container->get('request_stack')->getCurrentRequest()) {
        if ($route = $this->container->get('current_route_match')->getRouteObject()) {
          $this->transaction->setName($request->getMethod() . ' ' . $route->getPath())
            ->getMetadata()->setSource(TransactionSource::route());
        }
      }
    }
  }

  /**
   * Callback for returning Sentry baggage as renderable array.
   *
   * @return mixed[]
   *   Renderable array.
   */
  public function getBaggage(): array {
    $this->sanitizeBaggage();
    // The baggage is URL-encoded and therefore should not need HTML encoding.
    return ['#markup' => \Sentry\getBaggage()];
  }

  /**
   * Callback for returning the Sentry trace string as renderable array.
   *
   * @return mixed[]
   *   Renderable array.
   */
  public function getTraceparent(): array {
    return ['#markup' => \Sentry\getTraceparent()];
  }

  /**
   * Callback for returning the W3C traceparent string as renderable array.
   *
   * @return mixed[]
   *   Renderable array.
   */
  public function getW3cTraceparent(): array {
    return ['#markup' => \Sentry\getW3CTraceparent()];
  }

}
