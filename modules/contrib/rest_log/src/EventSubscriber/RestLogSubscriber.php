<?php

namespace Drupal\rest_log\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\rest\ResourceResponse;
use Drupal\rest_log\RouteCheckManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Logs all rest responses/exceptions.
 */
class RestLogSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The exception if exists.
   *
   * @var \Throwable|null
   */
  protected $throwable;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private TimeInterface $time;

  /**
   * SplStack.
   *
   * @var \SplStack
   */
  private \SplStack $stack;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The rest log route check manager.
   *
   * @var \Drupal\rest_log\RouteCheckManagerInterface
   */
  protected $routeCheckManager;

  /**
   * Constructs an rest log subscriber.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\rest_log\RouteCheckManagerInterface $route_check_manager
   *   The rest log route check manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TimeInterface $time,
    LoggerChannelFactoryInterface $logger_factory,
    EntityTypeManagerInterface $entity_type_manager,
    RouteCheckManagerInterface $route_check_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->time = $time;
    $this->stack = new \SplStack();
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->routeCheckManager = $route_check_manager;
  }

  /**
   * Whether to log the requests with same-host referrer.
   *
   * @return bool
   *   TRUE if the requests with same-host referrer should be logged.
   */
  private function includeSameHost(): bool {
    return $this->configFactory->get('rest_log.settings')->get('include_same_host');
  }

  /**
   * Log the REST response.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event to process.
   */
  public function logResponse(KernelEvent $event): void {
    if (!$this->routeCheckManager->check()) {
      return;
    }
    $request = $event->getRequest();
    $response = $event->getResponse();
    // Do not log requests with same-host referrer if disabled.
    $referer = $request->headers->get('referer') ?? '';
    if ($request->getHost() === parse_url($referer, PHP_URL_HOST) && !$this->includeSameHost()) {
      return;
    }
    $this->stack->push([
      'request' => $request,
      'response' => $response,
    ]);
  }

  /**
   * Log the REST response.
   */
  public function terminate(): void {
    try {
      foreach ($this->stack as $item) {
        $this->doLogResponse($item['request'], $item['response']);
      }
    }
    catch (\Error $e) {
      $this->loggerFactory->get('rest_log')->log(
        RfcLogLevel::ERROR,
        'Rest log could not be added. (@e)',
        ['@e' => $e->getMessage()]
      );
    }
  }

  /**
   * Log the REST response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   */
  private function doLogResponse(Request $request, Response $response) {
    // Prepare the response log (in json format).
    $response_content_type = $response->headers->get('Content-type');
    $response_content = $response->getContent();
    switch ($response_content_type) {
      case 'application/octet-stream':
        // Do not log files.
        $responseBody['Content'] = 'File';
        break;

      case 'text/xml':
        $responseBody = (new XmlEncoder)->decode($response_content, 'array');
        break;

      default:
        $responseBody = Json::decode($response_content);
        break;
    }

    if ($this->throwable) {
      $responseBody['Exception'] = $this->throwable->getMessage();
    }

    // Prepare the request log.
    $request_content_type = $request->headers->get('Content-type');
    $request_content = $request->getContent();
    if ($request_content_type === 'application/octet-stream') {
      // Do not log files.
      $request_content = '';
    }

    $time = round(1000 * (microtime(TRUE) - $this->time->getRequestMicroTime()));

    $values = array_filter([
      'request_header' => print_r($this->cleanUpHeaders($request->headers->all()), TRUE),
      'request_method' => $request->getMethod(),
      'request_payload' => $request_content,
      'response_status' => $response->getStatusCode(),
      'response_header' => print_r($this->cleanUpHeaders($response->headers->all()), TRUE),
      'response_body' => Json::encode($responseBody),
      'request_uri' => substr($request->getUri(), 0, 2048),
      'request_cookie' => print_r($this->cleanUpCookies($request->cookies->all()), TRUE),
      'response_time' => $time,
    ]);

    try {
      $this->entityTypeManager->getStorage('rest_log')->create($values)->save();
    }
    catch (EntityStorageException $exception) {
      $this->loggerFactory->get('rest_log')->log(RfcLogLevel::ERROR, Error::DEFAULT_ERROR_MESSAGE, Error::decodeException($exception));
    }
  }

  /**
   * Return clear message on exception.
   *
   * @param \Symfony\Component\HttpKernel\Event\KernelEvent $event
   *   The event triggered by the request.
   */
  public function onException(KernelEvent $event): void {
    if (!$this->routeCheckManager->check()) {
      return;
    }
    $this->throwable = $event->getThrowable();
    $errorResponse = [
      'status' => 'error',
      'message' => $this->t('System error, please contact the site administrator.'),
    ];
    $response = new ResourceResponse($errorResponse);
    $event->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['logResponse', 1000];
    $events[KernelEvents::EXCEPTION][] = ['onException', -254];
    $events[KernelEvents::TERMINATE][] = ['terminate', 1000];
    return $events;
  }

  /**
   * Mask array of headers.
   *
   * @param array $headers
   *   An array of headers.
   *
   * @return array
   *   Sanitized array of headers.
   */
  protected function cleanUpHeaders(array $headers): array {
    foreach ($headers as $headerKey => $header) {
      if (is_array($header) && count($header) === 1) {
        $headers[$headerKey] = $header[0];
      }
      // Mask authorization headers since it's potential security issue.
      foreach (['auth', 'pass', 'token', 'cookie'] as $str) {
        if (stripos(strtolower($headerKey), $str) !== FALSE) {
          $pass_code = $headers[$headerKey];
          if ($pass_code && $headerKey == 'authorization' && preg_match('/\s+/', $pass_code) !== FALSE) {
            [$auth_scheme, $credentials] = preg_split('/\s+/', $pass_code);
            $headers[$headerKey] = $auth_scheme . ' ' . $this->maskString($credentials);
          }
          else {
            $headers[$headerKey] = $this->maskString($pass_code);
          }
        }
      }
    }
    return $headers;
  }

  /**
   * Mask array of cookies.
   *
   * @param array $all
   *   Array of cookies to sanitize.
   *
   * @return array
   *   Sanitized array of cookies.
   */
  protected function cleanUpCookies(array $all): array {
    foreach ($all as $key => $value) {
      if (
        strpos($key, 'SESS') === 0
        || strpos($key, 'SSESS') === 0
        || strpos($key, 'openid_connect') === 0
      ) {
        $all[$key] = $this->maskString($value);
      }
    }
    return $all;
  }

  /**
   * Mask string.
   *
   * @param string $string
   *   String.
   *
   * @return string
   *   Returns masked string.
   */
  private function maskString(string $string): string {
    return substr($string, 0, 3) . '*********';
  }

}
