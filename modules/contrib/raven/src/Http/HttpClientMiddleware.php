<?php

namespace Drupal\raven\Http;

use Drupal\raven\EventSubscriber\RequestSubscriber;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Sentry\Breadcrumb;
use Sentry\SentrySdk;
use Sentry\Tracing\SpanContext;
use Sentry\Tracing\SpanStatus;

/**
 * Instrument Guzzle HTTP requests.
 */
class HttpClientMiddleware {

  /**
   * {@inheritdoc}
   */
  public function __construct(protected RequestSubscriber $requestSubscriber) {
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(): callable {
    return function ($handler) {
      return function (RequestInterface $request, array $options) use ($handler) {
        // Build URI without username/password.
        $partialUri = Uri::fromParts([
          'scheme' => $request->getUri()->getScheme(),
          'host' => $request->getUri()->getHost(),
          'port' => $request->getUri()->getPort(),
          'path' => $request->getUri()->getPath(),
        ]);
        $hub = SentrySdk::getCurrentHub();
        $spanAndBreadcrumbData = [
          'http.url' => (string) $partialUri,
          'http.request.method' => $request->getMethod(),
          'http.request.body.size' => $request->getBody()->getSize(),
        ];
        if ($request->getUri()->getQuery() !== '') {
          $spanAndBreadcrumbData['http.query'] = $request->getUri()->getQuery();
        }
        if ($request->getUri()->getFragment() !== '') {
          $spanAndBreadcrumbData['http.fragment'] = $request->getUri()->getFragment();
        }
        $span = NULL;
        $parent = $hub->getSpan();
        if ($parent && $parent->getSampled()) {
          $context = SpanContext::make()
            ->setOrigin('auto.http.client')
            ->setOp('http.client')
            ->setDescription($request->getMethod() . ' ' . (string) $partialUri)
            ->setData($spanAndBreadcrumbData);
          $span = $parent->startChild($context);
          $hub->setSpan($span);
        }
        if ($client = $hub->getClient()) {
          $targets = $client->getOptions()->getTracePropagationTargets();
          if ($targets === NULL || in_array($request->getUri()->getHost(), $targets)) {
            $request = $request
              ->withHeader('sentry-trace', \Sentry\getTraceparent())
              ->withHeader('traceparent', \Sentry\getW3CTraceparent());
          }
          if ($targets !== NULL && in_array($request->getUri()->getHost(), $targets)) {
            $this->requestSubscriber->sanitizeBaggage();
            $request = $request->withHeader('baggage', \Sentry\getBaggage());
          }
        }
        $handlerPromiseCallback = static function ($responseOrException) use ($hub, $spanAndBreadcrumbData, $span, $parent) {
          if ($span) {
            $span->finish();
            $hub->setSpan($parent);
          }
          $response = NULL;
          if ($responseOrException instanceof ResponseInterface) {
            $response = $responseOrException;
          }
          elseif ($responseOrException instanceof RequestException) {
            $response = $responseOrException->getResponse();
          }
          $breadcrumbLevel = Breadcrumb::LEVEL_INFO;
          if ($response) {
            $spanAndBreadcrumbData['http.response.status_code'] = $response->getStatusCode();
            $spanAndBreadcrumbData['http.response.body.size'] = $response->getBody()->getSize();
            if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
              $breadcrumbLevel = Breadcrumb::LEVEL_WARNING;
            }
            elseif ($response->getStatusCode() >= 500) {
              $breadcrumbLevel = Breadcrumb::LEVEL_ERROR;
            }
            if ($span) {
              $span->setStatus(SpanStatus::createFromHttpStatusCode($response->getStatusCode()));
              $span->setData($spanAndBreadcrumbData);
            }
          }
          elseif ($span) {
            $span->setStatus(SpanStatus::internalError());
          }
          $hub->addBreadcrumb(new Breadcrumb($breadcrumbLevel, Breadcrumb::TYPE_HTTP, 'http', NULL, $spanAndBreadcrumbData));
          if ($responseOrException instanceof \Throwable) {
            return Create::rejectionFor($responseOrException);
          }
          return $responseOrException;
        };
        return $handler($request, $options)->then($handlerPromiseCallback, $handlerPromiseCallback);
      };
    };
  }

}
