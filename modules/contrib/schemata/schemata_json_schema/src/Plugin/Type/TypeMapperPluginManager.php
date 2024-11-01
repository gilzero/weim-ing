<?php

namespace Drupal\schemata_json_schema\Plugin\Type;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\schemata_json_schema\Annotation\TypeMapper;
use Drupal\schemata_json_schema\Plugin\schemata_json_schema\type_mapper\TypeMapperInterface;

/**
 * Manages TypeMapper plugins.
 *
 * TypeMappers are used to adapt Drupal TypedData types to JSON Schema specs.
 *
 * @see \Drupal\schemata_json_schema\Annotation\TypeMapper
 * @see \Drupal\schemata_json_schema\Plugin\schemata_json_schema\type_mapper\TypeMapperBase
 * @see \Drupal\schemata_json_schema\Plugin\schemata_json_schema\type_mapper\TypeMapperInterface
 * @see plugin_api
 */
class TypeMapperPluginManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * The TypeMapper to use if there's a miss.
   */
  const FALLBACK_TYPE_MAPPER = 'fallback';

  /**
   * Constructs a new \Drupal\rest\Plugin\Type\ResourcePluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend to cache plugin definitions.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/schemata_json_schema/type_mapper', $namespaces, $module_handler, TypeMapperInterface::class, TypeMapper::class);
    $this->alterInfo('json_schema_type_mapper');
    $this->setCacheBackend($cache_backend, 'json_schema_type_mapper_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return static::FALLBACK_TYPE_MAPPER;
  }

}
