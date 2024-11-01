<?php

namespace Drupal\azure_ai_faq_bot\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\LoggerChannelInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for generating Direct Line token.
 */
class WebChatController extends ControllerBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a WebChatController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelInterface $logger, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('logger.channel.default'),
      $container->get('http_client')
    );
  }

  /**
   * Generates a Direct Line token.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The Direct Line token.
   */
  public function generateToken(): JsonResponse {
    // Fetch the Direct Line secret from the block configuration.
    $config = $this->configFactory->get('azure_ai_faq_bot.settings');
    $direct_line_secret = $config->get('direct_line_secret');

    if (empty($direct_line_secret)) {
      $this->logger->error('Direct Line secret is not configured.');
      return new JsonResponse(['error' => 'Direct Line secret is not configured.'], 500);
    }

    try {
      $response = $this->httpClient->request('POST', 'https://directline.botframework.com/v3/directline/tokens/generate', [
        'headers' => [
          'Authorization' => 'Bearer ' . $direct_line_secret,
        ],
      ]);

      // Check if the response status code is 200 OK.
      if ($response->getStatusCode() !== 200) {
        $this->logger->error('Failed to fetch Direct Line token. HTTP status code: @code', ['@code' => $response->getStatusCode()]);
        return new JsonResponse(['error' => 'Failed to fetch Direct Line token.'], 500);
      }

      $data = json_decode($response->getBody()->getContents(), TRUE);

      // Validate the token in the response.
      if (empty($data['token'])) {
        $this->logger->error('Invalid response received from Direct Line API.');
        return new JsonResponse(['error' => 'Invalid response received from Direct Line API.'], 500);
      }

      return new JsonResponse(['token' => $data['token']]);
    }
    catch (\Exception $e) {
      $this->logger->error('Failed to fetch Direct Line token: @message', ['@message' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to fetch Direct Line token.'], 500);
    }

  }

}
