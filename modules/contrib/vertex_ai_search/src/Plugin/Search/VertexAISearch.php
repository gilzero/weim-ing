<?php

namespace Drupal\vertex_ai_search\Plugin\Search;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\search\Plugin\ConfigurableSearchPluginBase;
use Drupal\search\SearchPageRepositoryInterface;
use Drupal\vertex_ai_search\VertexAutocompletePluginManager;
use Drupal\vertex_ai_search\VertexSearchFilterPluginManager;
use Drupal\vertex_ai_search\VertexSearchResultsPluginManager;
use Google\ApiCore\ApiException;
use Google\Cloud\DiscoveryEngine\V1\Client\SearchServiceClient;
use Google\Cloud\DiscoveryEngine\V1\SearchRequest;
use Google\Cloud\DiscoveryEngine\V1\SearchRequest\SpellCorrectionSpec;
use Google\Cloud\DiscoveryEngine\V1\SearchRequest\SpellCorrectionSpec\Mode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles searching for node entities using the Search module index.
 *
 * @SearchPlugin(
 *   id = "vertex_ai_search",
 *   title = @Translation("Vertex AI Search")
 * )
 */
class VertexAISearch extends ConfigurableSearchPluginBase {

  /**
   * Search Page Repository Service.
   *
   * @var \Drupal\search\SearchPageRepositoryInterface
   */
  protected $searchPageRepository;

  /**
   * Vertex Autocomplete Plugin Manager.
   *
   * @var \Drupal\vertex_ai_search\VertexAutocompletePluginManager
   */
  protected $autoPluginManager;

  /**
   * Vertex Search Results Plugin Manager.
   *
   * @var \Drupal\vertex_ai_search\VertexSearchResultsPluginManager
   */
  protected $resultsPluginManager;

  /**
   * Vertex Search Filter Plugin Manager.
   *
   * @var \Drupal\vertex_ai_search\VertexSearchFilterPluginManager
   */
  protected $filterPluginManager;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenManager;

  /**
   * PagerManager service object.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Module Extension List.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration array containing information about search page.
   * @param string $plugin_id
   *   Identifier of custom plugin.
   * @param array $plugin_definition
   *   Provides definition of search plugin.
   * @param \Drupal\search\SearchPageRepositoryInterface $searchPageRepository
   *   Repository for the search page.
   * @param \Drupal\vertex_ai_search\VertexAutocompletePluginManager $autoPluginManager
   *   Vertex Autocomplete Plugin Manager.
   * @param \Drupal\vertex_ai_search\VertexSearchResultsPluginManager $resultsPluginManager
   *   Vertex Search Results Plugin Manager.
   * @param \Drupal\vertex_ai_search\VertexSearchFilterPluginManager $filterPluginManager
   *   Vertex Search Filter Plugin Manager.
   * @param \Drupal\Core\Utility\Token $tokenManager
   *   For managing Tokens.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   Pager Manager.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module Extension List.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    SearchPageRepositoryInterface $searchPageRepository,
    VertexAutocompletePluginManager $autoPluginManager,
    VertexSearchResultsPluginManager $resultsPluginManager,
    VertexSearchFilterPluginManager $filterPluginManager,
    Token $tokenManager,
    PagerManagerInterface $pagerManager,
    ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->searchPageRepository = $searchPageRepository;
    $this->autoPluginManager = $autoPluginManager;
    $this->resultsPluginManager = $resultsPluginManager;
    $this->filterPluginManager = $filterPluginManager;
    $this->tokenManager = $tokenManager;
    $this->pagerManager = $pagerManager;
    $this->moduleExtensionList = $moduleExtensionList;
    $this->configuration = $this->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('search.search_page_repository'),
      $container->get('plugin.manager.vertex_autocomplete'),
      $container->get('plugin.manager.vertex_search_results'),
      $container->get('plugin.manager.vertex_search_filter'),
      $container->get('token'),
      $container->get('pager.manager'),
      $container->get('extension.list.module')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {

    // Retrieve credentials and create a search client.
    // @see https://github.com/googleapis/google-cloud-php/blob/main/AUTHENTICATION.md.
    $modulepath = $this->moduleExtensionList->getPath('vertex_ai_search');
    $credPath = $this->configuration['service_account_credentials_file'];
    $searchServiceClient = new SearchServiceClient([
      'credentials' => json_decode(file_get_contents($credPath), TRUE),
    ]);

    // Configure Search Client with serving configuration.
    // @see: https://cloud.google.com/php/docs/reference/cloud-discoveryengine/0.4.0/V1.Client.SearchServiceClient.
    $formattedServingConfig = SearchServiceClient::servingConfigName(
      $this->configuration['google_cloud_project_id'],
      $this->configuration['google_cloud_location'],
      $this->configuration['vertex_ai_data_store_id'],
      $this->configuration['vertex_ai_serving_config']
    );

    // Prepare the search request.
    $request = (new SearchRequest())->setServingConfig($formattedServingConfig);

    // Set Keywords to be used in request query.
    $request->setQuery($this->getKeywords());

    // Set results per page.
    $request->setPageSize($this->configuration['resultsPerPage']);

    // Specify if safe search is on or off.
    $safeSearch = !empty($this->configuration['safeSearch']) ? TRUE : FALSE;
    $request->setSafeSearch($safeSearch);

    // Specify the spelling correction mode (automatic or not).
    $spellCorrection = new SpellCorrectionSpec();
    $spellCorrection->setMode(MODE::value($this->configuration['spelling_correction_mode']));
    $request->setSpellCorrectionSpec($spellCorrection);

    // Set offset (starting point) of search request.
    $parameters = $this->getParameters();
    $page = $parameters['page'] ?? 0;
    $offset = $this->configuration['resultsPerPage'] * $page;
    $request->setOffset($offset);

    // Set filter for search request if configured.
    $filter = $this->getPluginFilter();
    if ($filter) {
      $request->setFilter($filter);
    }

    // Perform the search and retrieve results.
    $results = $this->performSearch($searchServiceClient, $request);

    /* @todo The Drupal\search\Plugin\SearchInterface says this should return
     * a structured list of search results.  How do we pare this down to just
     * results?
     */
    return $results;

  }

  /**
   * {@inheritdoc}
   */
  public function buildResults() {
    $results = $this->execute();
    $output = [];

    // See if the query was corrected.
    if (!empty($results['correctedQuery'])) {
      $url = Url::fromRoute('<current>', [
        'keys' => $results['correctedQuery'],
      ]);

      $output[] = [
        '#theme' => 'vertex_ai_search_spelling_correction',
        '#spelling' => $results['correctedQuery'],
        '#corrected' => $results['queryCorrected'],
        '#url' => $url,
        '#term' => $this->getKeywords(),
        '#plugin_id' => $this->getPluginId(),
        '#attached' => [
          'library' => [
            'vertex_ai_search/vertexAiSearchResults',
          ],
        ],
      ];

      // Modify search keywords if they were automatically corrected.
      if ($results['queryCorrected']) {
        $this->setSearch($results['correctedQuery'], $this->getParameters(), $this->getAttributes());
      }
    }

    if (empty($results['results'])) {
      return $output;
    }

    // Get query parameters.
    $parameters = $this->getParameters();

    // Retrieve the starting result count and ending count for the page.
    $page = $parameters['page'] ?? 0;
    $startCount = ($page * $this->configuration['resultsPerPage']) + 1;
    $endCount = $startCount + count($results['results']) - 1;

    // Populate the data array used to populate Vertex AI Search custom tokens.
    $tokens['vertex_ai_search'] = [
      'vertex_ai_search_keywords' => $this->getKeywords(),
      'vertex_ai_search_result_start' => $startCount,
      'vertex_ai_search_result_end' => $endCount,
      'vertex_ai_search_page' => $this->configuration['label'],
    ];

    // Display the results message - configurable on the search page.
    $resultsMessage = $this->tokenManager->replace($this->configuration['results_message'], $tokens);

    if (count($results['results']) == 1 && !empty($this->configuration['results_message_singular'])) {
      $resultsMessage = $this->tokenManager->replace($this->configuration['results_message_singular'], $tokens);
    }

    if (!empty($resultsMessage)) {
      $output[] = [
        '#theme' => 'vertex_ai_search_results_message',
        '#message' => $resultsMessage,
        '#term' => $this->getKeywords(),
        '#plugin_id' => $this->getPluginId(),
        '#attached' => [
          'library' => [
            'vertex_ai_search/vertexAiSearchResults',
          ],
        ],
      ];
    }

    foreach ($results['results'] as $result) {
      $decodedResult = json_decode($result, TRUE);
      $derivedStructData = $decodedResult['document']['derivedStructData'];
      $parsedResult['htmlTitle'] = $derivedStructData['htmlTitle'];
      $parsedResult['link'] = $derivedStructData['link'];
      $parsedResult['snippet'] = NULL;

      if ($this->configuration['result_parts'] === 'SNIPPETS') {
        $parsedResult['snippet'] = $derivedStructData['snippets'][0]['htmlSnippet'];
      }

      // Remove the domain if the feature is enabled.
      if (!empty($this->configuration['removeDomain'])) {
        $url_parts = parse_url($parsedResult['link']);
        $new_link = str_replace($url_parts['scheme'] . '://' . $url_parts['host'], '', $parsedResult['link']);
        $parsedResult['link'] = $new_link;
      }

      $output[] = [
        '#theme' => 'vertex_ai_search_result',
        '#result' => $parsedResult,
        '#term' => $this->getKeywords(),
        '#plugin_id' => $this->getPluginId(),
        '#attached' => [
          'library' => [
            'vertex_ai_search/vertexAiSearchResults',
          ],
        ],
      ];
    }

    // Retrieve any VertexSearchResultPlugins and modified page results.
    $resultsPluginDefinitions = $this->resultsPluginManager->getDefinitions();

    foreach ($resultsPluginDefinitions as $pluginKey => $pluginDefinition) {

      $resultsPlugin = $this->resultsPluginManager->createInstance(
        $pluginKey
      );

      $output = $resultsPlugin->modifyPageResults($this->getKeywords(), $output, $this->configuration['id']);

    }

    // Use the lesser of total results and total results limit.
    $totResults =
      ($results['totalResults'] < $this->configuration['totalResultsLimit'])
      ? $results['totalResults'] : $this->configuration['totalResultsLimit'];

    $this->pagerManager->createPager(
      $totResults,
      $this->configuration['resultsPerPage'],
      0
    );

    return $output;

  }

  /**
   * {@inheritdoc}
   */
  public function searchFormAlter(array &$form, FormStateInterface $form_state) {

    unset($form['basic']);

    if (!$this->configuration['displaySearchForm']) {
      return;
    }

    $form['#attributes']['class'][] = 'vertex-ai-search-search-box-form';
    $form['#attributes']['id'] = 'vertex-ai-search-search-box-form';
    $form['#theme'] = 'vertex_ai_search_search_page_form';

    // Search term element.
    $form['keys'] = [
      '#type' => 'search',
      '#title' => $this->t('Search'),
      '#title_display' => 'none',
      '#default_value' => $this->getKeywords(),
    ];

    // Determine if Autocomplete should be enabled or not.
    if (!empty($this->configuration['autocomplete_enable']) &&
      !empty($this->configuration['autocomplete_source'])) {
      $form['keys']['#autocomplete_route_name'] = 'vertex_ai_search.autocomplete';
      $form['keys']['#autocomplete_route_parameters'] = ['search_page_id' => $this->configuration['id']];
    }

    // Add class attributes to search keys input element.
    if (!empty($this->configuration['classSearchKeys'])) {
      $form['keys']['#attributes']['class'][] = $this->configuration['classSearchKeys'];
    }

    // Add aria label attribute to search keys input element.
    if (!empty($this->configuration['searchInputAriaLabel'])) {
      $form['keys']['#attributes']['aria-label'] = $this->configuration['searchInputAriaLabel'];
    }

    // Search submit element.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Search',
    ];

    // Add class attributes to search submit button.
    if (!empty($this->configuration['classSearchSubmit'])) {
      $form['submit']['#attributes']['class'][] = $this->configuration['classSearchSubmit'];
    }

    // Add aria label to search submit button.
    if (!empty($this->configuration['searchSubmitAriaLabel'])) {
      $form['submit']['#attributes']['aria-label'] = $this->configuration['searchSubmitAriaLabel'];
    }

  }

  /**
   * {@inheritdoc}
   */
  public function buildSearchUrlQuery(FormStateInterface $form_state) {

    return [
      'keys' => $form_state->getValue('keys'),
      'searchPage' => $form_state->getValue('searchPage'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form += $this->retrieveVertexAuthenticationElements($form, $form_state);

    $form += $this->retrieveVertexServingConfigElements($form, $form_state);

    $form += $this->retrieveVertexAutocompleteElements($form, $form_state);

    $form += $this->retrieveVertexSearchOptionElements($form, $form_state);

    $form += $this->retrieveVertexSearchFilterElements($form, $form_state);

    $form += $this->retrieveVertexSearchMessageElements($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    // The entity is a Drupal\search\Entity\SearchPage entity.
    // The getPlugin method of SearchPage returns ref to its plugin property.
    $plugin = $form_state->getFormObject()->getEntity()->getPlugin();

    // Update the SearchEntity plugin configuration.
    $plugin->setConfiguration($form_state->getValues());

    if (!empty($this->configuration['autocomplete_source'])) {

      $autoPlugin = $this->autoPluginManager->createInstance(
        $this->configuration['autocomplete_source'],
        $this->configuration
      );

      // Call the submit function on the autocomplete plugin.
      if ($autoPlugin instanceof PluginFormInterface) {
        $autoPlugin->submitConfigurationForm($form, $form_state);
      }

    }

  }

  /**
   * Gets the formatted filter from the selected plugin.
   *
   * @return false|string
   *   The formatted filter or FALSE.
   */
  protected function getPluginFilter() {

    if (empty($this->configuration['filter_enable']) || empty($this->configuration['filter_plugin'])) {
      return FALSE;
    }

    $filterPlugin = NULL;
    try {
      $filterPlugin = $this->filterPluginManager->createInstance(
        $this->configuration['filter_plugin'],
        $this->configuration
      );
    }
    catch (PluginException $e) {
      printf('Unable to create filter plugin instance: %s', $e->getMessage());
    }

    return $filterPlugin->getSearchFilter();

  }

  /**
   * Helper function to perform the search.
   *
   * @param \Google\Cloud\DiscoveryEngine\V1\Client\SearchServiceClient $searchServiceClient
   *   Vertex AI Search Service Client.
   * @param \Google\Cloud\DiscoveryEngine\V1\SearchRequest $request
   *   Vertex AI Search Request.
   */
  protected function performSearch(SearchServiceClient $searchServiceClient, SearchRequest $request) {

    $results = [];

    // Call the API and handle any network failures.
    try {

      /** @var \Google\ApiCore\PagedListResponse $response */
      $response = $searchServiceClient->search($request);

      $page = $response->getPage();

      $responseObject = $page->getResponseObject();
      $results['totalResults'] = $responseObject->getTotalSize();
      $results['pageResultCount'] = $page->getPageElementCount();
      $results['correctedQuery'] = $responseObject->getCorrectedQuery();

      // Determine query correction status.
      $results['queryCorrected'] = FALSE;
      $correctConfig = $this->configuration['spelling_correction_mode'];
      if (!empty($results['correctedQuery']) &&
         ($correctConfig === 'AUTO' || $correctConfig === 'MODE_UNSPECIFIED')) {
        $results['queryCorrected'] = TRUE;
      }

      /** @var \Google\Cloud\DiscoveryEngine\V1\SearchResponse\SearchResult $result */
      foreach ($page as $result) {
        $results['results'][] = $result->serializeToJsonString();
      }

    }
    catch (ApiException $ex) {
      printf('Call failed with message: %s' . PHP_EOL, $ex->getMessage());
    }

    return $results;

  }

  /**
   * Get the value from example select field and fill.
   *
   * @return array
   *   New autocomplete option elements.
   */
  public function retrieveAutocompleteOptions(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (empty($values['autocomplete_source'])) {

      $form['vertex_ai_search_autocomplete']['auto_options'] =
        array_diff_key(
          $form['vertex_ai_search_autocomplete']['auto_options'],
          array_flip($values['pluginElements'])
        );

      $form['vertex_ai_search_autocomplete']['auto_options']['pluginElements']['#value'] = [];

    }

    // Return the autocomplete options.
    return $form['vertex_ai_search_autocomplete']['auto_options'];

  }

  /**
   * Get the elements from the selected plugin's config form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the configuration form.
   *
   * @return array
   *   New search filter option elements.
   */
  public function retrieveFilterOptions(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    if (empty($values['filter_plugin'])) {

      $form['vertex_ai_search_filter']['filter_options'] =
        array_diff_key(
          $form['vertex_ai_search_filter']['filter_options'],
          array_flip($values['pluginElements'])
        );

      $form['vertex_ai_search_filter']['filter_options']['pluginElements']['#value'] = [];
    }

    return $form['vertex_ai_search_filter']['filter_options'];
  }

  /**
   * Helper to add vertex authentication elements to config form.
   *
   * @param array $form
   *   The configuration Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state of configuration form.
   */
  private function retrieveVertexAuthenticationElements(array $form, FormStateInterface $form_state) {

    $form['vertex_ai_authentication'] = [
      '#title' => $this->t('Vertex AI Agent Builder Authentication'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Path and name of Service Account credentials file.
    $form['vertex_ai_authentication']['service_account_credentials_file'] = [
      '#title' => $this->t('Service Account Credentials'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['service_account_credentials_file'] ?? '',
      '#required' => TRUE,
    ];

    return $form;

  }

  /**
   * Helper to add vertex app configuration elements to config form.
   *
   * @param array $form
   *   The configuration Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state of configuration form.
   */
  private function retrieveVertexServingConfigElements(array $form, FormStateInterface $form_state) {

    $form['vertex_ai_config_info'] = [
      '#title' => $this->t('Vertex AI Agent Builder Serving Config'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Vertex AI Search Google Cloud Platform project id.
    $form['vertex_ai_config_info']['google_cloud_project_id'] = [
      '#title' => $this->t('Google Cloud Project ID'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['google_cloud_project_id'] ?? '',
      '#required' => TRUE,
    ];

    // Vertex AI Search Google Cloud Platform application cloud location.
    $form['vertex_ai_config_info']['google_cloud_location'] = [
      '#title' => $this->t('Google Cloud Location'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['google_cloud_location'] ?? 'global',
      '#required' => TRUE,
    ];

    // Vertex AI Search Google Cloud Platform data store.
    $form['vertex_ai_config_info']['vertex_ai_data_store_id'] = [
      '#title' => $this->t('Vertex AI Data Store ID'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['vertex_ai_data_store_id'] ?? '',
      '#required' => TRUE,
    ];

    // Vertex AI Search Google Cloud Platform Serving configuration.
    $form['vertex_ai_config_info']['vertex_ai_serving_config'] = [
      '#title' => $this->t('Vertex AI Serving Configuration'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['vertex_ai_serving_config'] ?? 'default_search',
      '#required' => TRUE,
    ];

    return $form;

  }

  /**
   * Helper to add vertex autocomplete elements to config form.
   *
   * @param array $form
   *   The configuration Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state of configuration form.
   */
  private function retrieveVertexAutocompleteElements(array $form, FormStateInterface $form_state) {

    $form['vertex_ai_search_autocomplete'] = [
      '#title' => $this->t('Search Autocomplete'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Enable Autocomplete (or not).
    $form['vertex_ai_search_autocomplete']['autocomplete_enable'] = [
      '#title' => $this->t('Enable Autocomplete?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['autocomplete_enable'] ?? '',
      '#description' => $this->t('Enable an autocomplete mechanism.'),
    ];

    // Enable Autocomplete (or not) on core search block form.
    $form['vertex_ai_search_autocomplete']['autocomplete_enable_block'] = [
      '#title' => $this->t('Enable Autocomplete on Core Search Block Form?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['autocomplete_enable_block'] ?? '',
      '#description' => $this->t(
        'Enable autocomplete on search block form as well as main search form.'
      ),
      '#states' => [
        'visible' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get Available Vertex Autocomplete Plugins.
    $autoDefinitions = $this->autoPluginManager->getDefinitions();
    $autoOptions[NULL] = $this->t('-- Select an Autocomplete Plugin --');

    foreach ($autoDefinitions as $key => $definition) {
      $autoOptions[$key] = $definition['title'];
    }

    // Trigger Length.
    $form['vertex_ai_search_autocomplete']['autocomplete_trigger_length'] = [
      '#title' => $this->t('Autocomplete Trigger Length'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $this->configuration['autocomplete_trigger_length'] ?? 4,
      '#states' => [
        'visible' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Max Autocomplete Suggestions.
    $form['vertex_ai_search_autocomplete']['autocomplete_max_suggestions'] = [
      '#title' => $this->t('Max Suggestions'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $this->configuration['autocomplete_max_suggestions'] ?? 10,
      '#states' => [
        'visible' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Autocomplete Source (plugin).
    $form['vertex_ai_search_autocomplete']['autocomplete_source'] = [
      '#title' => $this->t('Autocomplete Source'),
      '#type' => 'select',
      '#options' => $autoOptions,
      '#default_value' => $this->configuration['autocomplete_source'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'retrieveAutocompleteOptions'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'auto-options-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Retrieving Options...'),
        ],
      ],
    ];

    // Autocomplete Plugin-Specific Options.
    $form['vertex_ai_search_autocomplete']['auto_options'] = [
      '#title' => $this->t('Autocomplete Options'),
      '#type' => 'details',
      '#open' => TRUE,
      '#prefix' => '<div id="auto-options-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="autocomplete_enable"]' => ['checked' => TRUE],
          ':input[name="autocomplete_source"]' => ['!value' => ''],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $autoPlugin = NULL;

    if (!empty($this->configuration['autocomplete_source'])) {
      $autoPlugin = $this->autoPluginManager->createInstance(
        $this->configuration['autocomplete_source'],
        $this->configuration
      );
    }

    $currentForm = $form;
    if ($autoPlugin instanceof PluginFormInterface) {
      $form += $autoPlugin->buildConfigurationForm($form, $form_state);
    }

    $autocompleteElements = array_diff_key($form, $currentForm);

    foreach ($autocompleteElements as $key => $element) {
      $form['vertex_ai_search_autocomplete']['auto_options'][$key] = $element;
      unset($form[$key]);
    }

    $form['vertex_ai_search_autocomplete']['auto_options']['pluginElements'] = [
      '#type' => 'hidden',
      '#value' => array_keys($autocompleteElements),
    ];

    // Manipulate form to display appropriate autocomplete plug options.
    if ($newSource = $form_state->getValue('autocomplete_source')) {

      // Remove unneeded elements from previous autocomplete_source.
      $remove = array_keys($autocompleteElements);
      $form['vertex_ai_search_autocomplete']['auto_options'] = array_diff_key(
        $form['vertex_ai_search_autocomplete']['auto_options'],
        array_flip($remove)
      );
      $this->SetConfiguration(array_diff_key($this->configuration, array_flip($remove)));

      // Set autocomplete source based on form_state value ($newSource).
      $this->configuration['autocomplete_source'] = $newSource;

      $autoPlugin = $this->autoPluginManager->createInstance($newSource, $this->configuration);

      $currentForm = $form;
      if ($autoPlugin instanceof PluginFormInterface) {
        $form += $autoPlugin->buildConfigurationForm($form, $form_state);
      }

      // Get an array of form elements specific to the autocomplete plugin.
      $autocompleteElements = array_diff_key($form, $currentForm);

      $form['vertex_ai_search_autocomplete']['autocomplete_source']['#default_value'] = $newSource;

      // Make sure autocomplete plugin-specific elements in auto_options group.
      foreach ($autocompleteElements as $key => $element) {
        $form['vertex_ai_search_autocomplete']['auto_options'][$key] = $element;
        $form['vertex_ai_search_autocomplete']['auto_options'][$key]['#default_value'] = $this->configuration[$key] ?? NULL;
      }

      // Keep a record of autocomplete-plugin specific elements.
      $form['vertex_ai_search_autocomplete']['auto_options']['pluginElements'] = [
        '#type' => 'hidden',
        '#value' => array_keys($autocompleteElements),
      ];

    }

    return $form;

  }

  /**
   * Helper to add vertex search option elements to config form.
   *
   * @param array $form
   *   The configuration Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state of configuration form.
   */
  private function retrieveVertexSearchOptionElements(array $form, FormStateInterface $form_state) {

    $form['vertex_ai_search_options'] = [
      '#title' => $this->t('Search Results Page Display Options'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Results per page.
    $form['vertex_ai_search_options']['resultsPerPage'] = [
      '#title' => $this->t('Number of results per page'),
      '#type' => 'select',
      '#options' => [
        10 => 10,
        20 => 20,
        30 => 30,
        40 => 40,
        50 => 50,
      ],
      '#default_value' => $this->configuration['resultsPerPage'] ?? '',
    ];

    // Max number of results to retrieve.
    $form['vertex_ai_search_options']['totalResultsLimit'] = [
      '#title' => $this->t('Total Results Limit'),
      '#type' => 'number',
      '#min' => 1,
      '#step' => 1,
      '#default_value' => $this->configuration['totalResultsLimit'] ?? 100,
    ];

    // Vertex Spelling-correction mode.
    $form['vertex_ai_search_options']['spelling_correction_mode'] = [
      '#title' => $this->t('Spelling Correction Mode'),
      '#type' => 'select',
      '#options' => [
        'AUTO' => 'AUTO',
        'SUGGESTION_ONLY' => 'SUGGESTION ONLY',
        'MODE_UNSPECIFIED' => 'MODE UNSPECIFIED (Defaults to AUTO)',
      ],
      '#default_value' => $this->configuration['spelling_correction_mode'] ?? '',
    ];

    // Result display options.
    $form['vertex_ai_search_options']['result_parts'] = [
      '#title' => $this->t('Result Output'),
      '#type' => 'select',
      '#options' => [
        'TITLE' => 'Title Only',
        'SNIPPETS' => 'Title and Snippet',
      ],
      '#default_value' => $this->configuration['result_parts'] ?? 'SNIPPETS',
    ];

    // Vertex AI Search SafeSearch option.
    $form['vertex_ai_search_options']['safeSearch'] = [
      '#title' => $this->t('Use SafeSearch?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['safeSearch'] ?? '',
      '#description' => $this->t('SafeSearch helps filter out explicit content in search results.'),
    ];

    // Remove domain from results - good for development environments.
    $form['vertex_ai_search_options']['removeDomain'] = [
      '#title' => $this->t('Remove Domain from Result Link URLs'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['removeDomain'] ?? '',
      '#description' => $this->t('Removing the domain makes all search results relative links, instead of pointing to the production domain. Useful when working with non-production domains.'),
    ];

    // Should search form be on SERP.
    $form['vertex_ai_search_options']['displaySearchForm'] = [
      '#title' => $this->t('Display Search Form on results page'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['displaySearchForm'] ?? '',
    ];

    // Class to associate with search key input element.
    $form['vertex_ai_search_options']['classSearchKeys'] = [
      '#title' => $this->t('Keywords Input Class'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['classSearchKeys'] ?? '',
      '#description' => $this->t('Add optional class to the keywords input element.'),
      '#states' => [
        'visible' => [
          ':input[name="displaySearchForm"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Aria Label to associate with search key input element.
    $form['vertex_ai_search_options']['searchInputAriaLabel'] = [
      '#title' => $this->t('Keywords Input Aria Label'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['searchInputAriaLabel'] ?? '',
      '#description' => $this->t('Add optional aria-label attribute the keywords input element.'),
      '#states' => [
        'visible' => [
          ':input[name="displaySearchForm"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Class to associate with search submit button.
    $form['vertex_ai_search_options']['classSearchSubmit'] = [
      '#title' => $this->t('Submit Input Class'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['classSearchSubmit'] ?? '',
      '#description' => $this->t('Add optional class to the submit input element.'),
      '#states' => [
        'visible' => [
          ':input[name="displaySearchForm"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Aria label to associate with search submit button.
    $form['vertex_ai_search_options']['searchSubmitAriaLabel'] = [
      '#title' => $this->t('Submit Input Aria Label'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['searchSubmitAriaLabel'] ?? '',
      '#description' => $this->t('Add optional aria-label attribute the submit input element.'),
      '#states' => [
        'visible' => [
          ':input[name="displaySearchForm"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;

  }

  /**
   * Helper to add vertex search filter elements to config form.
   *
   * @param array $form
   *   The configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the configuration form.
   *
   * @return array
   *   The elements to be added.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function retrieveVertexSearchFilterElements(array $form, FormStateInterface $form_state) {

    $form['vertex_ai_search_filter'] = [
      '#title' => $this->t('Search Filter'),
      '#type' => 'details',
      '#open' => TRUE,
    ];

    // Enable filtering.
    $form['vertex_ai_search_filter']['filter_enable'] = [
      '#title' => $this->t('Enable Filter?'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['filter_enable'] ?? '',
      '#description' => $this->t('Enable search filter.'),
    ];

    // Get available filter plugins.
    $filterDefinitions = $this->filterPluginManager->getDefinitions();
    $filterOptions[NULL] = $this->t('-- Select a Filter Plugin --');

    foreach ($filterDefinitions as $pluginKey => $pluginDefinition) {
      $filterOptions[$pluginKey] = $pluginDefinition['title'];
    }

    $form['vertex_ai_search_filter']['filter_plugin'] = [
      '#title' => $this->t('Filter Plugin'),
      '#type' => 'select',
      '#options' => $filterOptions,
      '#default_value' => $this->configuration['filter_plugin'] ?? NULL,
      '#states' => [
        'visible' => [
          ':input[name="filter_enable"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="filter_enable"]' => ['checked' => TRUE],
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'retrieveFilterOptions'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'filter-options-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Retrieving Options...'),
        ],
      ],
    ];

    // Filter plugin-specific options.
    $form['vertex_ai_search_filter']['filter_options'] = [
      '#title' => $this->t('Filter Options'),
      '#type' => 'details',
      '#open' => TRUE,
      '#prefix' => '<div id="filter-options-wrapper">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="filter_enable"]' => ['checked' => TRUE],
          ':input[name="filter_plugin"]' => ['!value' => ''],
        ],
      ],
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    $filterPlugin = NULL;

    if (!empty($this->configuration['filter_plugin'])) {
      $filterPlugin = $this->filterPluginManager->createInstance(
        $this->configuration['filter_plugin'],
        $this->configuration
      );
    }

    $currentForm = $form;
    if ($filterPlugin instanceof PluginFormInterface) {
      $form += $filterPlugin->buildConfigurationForm($form, $form_state);
    }

    $filterElements = array_diff_key($form, $currentForm);

    foreach ($filterElements as $filterKey => $filterElement) {
      $form['vertex_ai_search_filter']['filter_options'][$filterKey] = $filterElement;
      unset($form[$filterKey]);
    }

    $form['vertex_ai_search_filter']['filter_options']['pluginElements'] = [
      '#type' => 'hidden',
      '#value' => array_keys($filterElements),
    ];

    // Manipulate form to display appropriate filter plugin options.
    if ($newFilter = $form_state->getValue('filter_plugin')) {

      // Remove unneeded elements from previous filter_plugin.
      $remove = array_keys($filterElements);
      $form['vertex_ai_search_filter']['filter_options'] = array_diff_key($form['vertex_ai_search_filter']['filter_options'], array_flip($remove));
      $this->SetConfiguration(array_diff_key($this->configuration, array_flip($remove)));

      // Set filter plugin based on form_state value ($newFilter).
      $this->configuration['filter_plugin'] = $newFilter;

      $filterPlugin = $this->filterPluginManager->createInstance($newFilter, $this->configuration);

      $currentForm = $form;
      if ($filterPlugin instanceof PluginFormInterface) {
        $form += $filterPlugin->buildConfigurationForm($form, $form_state);
      }

      // Get an array of form elements specific to the filter plugin.
      $filterElements = array_diff_key($form, $currentForm);

      $form['vertex_ai_search_filter']['filter_plugin']['#default_value'] = $newFilter;

      // Make sure filter plugin-specific elements in filter_options group.
      foreach ($filterElements as $filterKey => $filterElement) {
        $form['vertex_ai_search_filter']['filter_options'][$filterKey] = $filterElement;
        $form['vertex_ai_search_filter']['filter_options'][$filterKey]['#default_value'] = $this->configuration[$filterKey] ?? NULL;
      }

      // Keep a record of filter plugin-specific elements.
      $form['vertex_ai_search_filter']['filter_options']['pluginElements'] = [
        '#type' => 'hidden',
        '#value' => array_keys($filterElements),
      ];

    }

    return $form;

  }

  /**
   * Helper to add vertex search message elements to config form.
   *
   * @param array $form
   *   The configuration Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state of configuration form.
   */
  private function retrieveVertexSearchMessageElements(array $form, FormStateInterface $form_state) {

    $form['serp_messages'] = [
      '#title' => $this->t('Search Results Page Messages'),
      '#type' => 'details',
      '#open' => TRUE,
      '#description' => $this->t('Tokens may be used in any of the results-related messages.'),
    ];

    // Add token browser to search page configuration form.
    $form['serp_messages']['token_browser'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['vertex_ai_search', 'vertex_ai_search_keywords'],
      '#global_types' => FALSE,
    ];

    // Message to display on SERP when results are returned.
    $form['serp_messages']['results_message'] = [
      '#title' => $this->t('Results message'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['results_message'] ?? '',
      '#description' => $this->t('Message to display when results are returned.'),
    ];

    // Corner-case message if only one result is returned.
    $form['serp_messages']['results_message_singular'] = [
      '#title' => $this->t('Results message (singular)'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['results_message_singular'] ?? '',
      '#description' => $this->t('Message to display when only a single result is returned.  Normal results message will be default.'),
    ];

    // Message to display if search query has no results returned.
    $form['serp_messages']['no_results_message'] = [
      '#title' => $this->t('No results message'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['no_results_message'] ?? '',
      '#description' => $this->t('Message to display when there are no search results returned.'),
    ];

    // Message to display when trying to search without specifying keywords.
    $form['serp_messages']['no_keywords_message'] = [
      '#title' => $this->t('No keywords specified message'),
      '#type' => 'textarea',
      '#default_value' => $this->configuration['no_keywords_message'] ?? '',
      '#description' => $this->t('Message to display when arriving at search page without specifying keywords on which to search.'),
    ];

    return $form;
  }

}
