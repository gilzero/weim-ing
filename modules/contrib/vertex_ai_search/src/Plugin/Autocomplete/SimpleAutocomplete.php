<?php

namespace Drupal\vertex_ai_search\Plugin\Autocomplete;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\vertex_ai_search\Plugin\VertexAutocompletePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides simple autocomplete based on node title and/or description.
 *
 * @VertexAutocompletePlugin(
 *   id = "vertex_autocomplete_simple",
 *   title = @Translation("Simple Autocomplete")
 * )
 */
class SimpleAutocomplete extends VertexAutocompletePluginBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $eTypeManager;

  /**
   * Database Connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration array containing information about search page.
   * @param string $plugin_id
   *   Identifier of custom plugin.
   * @param array $plugin_definition
   *   Provides definition of search plugin.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Entity Type Manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->eTypeManager = $entityTypeManager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSuggestions($keys) {

    // Look at published nodes.
    $query = $this->database->select('node_field_data', 'fd');
    $query->addField('fd', 'title', 'title');
    $query->condition('fd.status', 1, '=');

    // Perform comparisons based on selected autocomplete model.
    if ($this->configuration['autocomplete_model'] == 'title_only') {

      $query->condition('fd.title', '%' . $keys . '%', 'LIKE');

    }
    elseif ($this->configuration['autocomplete_model'] == 'title_body') {

      $query->join('node__body', 'nb', 'nb.entity_id = fd.nid');

      $orCondition = $query->orConditionGroup()
        ->condition('fd.title', '%' . $keys . '%', 'LIKE')
        ->condition('nb.body_value', '%' . $keys . '%', 'LIKE');

      $query->condition($orCondition);

    }

    // Only retrieve up to the max suggestions.
    $query->range(0, $this->configuration['autocomplete_max_suggestions']);

    // If specific content types selected, then filter further.
    if ($content_types = $this->configuration['autocomplete_content_types']) {
      $query->condition('fd.type', $content_types, 'IN');
    }

    $query->orderBy('fd.title');

    $results = $query->execute()->fetchCol();

    return $results;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $types = $this->eTypeManager->getStorage('node_type')->loadMultiple();

    $contentTypes = [];

    foreach ($types as $key => $type) {
      $contentTypes[$key] = $type->get('name');
    }

    $form['autocomplete_model'] = [
      '#title' => $this->t('Autocomplete Model'),
      '#type' => 'select',
      '#required' => TRUE,
      '#options' => [
        NULL => $this->t('-- Select a Model --'),
        'title_only' => $this->t('Node Title Only'),
        'title_body' => $this->t('Node Title and Body'),
      ],
      '#default_value' => $this->configuration['autocomplete_model'] ?? NULL,
    ];

    $form['autocomplete_content_types'] = [
      '#title' => $this->t('Content Types'),
      '#type' => 'select',
      '#options' => $contentTypes,
      '#multiple' => TRUE,
      '#description' => $this->t('If none selected, all will be included.'),
      '#default_value' => $this->configuration['autocomplete_content_types'] ?? NULL,
    ];

    return $form;

  }

}
