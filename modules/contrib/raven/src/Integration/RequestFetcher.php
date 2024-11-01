<?php

declare(strict_types=1);

namespace Drupal\raven\Integration;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\Integration\RequestFetcherInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Converts request into PSR-7 request for the RequestIntegration.
 */
class RequestFetcher implements RequestFetcherInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected HttpMessageFactoryInterface $httpMessageFactory,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRequest(): ?ServerRequestInterface {
    $request = $this->requestStack->getCurrentRequest();
    if (NULL === $request) {
      return NULL;
    }
    try {
      return $this->httpMessageFactory->createRequest($request);
    }
    catch (\Throwable $exception) {
      return NULL;
    }
  }

}
