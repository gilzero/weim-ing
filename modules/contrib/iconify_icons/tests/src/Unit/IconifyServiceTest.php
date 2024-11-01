<?php

namespace Drupal\Tests\iconify_icons\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\iconify_icons\IconifyService;
use Drupal\iconify_icons\IconsCacheInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Test description.
 *
 * @group iconify_icons
 */
class IconifyServiceTest extends UnitTestCase {

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
   * The tested ban iconify_service.
   *
   * @var \Drupal\iconify_icons\IconifyService
   */
  protected $iconifyService;

  /**
   * The cache service.
   *
   * @var \Drupal\iconify_icons\IconsCacheInterface
   */
  protected $cache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->cache = $this->createMock(IconsCacheInterface::class);
    $this->iconifyService = new IconifyService($this->httpClient, $this->logger, $this->cache);
  }

  /**
   * Tests getIcons method.
   */
  public function testGetIcons() {
    $query = 'query_example';
    $collection = 'collection_test';
    $limit = 10;

    $ok_response = ['icon_1.png', 'icon_2.png'];

    $stream = $this->createMock(StreamInterface::class);
    $stream
      ->method('getContents')
      ->willReturn(json_encode(['icons' => $ok_response]));

    $response = $this->createMock(ResponseInterface::class);
    $response
      ->method('getBody')
      ->willReturn($stream);

    $this->httpClient
      ->method('request')
      ->with('GET', $this->iconifyService::SEARCH_API_ENDPOINT, [
        'query' => [
          'query' => urlencode($query),
          'prefixes' => $collection,
        ],
      ])
      ->willReturn($response);

    // Test the search with no limits.
    $assert = $this->iconifyService->getIcons($query, $collection, $limit);
    $this->assertEquals($ok_response, $assert);

    // Test the limit.
    // @todo the limit is not implemented yet.
    $assert = $this->iconifyService->getIcons($query, $collection, 1);
    // $this->assertEquals([$ok_response[0]], $assert);
  }

  /**
   * Tests getIcons method.
   */
  public function testgetCollections() {
    $ok_response = ['collection_1', 'collection_2'];

    $stream = $this->createMock(StreamInterface::class);
    $stream
      ->method('getContents')
      ->willReturn(json_encode($ok_response));

    $response = $this->createMock(ResponseInterface::class);
    $response
      ->method('getBody')
      ->willReturn($stream);

    $this->httpClient
      ->method('request')
      ->with('GET', $this->iconifyService::COLLECTIONS_API_ENDPOINT, [])
      ->willReturn($response);

    $assert = $this->iconifyService->getCollections();
    $this->assertEquals($ok_response, $assert);
  }

  /**
   * Tests generateSvg method.
   */
  public function testGenerateSvg() {
    $ok_response = 'SVG content';
    $collection = 'collection_1';
    $icon_name = 'icon_1';
    $icons = ["$collection:$icon_name"];
    $parameters = [];

    $stream = $this->createMock(StreamInterface::class);
    $stream
      ->method('getContents')
      ->willReturn($ok_response);

    $response = $this->createMock(ResponseInterface::class);
    $response
      ->method('getBody')
      ->willReturn($stream);

    // Mock the promise to return the mocked response when 'wait' is called.
    $promise = $this->createMock(PromiseInterface::class);
    $promise
      ->method('wait')
      ->willReturn($response);

    // Mock the HTTP client to return the mocked promise when 'requestAsync'
    // is called.
    $this->httpClient
      ->method('requestAsync')
      ->with('GET', sprintf($this->iconifyService::DESIGN_DOWNLOAD_API_ENDPOINT, $collection, $icon_name))
      ->willReturn($promise);

    $assert = $this->iconifyService->generateSvgIcons($icons, $parameters);
    $this->assertEquals(["$collection:$icon_name" => $ok_response], $assert);
  }

}
