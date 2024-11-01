<?php

namespace Drupal\vertex_ai_search\Plugin\Autocomplete;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\vertex_ai_search\Plugin\VertexAutocompletePluginBase;
use Google\ApiCore\ApiException;
use Google\ApiCore\ValidationException;
use Google\Cloud\DiscoveryEngine\V1\Client\CompletionServiceClient;
use Google\Cloud\DiscoveryEngine\V1\CompleteQueryRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @VertexAutocompletePlugin(
 *   id = "vertex_autocomplete_vertex",
 *   title = @Translation("Vertex Autocomplete")
 * )
 */
class VertexAutocomplete extends VertexAutocompletePluginBase {

  /**
   * Messenger Service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MessengerInterface $messenger, LoggerChannelFactoryInterface $loggerFactory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->messenger = $messenger;
    $this->logger = $loggerFactory->get('vertex_ai_search');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('messenger'),
      $container->get('logger.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestions($keys) {

    $config = $this->getConfiguration();

    $formattedDataStore = CompletionServiceClient::dataStoreName(
      $config['google_cloud_project_id'],
      $config['google_cloud_location'],
      $config['vertex_ai_data_store_id']
    );

    $credPath = $config['service_account_credentials_file'];

    // Create a client.
    $completionServiceClient = new CompletionServiceClient([
      'credentials' => json_decode(file_get_contents($credPath), TRUE),
    ]);

    // Prepare the request message.
    $request = (new CompleteQueryRequest())
      ->setDataStore($formattedDataStore)
      ->setQuery($keys);

    // Call the API and handle any network failures.
    try {

      /** @var \Google\Cloud\DiscoveryEngine\V1\CompleteQueryResponse $response */
      $response = $completionServiceClient->completeQuery($request);
      $jsonResults = $response->serializeToJsonString();
      $suggestionResults = json_decode($jsonResults, TRUE);

      $suggestions = [];

      if (empty($suggestionResults['querySuggestions'])) {
        return $suggestions;
      }

      foreach ($suggestionResults['querySuggestions'] as $suggestion) {
        $suggestions[] = $suggestion['suggestion'];
      }

      return $suggestions;

    }
    catch (ApiException $ex) {

      $this->logger->error($ex);
      $this->messenger->addError("An exception occurred: " . $ex);

    }
    catch (ValidationException $ex) {

      $this->logger->error($ex);
      $this->messenger->addError("An exception occurred: " . $ex);

    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['autocomplete_model'] = [
      '#title' => $this->t('Autocomplete Model'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        'search_history' => $this->t('Search History'),
      ],
      '#default_value' => $this->configuration['autocomplete_model'] ?? NULL,
    ];

    return $form;

  }

}
