<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\ContentDisplay;

use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\EntityDisplayArchitectureInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "entity_displays",
 *   label = @Translation("Entities Displays"),
 *   description = @Translation("Display modes of entities."),
 *   group = "content_display",
 *   sort = 1,
 *   operations = {
 *      "node_display" = {
 *          "label" = "Node display configurations",
 *          "description" = "",
 *          "dependencies" = {"node"}
 *       },
 *     "paragraphs_display" = {
 *          "label" = "Paragraphs display configurations",
 *          "description" = "",
 *          "dependencies" = {"paragraphs"}
 *      },
 *     "media_display" = {
 *          "label" = "Media display configurations",
 *          "description" = "",
 *          "dependencies" = {"media"}
 *     },
 *     "taxonomy_display" = {
 *          "label" = "Taxonomy display configurations",
 *          "description" = "",
 *          "dependencies" = {"taxonomy"}
 *      }
 *   },
 *   dependencies = {"field"}
 * )
 */
class XrayAuditEntityDisplaysPlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "entity_display_architecture".
   *
   * @var \Drupal\xray_audit\Services\EntityDisplayArchitectureInterface
   */
  protected $entityDisplayArchitecture;

  /**
   * Service "xray_audit.plugin_repository".
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\xray_audit\Services\EntityDisplayArchitectureInterface $entityDisplayArchitecture
   *   Service "entity_display_architecture".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, EntityDisplayArchitectureInterface $entityDisplayArchitecture, PluginRepositoryInterface $pluginRepository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityDisplayArchitecture = $entityDisplayArchitecture;
    $this->pluginRepository = $pluginRepository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('xray_audit.entity_display_architecture'),
      $container->get('xray_audit.plugin_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'node_display':
        return $this->entityDisplayArchitecture->getData('node_type', 'node');

      case 'paragraphs_display':
        return $this->entityDisplayArchitecture->getData('paragraphs_type', 'paragraph');

      case 'media_display':
        return $this->entityDisplayArchitecture->getData('media_type', 'media');

      case 'taxonomy_display':
        return $this->entityDisplayArchitecture->getData('taxonomy_vocabulary', 'taxonomy_term');

    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $build = [];

    $build['summary_table_introduction'] = [
      '#markup' => $this->t("Data architecture overview"),
      '#prefix' => "<p></p><h2>",
      '#suffix' => "</h2><p></p>",
      '#weight' => '0',
    ];

    $build['summary_bundle_display'] =
      [
        '#theme' => 'table',
        '#header' => ['Bundle', 'Displays', 'Displays Count'],
        '#rows' => $data['summary_bundle_display'],
        '#weight' => 20,
      ];

    $build['summary_display_bundles'] =
      [
        '#theme' => 'table',
        '#header' => ['Display', 'Bundles', 'Bundles Count'],
        '#rows' => $data['summary_display_bundles'],
        '#weight' => 20,
      ];

    $build['main_table_introduction'] = [
      '#markup' => $this->t("Data architecture"),
      '#prefix' => "<p></p><h2>",
      '#suffix' => "</h2><p></p>",
      '#weight' => 23,
    ];

    if (isset($data['main_table']['computed'])) {
      $build['computed'] = [
        '#markup' => $this->t("<b>Computed fields:</b> %computed", ['%computed' => $data['main_table']['computed']]),
        '#prefix' => "<p></p><p>",
        '#suffix' => "</p>",
        '#weight' => 25,
      ];
    }

    $headers = $data['main_table']['headers'];
    $rows = [];

    // Sort columns in rows following the header structure.
    foreach ($data['main_table']['rows'] as $delta_row => $row) {
      $delta_headers = array_keys($headers);
      foreach ($delta_headers as $delta_header) {
        $rows[$delta_row]['data'][$delta_header] = $row[$delta_header] ?? '';
      }
      $rows[$delta_row]['class'] = ['xray-audit__row'];
    }

    $build['main_table'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#weight' => 30,
      '#attributes' => [
        'class' => [
          'xray-audit__table',
          'xray-audit__sticky-header',
          'alignment-vertical-center',
        ],
      ],
      '#attached' => [
        'library' => [
          'xray_audit/xray_audit',
        ],
      ],
    ];

    return $build;

  }

}
