<?php

namespace Drupal\raven\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\raven\Event\OptionsAlter;
use Drupal\raven\Logger\RavenInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Sentry\Severity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Sends test message to Sentry.
 */
class TestController extends ControllerBase {

  /**
   * Constructs a new TestController instance.
   */
  final public function __construct(
    protected EventDispatcherInterface $eventDispatcher,
    protected RavenInterface $logger,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('logger.raven'),
    );
  }

  /**
   * Sends a test message to Sentry.
   */
  public function doTest(Request $request): JsonResponse {
    $logger = $this->createLogger();
    $listener = fn(OptionsAlter $event) => $event->options['logger'] = $logger;
    // Add listener to modify Sentry client options.
    $this->eventDispatcher->addListener(OptionsAlter::class, $listener);
    // Initialize a new Sentry client with debug logging enabled.
    $this->logger->getClient(TRUE);
    $dateTime = new \DateTime();
    $id = \Sentry\captureMessage($this->t('Test message @time.', ['@time' => $dateTime->format('r')]), new Severity('info'));
    return new JsonResponse(['id' => (string) $id, 'log' => $logger->log ?? []]);
  }

  /**
   * Returns a debug logger.
   */
  public function createLogger(): LoggerInterface {
    return new class() implements LoggerInterface {
      use LoggerTrait;
      /**
       * Array of log messages.
       *
       * @var array{'level': string, 'message': string}
       */
      public array $log;

      /**
       * {@inheritdoc}
       */
      public function log(mixed $level, string|\Stringable $message, array $context = []): void {
        $this->log[] = compact('level', 'message');
      }

    };
  }

}
