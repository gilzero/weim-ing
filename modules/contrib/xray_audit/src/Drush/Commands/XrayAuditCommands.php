<?php

namespace Drupal\xray_audit\Drush\Commands;

use Drupal\xray_audit\Services\EntityUseInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A Drush command file.
 */
final class XrayAuditCommands extends DrushCommands {

  /**
   * Service to get the use of paragraphs.
   *
   * @var \Drupal\xray_audit\Services\EntityUseInterface
   */
  protected $entityUseParagraph;

  /**
   * Service to get the use of node.
   *
   * @var \Drupal\xray_audit\Services\EntityUseInterface
   */
  protected $entityUseNode;

  /**
   * Constructs a XrayAuditCommands object.
   *
   * @param \Drupal\xray_audit\Services\EntityUseInterface $entity_use_paragraph
   *   Service to get the use of paragraphs.
   * @param \Drupal\xray_audit\Services\EntityUseInterface $entity_use_node
   *   Service to get the use of node.
   */
  public function __construct(EntityUseInterface $entity_use_paragraph, EntityUseInterface $entity_use_node) {
    parent::__construct();
    $this->entityUseParagraph = $entity_use_paragraph;
    $this->entityUseNode = $entity_use_node;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('xray_audit.entity_use_paragraph'),
      $container->get('xray_audit.entity_use_node')
    );
  }

  /**
   * Count the nodes that are bing used.
   *
   * Criterion of "being using": status = publish.
   *
   * @usage xray_audit:node_count
   *
   * @command xray_audit:node_count
   *
   * @return array
   *   Result in table format.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function nodeUsageCount($arg1 = '', $options = []) {
    $service = $this->entityUseNode;
    $service->initParameters('node', NULL);
    return $service->countEntityUses() ?? [];
  }

  /**
   * Count the paragraph bundles that are used.
   *
   * Criterion for paragraphs: referenced in published and unpublished entities.
   *
   * @usage xray_audit:paragraph_count
   *
   * @command xray_audit:paragraph_count
   *
   * @return array
   *   Result in table format.
   */
  public function countParagraphs() {
    return $this->entityUseParagraph->countEntityUses() ?? [];
  }

  /**
   * List of nodes where entity (node or paragraph) bundles are used.
   *
   * Criterion for nodes: status = published and unpublished.
   * Criterion for paragraphs: referenced in published and unpublished entities.
   *
   * In the case of nodes, the option parents is not used.
   *
   * @param string $arg1
   *   Entity type (node, paragraph).
   * @param array $options
   *   An associative array of options.
   *
   * @option bundles
   *   List of bundles separated by a comma.
   * @option parents
   *   The parent entity types separated by a comma.
   *
   * @usage xray_audit:usage_place paragraph --bundles=paragraph_bundle1,paragraph_bundle2
   *
   * @command xray_audit:usage_place
   *
   * @return array
   *   Result in table format.
   */
  public function usagePlace($arg1 = '', $options = ['bundles' => '', 'parents' => '']) {
    $service = $this->getEntityUseService($arg1, $options);
    if (!$service instanceof EntityUseInterface) {
      return [];
    }

    $rows = [];
    foreach ($options['parents'] as $parent) {
      $parent = trim($parent);
      foreach ($options['bundles'] as $bundle) {
        $bundle = is_string($bundle) ? trim($bundle) : NULL;
        $service->initParameters($parent, $bundle);
        $result_one_bundle = $service->getEntityUsePlaces();
        if (is_array($result_one_bundle)) {
          $rows = array_merge($rows, $result_one_bundle);
        }
      }
    }
    return $rows;
  }

  /**
   * Return the service if the arguments are correct.
   *
   * @param string $arg1
   *   Arg1 from command.
   * @param array $options
   *   Options from command.
   *
   * @return \Drupal\xray_audit\Services\EntityUseInterface|null
   *   Return the service if the arguments and options are correct.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function getEntityUseService(string $arg1, array &$options): ?EntityUseInterface {
    $allowed_entities = ['paragraph', 'node'];

    if (empty($arg1) || !in_array($arg1, $allowed_entities)) {
      if ($logger = $this->logger()) {
        $logger->error(dt('You should set an allowed entity (node or paragraph) as argument.'));
      }
      return NULL;
    }
    if (empty($options['bundles'])) {
      $options['bundles'] = NULL;
    }

    if (empty($options['parents'])) {
      $options['parents'] = 'node';
    }

    $options['bundles'] = explode(',', $options['bundles']);
    $options['parents'] = explode(',', $options['parents']);

    $service = NULL;
    switch ($arg1) {
      case 'paragraph':
        $service = $this->entityUseParagraph;
        break;

      case 'node':
        $service = $this->entityUseNode;
        break;
    }

    if ($service === NULL) {
      if ($logger = $this->logger()) {
        $logger->error(dt('The proper service could not find.'));
      }
      return NULL;
    }

    return $service;
  }

}
