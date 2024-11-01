<?php

namespace Drupal\xray_audit\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;

/**
 * Interface for services that retrieve the entities uses.
 *
 * @package Drupal\xray_audit\src\Services
 */
abstract class EntityUseBase {

  /**
   * The entity status is published.
   */
  const ENTITY_STATUS_PUBLISHED = 1;

  /**
   * The entity status is published.
   */
  const ENTITY_STATUS_UNPUBLISHED = 0;

  /**
   * The entity status is IRRELEVANT.
   */
  const ENTITY_STATUS_IRRELEVANT = 2;

  /**
   * The limit of queries to the database.
   */
  const LIMIT_QUERIES = 40000;

  /**
   * The storage of the service.
   *
   * @var mixed[]
   */
  static protected $storage = [];

  /**
   * The xray_audit.entity_architecture service.
   *
   * @var \Drupal\xray_audit\Services\EntityArchitectureInterface
   */
  protected $entityArchitecture;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an EntityUseParagraph object.
   *
   * @param \Drupal\xray_audit\Services\EntityArchitectureInterface $entity_architecture
   *   The xray_audit.entity_architecture service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Service Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService
   *   Service Bundle Info.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Service Config Factory.
   */
  public function __construct(
    EntityArchitectureInterface $entity_architecture,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $bundleInfoService,
    ConfigFactoryInterface $config_factory
  ) {
    $this->entityArchitecture = $entity_architecture;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfoService = $bundleInfoService;
    $this->configFactory = $config_factory;
  }

  /**
   * Check uf the bundle exists.
   *
   * @param string $entity_type
   *   Entity type.
   * @param string $entity_bundle
   *   Entity bundle.
   *
   * @return bool
   *   True or False.
   */
  protected function checkEntityBundleExists(string $entity_type, string $entity_bundle): bool {
    $bundle_info = $this->bundleInfoService->getBundleInfo($entity_type);
    return isset($bundle_info[$entity_bundle]);
  }

  /**
   * Process the data and add absolute link.
   *
   * @param string $parent_entity_type
   *   Parent entity type.
   * @param string $entity_type
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param array $data
   *   Node ides to process.
   * @param int $status_entity
   *   Entity Status.
   *
   * @return array
   *   Data processed.
   */
  protected function processResults(string $parent_entity_type, string $entity_type, string $bundle, array $data, int $status_entity = self::ENTITY_STATUS_IRRELEVANT) {

    $url_segment_entity_mapping = [
      'node' => 'node/',
      'taxonomy_term' => 'taxonomy/term/',
      'block_content' => 'block/',
    ];

    $result = [];
    $absolute = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $entity_url = $absolute . '/' . ($url_segment_entity_mapping[$parent_entity_type] ?? $parent_entity_type) . '/';

    $status = '';
    switch ($status_entity) {
      case self::ENTITY_STATUS_IRRELEVANT:
        $status = '-';
        break;

      case self::ENTITY_STATUS_PUBLISHED:
        $status = 'Published';
        break;

      case self::ENTITY_STATUS_UNPUBLISHED:
        $status = 'Unpublished';
        break;
    }

    $count = 1;
    foreach ($data as $value) {
      $result[$count] = [
        'entity_type_parent' => $parent_entity_type,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'nid' => $value,
        'url' => $entity_url . $value,
        'status' => $status,
      ];
      $count++;
    }

    return $result;
  }

  /**
   * Remove the data from service storage.
   */
  public function __destruct() {
    static::$storage = [];
  }

  /**
   * Get the site name.
   *
   * @return string
   *   Site name.
   */
  protected function getSiteName(): string {
    return $this->configFactory->get('system.site')->get('name');
  }

}
