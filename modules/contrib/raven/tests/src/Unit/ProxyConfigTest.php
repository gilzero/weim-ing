<?php

namespace Drupal\Tests\raven\Unit;

use Drupal\Core\Logger\LogMessageParserInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\raven\Logger\Raven;
use Sentry\Integration\RequestFetcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test proxy configuration.
 *
 * @group raven
 */
class ProxyConfigTest extends UnitTestCase {

  /**
   * Environment.
   */
  protected string $environment;

  /**
   * The message's placeholders parser.
   */
  protected LogMessageParserInterface $parser;

  /**
   * Mock user.
   */
  protected AccountInterface $currentUser;

  /**
   * Mock request stack.
   */
  protected RequestStack $requestStack;

  /**
   * Mock event dispatcher.
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Mock request fetcher.
   */
  protected RequestFetcherInterface $requestFetcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->parser = $this->createMock(LogMessageParserInterface::class);
    $this->environment = 'testing';
    $this->currentUser = $this->createMock(AccountInterface::class);
    $this->requestStack = $this->createMock(RequestStack::class);
    $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->requestFetcher = $this->createMock(RequestFetcherInterface::class);
  }

  /**
   * Data provider for testProxyConfiguration().
   *
   * @return mixed[]
   *   Data for the proxy configuration test.
   */
  public static function proxyConfigurationData(): array {
    return [
      // HTTP DSN, Empty proxy white-list.
      [
        'http://user@sentry.test/123456',
        ['http' => NULL, 'https' => NULL, 'no' => []],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        ['http' => 'http-proxy.server.test:3129', 'https' => NULL, 'no' => []],
        'http',
      ],
      [
        'http://user@sentry.test/123456',
        ['http' => NULL, 'https' => 'https-proxy.server.test:3129', 'no' => []],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => [],
        ],
        'http',
      ],
      // HTTP DSN, Not empty proxy white-list.
      [
        'http://user@sentry.test/123456',
        ['http' => NULL, 'https' => NULL, 'no' => ['some.server.test']],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => NULL,
          'no' => ['some.server.test'],
        ],
        'http',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test'],
        ],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test'],
        ],
        'http',
      ],
      // HTTP DSN, Not empty proxy white-list, Sentry white-listed.
      [
        'http://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => NULL,
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => NULL,
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'http://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      // HTTPS DSN, Empty proxy white-list.
      [
        'https://user@sentry.test/123456',
        ['http' => NULL, 'https' => NULL, 'no' => []],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        ['http' => 'http-proxy.server.test:3129', 'https' => NULL, 'no' => []],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        ['http' => NULL, 'https' => 'https-proxy.server.test:3129', 'no' => []],
        'https',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => [],
        ],
        'https',
      ],
      // HTTPS DSN, Not empty proxy white-list.
      [
        'https://user@sentry.test/123456',
        ['http' => NULL, 'https' => NULL, 'no' => ['some.server.test']],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => NULL,
          'no' => ['some.server.test'],
        ],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test'],
        ],
        'https',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test'],
        ],
        'https',
      ],
      // HTTPS DSN, Not empty proxy white-list, Sentry white-listed.
      [
        'https://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => NULL,
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => NULL,
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => NULL,
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
      [
        'https://user@sentry.test/123456',
        [
          'http' => 'http-proxy.server.test:3129',
          'https' => 'https-proxy.server.test:3129',
          'no' => ['some.server.test', 'sentry.test'],
        ],
        'no',
      ],
    ];
  }

  /**
   * Test proxy configuration.
   *
   * @param string $dsn
   *   Sentry DSN.
   * @param mixed[] $config
   *   Proxy configuration array.
   * @param string $proxy
   *   Proxy.
   *
   * @dataProvider proxyConfigurationData
   */
  public function testProxyConfiguration(string $dsn, array $config, string $proxy): void {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $configFactory */
    $configFactory = $this->getConfigFactoryStub([
      'raven.settings' => [
        'client_key' => $dsn,
        // We need this to avoid registering error handlers in
        // \Drupal\raven\Logger\Raven constructor.
        'fatal_error_handler' => FALSE,
      ],
    ]);

    $settings = new Settings([
      'http_client_config' => [
        'proxy' => $config,
      ],
    ]);

    $raven = new Raven($configFactory, $this->parser, $this->environment, $this->currentUser, $this->requestStack, $settings, $this->eventDispatcher, $this->requestFetcher);
    if ($proxy === 'no') {
      $this->assertNotNull($client = $raven->getClient(TRUE));
      $this->assertEmpty($client->getOptions()->getHttpProxy(), 'No proxy configured for Sentry\Client');
    }
    else {
      $this->assertNotNull($client = $raven->getClient(TRUE));
      $this->assertSame($config[$proxy], $client->getOptions()->getHttpProxy(), strtoupper($proxy) . ' proxy configured for Sentry\Client');
    }
  }

}
