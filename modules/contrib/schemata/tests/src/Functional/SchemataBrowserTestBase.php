<?php

namespace Drupal\Tests\schemata\Functional;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Drupal\schemata\SchemaFactory;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\schemata\SchemaUrl;
use Drupal\Tests\BrowserTestBase;

/**
 * Sets up functional testing for Schemata.
 */
abstract class SchemataBrowserTestBase extends BrowserTestBase {

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Schema Factory.
   *
   * @var \Drupal\schemata\SchemaFactory
   */
  protected SchemaFactory $schemaFactory;

  /**
   * Dereferenced Schema Static Cache.
   *
   * @var array
   *
   * @see ::requestSchemaByUrl()
   */
  protected array $schemaCache = [];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'field',
    'filter',
    'text',
    'node',
    'taxonomy',
    'serialization',
    'hal',
    'schemata',
    'schemata_json_schema',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->container->get('entity_type.manager');
    $this->schemaFactory = $this->container->get('schemata.schema_factory');

    if (!NodeType::load('camelids')) {
      // Create a "Camelids" node type.
      NodeType::create([
        'name' => 'Camelids',
        'type' => 'camelids',
      ])->save();
    }

    // Create a "Camelids" vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Camelids',
      'vid' => 'camelids',
    ]);
    $vocabulary->save();

    $entity_types = ['node', 'taxonomy_term'];
    foreach ($entity_types as $entity_type) {
      // Add access-protected field.
      FieldStorageConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_' . $entity_type,
        'type' => 'text',
      ])
        ->setCardinality(1)
        ->save();
      FieldConfig::create([
        'entity_type' => $entity_type,
        'field_name' => 'field_test_' . $entity_type,
        'bundle' => 'camelids',
      ])
        ->setLabel('Test field')
        ->setTranslatable(FALSE)
        ->save();
    }
    $this->container->get('router.builder')->rebuild();
    $this->drupalLogin($this->drupalCreateUser(['access schemata data models']));
  }

  /**
   * Requests a Schema via HTTP, ready for session assertions.
   *
   * @param string $format
   *   The described format.
   * @param string $entity_type_id
   *   Then entity type.
   * @param string|null $bundle_id
   *   The bundle name or NULL.
   *
   * @return string
   *   Serialized schema contents.
   */
  protected function getRawSchemaByOptions($format, $entity_type_id, $bundle_id = NULL) {
    $url = SchemaUrl::fromOptions('schema_json', $format, $entity_type_id, $bundle_id)->toString();
    return $this->drupalGet($url);
  }

}
