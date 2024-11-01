<?php

namespace Drupal\iconify_icons;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise\Utils;
use Psr\Log\LoggerInterface;

/**
 * Service for Iconify functionality.
 */
class IconifyService implements IconifyServiceInterface {

  public const SEARCH_API_ENDPOINT = 'https://api.iconify.design/search';

  public const COLLECTION_API_ENDPOINT = 'https://api.iconify.design/collection';

  public const COLLECTIONS_API_ENDPOINT = 'https://api.iconify.design/collections';

  // Arguments [$collection, $icon_name].
  public const DESIGN_DOWNLOAD_API_ENDPOINT = 'https://api.iconify.design/%s/%s.svg';

  public const API_VERSION = 'https://api.iconify.design/version';

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The Logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The cache service.
   *
   * @var \Drupal\iconify_icons\IconsCacheInterface
   */
  protected $cache;

  /**
   * IconifyService constructor.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Psr\Log\LoggerInterface $logger
   *   The Logger service.
   * @param \Drupal\iconify_icons\IconsCacheInterface $cache
   *   The cache service.
   */
  public function __construct(ClientInterface $http_client, LoggerInterface $logger, IconsCacheInterface $cache) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(string $query, string $collection, int $limit = 120): array {
    try {
      $response = $this->httpClient->request('GET', $this::SEARCH_API_ENDPOINT, [
        'query' => [
          'query' => urlencode($query),
          'prefixes' => $collection,
        ],
      ]);

      $data = json_decode($response->getBody()
        ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
      return $data['icons'] ?? [];
    }
    catch (RequestException $e) {
      // Handle request exception (e.g., log error, return empty array)
      $this->logger->error('JSON decode error: {message}', ['message' => $e->getMessage()]);
      return [];
    }
    catch (\JsonException $e) {
      $this->logger->error('JSON decode error: {message}', ['message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCollections(): array {
    try {
      $response = $this->httpClient->request('GET', $this::COLLECTIONS_API_ENDPOINT, []);
      return json_decode($response->getBody()
        ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (RequestException $e) {
      // Handle request exception (e.g., log error, return empty array)
      $this->logger->error('Error fetching collections: ' . $e->getMessage());
      return [];
    }
    catch (\JsonException $e) {
      $this->logger->error('JSON decode error: {message}', ['message' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getIconsByCollection(string $collection): array {
    try {
      $response = $this->httpClient->request('GET', $this::COLLECTION_API_ENDPOINT, [
        'query' => [
          'prefix' => $collection,
        ],
      ]);

      $icons_list = Json::decode((string) $response->getBody());

      if (!is_array($icons_list)) {
        $param = [
          '@collection' => $collection,
          '@error' => (string) $response->getBody(),
        ];
        $this->logger->error('Iconify error for @collection: @error', $param);
        return [];
      }

      $icons = [];
      if (!empty($icons_list['categories'])) {
        foreach ($icons_list['categories'] as $list) {
          if (is_array($list)) {
            foreach ($list as $icon) {
              $icons[] = $icon;
            }
          }
        }

        return $icons;
      }

      return $icons_list['uncategorized'] ?? [];
    }
    catch (ClientException | ServerException $e) {
      $param = [
        '@errorType' => ($e instanceof ClientException) ? 'client' : 'server',
        '@collection' => $collection,
        '@error' => $e->getResponse(),
      ];
      $this->logger->error(
        'Iconify @errorType error for @collection: @error',
        $param
      );
      return [];
    }

  }

  /**
   * Qualifies the default parameters and sets if any are missing.
   *
   * @param array $parameters
   *   The parameters to qualify.
   *
   * @return array
   *   The qualified parameters.
   */
  protected function setDefaultParameters(array $parameters): array {
    return [
      'width' => $parameters['width'] ?? 25,
      'height' => $parameters['height'] ?? 25,
      'color' => $parameters['color'] ?? 'currentColor',
      'flip' => $parameters['flip'] ?? '',
      'rotate' => $parameters['rotate'] ?? '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function generateSvgIcon(string $collection, string $icon_name, array $parameters = []): string {
    $parameters = $this->setDefaultParameters($parameters);
    if ($this->cache->checkIcon($collection, $icon_name, $parameters)) {
      return $this->cache->getIcon($collection, $icon_name, $parameters);
    }
    try {
      $promise = $this->httpClient->requestAsync('GET', sprintf($this::DESIGN_DOWNLOAD_API_ENDPOINT, $collection, $icon_name), [
        'query' => $parameters,
      ]);

      // Wait for the asynchronous request to complete.
      $response = $promise->wait();

      $icon = $response->getBody()->getContents();

      $this->cache->setIcon($collection, $icon_name, $icon, $parameters);

      return $icon;
    }
    catch (RequestException $e) {
      // Handle request exception (e.g., log error, return empty array)
      $this->logger->error('Error generating svg icon: ' . $e->getMessage());
      return '';
    }
    catch (\JsonException $e) {
      $this->logger->error('JSON decode error: {message}', ['message' => $e->getMessage()]);
      return '';
    }

  }

  /**
   * {@inheritdoc}
   */
  public function generateSvgIcons(array $icons, array $parameters = []): array {
    $parameters = $this->setDefaultParameters($parameters);
    $results = [];
    $promises = [];
    foreach ($icons as $icon) {
      // Extract collection and name from $icon.
      [$collection, $icon_name] = explode(':', $icon, 2);
      if ($this->cache->checkIcon($collection, $icon_name, $parameters)) {
        $results[$icon] = $this->cache->getIcon($collection, $icon_name, $parameters);
      }
      else {
        $promises[$icon] = $this->httpClient->requestAsync('GET', sprintf($this::DESIGN_DOWNLOAD_API_ENDPOINT, $collection, $icon_name), [
          'query' => $parameters,
        ]);
      }
    }

    try {
      // Wait for the all asynchronous request to complete.
      $responses = Utils::unwrap($promises);
      foreach ($responses as $icon => $response) {
        [$collection, $icon_name] = explode(':', $icon, 2);
        try {
          $icon_svg = $response->getBody()->getContents();
          $this->cache->setIcon($collection, $icon_name, $icon_svg, $parameters);
          $results[$icon] = $icon_svg;
        }
        catch (RequestException $e) {
          // Handle request exception (e.g., log error, return empty array)
          $this->logger->error('Error generating svg icon: ' . $e->getMessage());
          $results[$icon] = '';
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
    catch (\Throwable $e) {
    }

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function getIconSource(string $collection, string $icon_name, array $parameters = []): string {
    $width = $parameters['width'] ?? '';
    $height = $parameters['height'] ?? '';
    $color = isset($parameters['color']) ? urlencode($parameters['color']) : '';
    $flip = $parameters['flip'] ?? '';
    $rotate = $parameters['rotate'] ?? '';

    return sprintf(
      "https://api.iconify.design/%s/%s.svg?width=%s&height=%s&color=%s&flip=%s&rotate=%s",
      $collection,
      $icon_name,
      $width,
      $height,
      $color,
      $flip,
      $rotate
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getApiVersion(): string {
    try {
      $response = $this->httpClient->request('GET', self::API_VERSION);
      return $response->getBody()->getContents();
    }
    catch (RequestException $e) {
      $this->logger->error('Error getting API version: ' . $e->getMessage());
      return '';
    }
  }

}
