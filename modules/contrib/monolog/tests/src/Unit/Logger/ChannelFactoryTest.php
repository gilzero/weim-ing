<?php

declare(strict_types=1);

namespace Drupal\Tests\monolog\Unit\Logger;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\monolog\Logger\Handler\DrupalHandler;
use Drupal\monolog\Logger\LoggerInterfacesAdapter;
use Drupal\monolog\Logger\MonologLoggerChannelFactory;
use Drupal\Tests\UnitTestCase;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversDefaultClass \Drupal\monolog\Logger\MonologLoggerChannelFactory
 * @group monolog
 */
class ChannelFactoryTest extends UnitTestCase {

  /**
   * Make sure that the level gets translated before sent to processors.
   *
   * @covers ::getChannelInstance
   * @dataProvider providerTestGetChannelInstance
   */
  public function testGetChannelInstance(
    $handlers,
    $class,
    $container_get,
    $rotating_file_set_formatter,
    $drupal_set_formatter,
    $rotating_file_push_processor,
    $drupal_push_processor,
  ): void {
    $messenger = $this->createMock(MessengerInterface::class);
    $container = $this->createMock(ContainerInterface::class);
    $request_stack = $this->createMock(RequestStack::class);
    $account_proxy = $this->createMock(AccountProxy::class);
    $channelFactory = new MonologLoggerChannelFactory($request_stack, $account_proxy, $messenger, $container);
    $channelFactory
      ->setContainer(
        $this->getMockContainer(
          $handlers,
          $container_get,
          $rotating_file_set_formatter,
          $drupal_set_formatter,
          $rotating_file_push_processor,
          $drupal_push_processor,
        ),
      );

    $logger = $channelFactory->get('test');
    \assert($logger instanceof LoggerInterfacesAdapter);

    self::assertInstanceOf($class, $logger->getAdaptedLogger());
  }

  /**
   * Get a mocked service container.
   *
   * @return \Drupal\Core\DependencyInjection\Container
   *   A mocked service container.
   */
  protected function getMockContainer(
    $handlers,
    $container_get,
    $rotating_file_set_formatter,
    $drupal_set_formatter,
    $rotating_file_push_processor,
    $drupal_push_processor,
  ): Container {
    $container = $this->createMock('Drupal\Core\DependencyInjection\Container');

    $container->expects($this->any())
      ->method('hasParameter')
      ->willReturn(TRUE);

    $container->expects($this->any())
      ->method('getParameter')
      ->willReturnMap([
        [
          'monolog.channel_handlers',
          $handlers,
        ],
        [
          'monolog.processors',
          [
            'message_placeholder',
            'current_user',
            'request_uri',
            'ip',
            'referer',
            'filter_backtrace',
            'introspection',
          ],
        ],
        [
          'monolog.logger.processors',
          [],
        ],
      ]);

    $container->expects($this->any())
      ->method('has')
      ->willReturn(TRUE);

    $rotatingFileHandler = $this->createMock(RotatingFileHandler::class);

    $rotatingFileHandler->expects($this->exactly($rotating_file_set_formatter))
      ->method('setFormatter');
    $rotatingFileHandler->expects($this->exactly($rotating_file_push_processor))
      ->method('pushProcessor');

    $drupalHandler = $this->createMock(DrupalHandler::class);

    $drupalHandler->expects($this->exactly($drupal_set_formatter))
      ->method('setFormatter');
    $drupalHandler->expects($this->exactly($drupal_push_processor))
      ->method('pushProcessor');

    $lineFormatter = $this->createMock(LineFormatter::class);

    $jsonFormatter = $this->createMock(JsonFormatter::class);

    $container->expects($this->exactly($container_get))
      ->method('get')
      ->willReturnMap([
        [
          'monolog.handler.rotating_file',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $rotatingFileHandler,
        ],
        [
          'monolog.handler.drupal.dblog',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $drupalHandler,
        ],
        [
          'monolog.formatter.line',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $lineFormatter,
        ],
        [
          'monolog.formatter.json',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $jsonFormatter,
        ],
        [
          'monolog.processor.message_placeholder',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.current_user',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.request_uri',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.ip',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.referer',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.filter_backtrace',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
        [
          'monolog.processor.introspection',
          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE,
          $this->createMock(ProcessorInterface::class),
        ],
      ]);

    return $container;
  }

  /**
   * Data provider for self::testGetChannelInstance().
   */
  public function providerTestGetChannelInstance(): array {
    $empty = Yaml::parse(\file_get_contents(__DIR__ . '/examples/empty.yaml'));
    $invalid = Yaml::parse(\file_get_contents(__DIR__ . '/examples/invalid.yaml'));
    $simpleNoArray = Yaml::parse(\file_get_contents(__DIR__ . '/examples/simpleNoArray.yaml'));
    $simpleWrongHandlers = Yaml::parse(\file_get_contents(__DIR__ . '/examples/simpleWrongHandlers.yaml'));
    $simple = Yaml::parse(\file_get_contents(__DIR__ . '/examples/simple.yaml'));
    $multiple = Yaml::parse(\file_get_contents(__DIR__ . '/examples/multiple.yaml'));
    $simpleWithFormatter = Yaml::parse(\file_get_contents(__DIR__ . '/examples/simpleWithFormatter.yaml'));
    $multipleWithFormatter = Yaml::parse(\file_get_contents(__DIR__ . '/examples/multipleWithFormatter.yaml'));
    $simpleWithFormatterAndProcessors = Yaml::parse(\file_get_contents(__DIR__ . '/examples/simpleWithFormatterAndProcessors.yaml'));
    $multipleWithFormatterAndProcessors = Yaml::parse(\file_get_contents(__DIR__ . '/examples/multipleWithFormatterAndProcessors.yaml'));

    return [
      [$empty, NullLogger::class, 0, 0, 0, 0, 0],
      [$invalid, NullLogger::class, 0, 0, 0, 0, 0],
      [$simpleNoArray, NullLogger::class, 0, 0, 0, 0, 0],
      [$simpleWrongHandlers, NullLogger::class, 0, 0, 0, 0, 0],
      [$simple, Logger::class, 8, 0, 0, 7, 0],
      [$multiple, Logger::class, 16, 0, 0, 7, 7],
      [$simpleWithFormatter, Logger::class, 9, 1, 0, 7, 0],
      [$multipleWithFormatter, Logger::class, 18, 1, 1, 7, 7],
      [$simpleWithFormatterAndProcessors, Logger::class, 3, 1, 0, 1, 0],
      [$multipleWithFormatterAndProcessors, Logger::class, 6, 1, 1, 1, 1],
    ];
  }

}
