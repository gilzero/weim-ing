<?php

namespace Drupal\Tests\rest_log\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\rest_log\Entity\RestLog;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base class for rest log kernel tests.
 */
abstract class RestLogKernelTestBase extends KernelTestBase {
  use UserCreationTrait;

  /**
   * The entity storage for rest_log.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $restLogStorage;

  /**
   * The Drupal kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $kernel;

  /**
   * The wrapped HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'views', 'user', 'path_alias', 'rest_log', 'file', 'field', 'rest_log_test', 'rest',
    'serialization', 'path_alias',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('rest_log');
    $this->installEntitySchema('path_alias');
    $this->installConfig(['system', 'field', 'file', 'views', 'user', 'rest', 'rest_log', 'rest_log_test']);
    $this->setUpCurrentUser(['uid' => 1]);
    $this->restLogStorage = $this->container->get('entity_type.manager')->getStorage('rest_log');
    $this->kernel = $this->container->get('kernel');
    $this->httpKernel = $this->container->get('http_kernel');
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * Get number of rest log entities.
   *
   * @return int
   *   Number of new rest_log entities.
   */
  protected function getNumberOfRestLogs(): int {
    return (int) $this->restLogStorage->getQuery()->accessCheck(FALSE)->count()->execute();
  }

  /**
   * Get newest response log.
   *
   * @return \Drupal\rest_log\Entity\RestLog|null
   *   The rest_log entity. Returns NULL if there is no entity.
   */
  protected function getNewestResponseLog(): ?RestLog {
    $rest_log_ids = $this->restLogStorage->getQuery()->accessCheck(FALSE)
      ->range(0, 1)
      ->execute();

    return !empty($rest_log_ids) ? $this->restLogStorage->load(end($rest_log_ids)) : NULL;
  }

  /**
   * Send request to given url.
   *
   * @param string $uri
   *   A string containing the URI to the rest endpoint.
   * @param array $headers
   *   An array of response headers.
   */
  protected function sendRestRequest(string $uri, array $headers = ['Accept' => 'application/json']): void {
    $request = Request::create($uri);

    foreach ($headers as $header_key => $header_value) {
      $request->headers->set($header_key, $header_value);
    }

    $response = $this->httpKernel->handle($request);
    $this->httpKernel->terminate($request, $response);
  }

}
