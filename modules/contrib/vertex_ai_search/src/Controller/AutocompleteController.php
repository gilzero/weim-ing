<?php

namespace Drupal\vertex_ai_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\vertex_ai_search\VertexAutocompletePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Vertex Autocomplete Plugin Manager.
   *
   * @var \Drupal\vertex_ai_search\VertexAutocompletePluginManager
   */
  protected $autoPluginManager;

  /**
   * EntityTypeManager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $eTypeManager;

  /**
   * Constructs a new search controller.
   *
   * @param \Drupal\vertex_ai_search\VertexAutocompletePluginManager $autoPluginManager
   *   Vertex Autocomplete Plugin Manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager.
   */
  public function __construct(
    VertexAutocompletePluginManager $autoPluginManager,
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->autoPluginManager = $autoPluginManager;
    $this->eTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.vertex_autocomplete'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, $search_page_id) {

    $searchPage = $this->eTypeManager->getStorage('search_page')->load($search_page_id);
    $searchPagePlugin = $searchPage->getPlugin();
    $configuration = $searchPagePlugin->getConfiguration();

    $keys = $request->query->get('q');

    if (strlen($keys) < (int) $configuration['autocomplete_trigger_length']) {
      return new JsonResponse([]);
    }

    $autoPlugin = $this->autoPluginManager->createInstance(
      $configuration['autocomplete_source'],
      $configuration
    );

    $suggestions = $autoPlugin->getSuggestions($keys);

    $suggestions = array_slice($suggestions, 0, $configuration['autocomplete_max_suggestions']);

    $matches = [];

    foreach ($suggestions as $suggestion) {
      $matches[] = [
        'label' => $suggestion,
        'value' => $suggestion,
      ];
    };

    return new JsonResponse($matches);

  }

}
