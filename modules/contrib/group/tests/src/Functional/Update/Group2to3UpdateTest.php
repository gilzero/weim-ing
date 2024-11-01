<?php

namespace Drupal\group\Tests\Functional\Update;

use Drupal\Core\Config\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Tests the Group v2 to v3 update path.
 *
 * @group group
 *
 * @todo Remove along with the dev requirement of variation cache in Group v4.
 */
class Group2to3UpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (version_compare(\Drupal::VERSION, '11', '>=')) {
      $this->markTestSkipped('The fixture for the upgrade path was made for Drupal 10.3, skipping test on later versions.');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/group-v-2-3-x.php.gz',
    ];
  }

  /**
   * Tests fields referring to relationships.
   */
  public function testEntityReferenceFields(): void {
    $last_installed_schema_repository = $this->getLastInstalledSchemaRepository();

    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('node');
    $this->assertSame('entity_reference', $field_storage_definitions['field_member_highlight']->getType());
    $this->assertSame('group_content', $field_storage_definitions['field_member_highlight']->getSetting('target_type'));

    $this->runUpdates();

    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('node');
    $this->assertSame('entity_reference', $field_storage_definitions['field_member_highlight']->getType());
    $this->assertSame('group_relationship', $field_storage_definitions['field_member_highlight']->getSetting('target_type'));
  }

  /**
   * Tests field storages on relationships.
   */
  public function testFieldStorages(): void {
    $last_installed_schema_repository = $this->getLastInstalledSchemaRepository();

    // Make sure no storages exist already.
    $this->assertEmpty($last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('group_relationship'));

    // Check the control values.
    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('group_content');
    assert($field_storage_definitions['field_short_field'] instanceof FieldStorageConfigInterface);
    $this->assertSame('group_content.field_short_field', $field_storage_definitions['field_short_field']->get('id'));
    $this->assertSame('group_content', $field_storage_definitions['field_short_field']->get('entity_type'));

    $this->runUpdates();

    // Make sure no storages linger around.
    $this->assertEmpty($last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('group_content'));

    // Check the new field storage properties.
    $field_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions('group_relationship');
    assert($field_storage_definitions['field_short_field'] instanceof FieldStorageConfigInterface);
    $this->assertSame('group_relationship.field_short_field', $field_storage_definitions['field_short_field']->get('id'));
    $this->assertSame('group_relationship', $field_storage_definitions['field_short_field']->get('entity_type'));
  }

  /**
   * Tests field storage tables for relationships.
   */
  public function testFieldStorageTableMapping(): void {
    $database = \Drupal::database();
    $database_schema = $database->schema();

    // Results gotten from DefaultTableMapping::getDedicatedDataTableName().
    $fields = [
      'field_really_long_field_title_00' => [
        'table_old' => 'group_content__field_really_long_field_title_00',
        'table_new' => 'group_relationship__5eb81ace03',
      ],
      'field_short_field' => [
        'table_old' => 'group_content__field_short_field',
        'table_new' => 'group_relationship__field_short_field',
      ],
      'group_roles' => [
        'table_old' => 'group_content__group_roles',
        'table_new' => 'group_relationship__group_roles',
      ],
    ];

    $field_data = [];
    foreach ($fields as $field_name => $field_info) {
      $this->assertTrue($database_schema->tableExists($field_info['table_old']));
      $this->assertFalse($database_schema->tableExists($field_info['table_new']));
      $field_data[$field_name] = $database->select($field_info['table_old'], 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC);
    }

    $this->runUpdates();

    foreach ($fields as $field_name => $field_info) {
      $this->assertFalse($database_schema->tableExists($field_info['table_old']));
      $this->assertTrue($database_schema->tableExists($field_info['table_new']));
      $this->assertSame($field_data[$field_name], $database->select($field_info['table_new'], 't')->fields('t')->execute()->fetchAll(\PDO::FETCH_ASSOC));
    }
  }

  /**
   * Tests that relationship types are converted.
   */
  public function testGroupRelationshipTypes() {
    $this->assertEquals([
      'group.content_type.class-group_membership',
      'group.content_type.class-group_node-page',
    ], \Drupal::configFactory()->listAll('group.content_type.'));
    $this->assertEquals([], \Drupal::configFactory()->listAll('group.relationship_type.'));

    $this->runUpdates();

    $this->assertEquals([], \Drupal::configFactory()->listAll('group.content_type.'));
    $this->assertEquals([
      'group.relationship_type.class-group_membership',
      'group.relationship_type.class-group_node-page',
    ], \Drupal::configFactory()->listAll('group.relationship_type.'));
  }

  /**
   * Tests that the config key store no longer refers to old types.
   */
  public function testConfigKeyStore() {
    $this->assertEquals([
      'uuid:000b9028-9efe-4a4f-9552-4ed2343481d5' => ['group.content_type.class-group_node-page'],
      'uuid:1891ac47-eae0-4075-b85d-38c7ca8b5122' => ['group.content_type.class-group_membership'],
    ], \Drupal::keyValue(QueryFactory::CONFIG_LOOKUP_PREFIX . 'group_content_type')->getAll());
    $this->assertEquals([], \Drupal::keyValue(QueryFactory::CONFIG_LOOKUP_PREFIX . 'group_relationship_type')->getAll());

    $this->runUpdates();

    $this->assertEquals([], \Drupal::keyValue(QueryFactory::CONFIG_LOOKUP_PREFIX . 'group_content_type')->getAll());
    $this->assertEquals([
      'uuid:000b9028-9efe-4a4f-9552-4ed2343481d5' => ['group.relationship_type.class-group_node-page'],
      'uuid:1891ac47-eae0-4075-b85d-38c7ca8b5122' => ['group.relationship_type.class-group_membership'],
    ], \Drupal::keyValue(QueryFactory::CONFIG_LOOKUP_PREFIX . 'group_relationship_type')->getAll());
  }

  /**
   * Tests field instances on relationships.
   */
  public function testFieldInstance(): void {
    // We can't test this with the field_config storage as it would try to load
    // the group_content entity type, which no longer exists in code when you
    // are about to run the update.
    $config_factory = \Drupal::configFactory();

    // Make sure no field instances exist already.
    $this->assertEmpty($config_factory->listAll('field.field.group_relationship.'));

    // Check the control values.
    $old_field = $config_factory->get('field.field.group_content.class-group_membership.field_short_field');
    $this->assertSame('group_content.class-group_membership.field_short_field', $old_field->get('id'));
    $this->assertSame('group_content', $old_field->get('entity_type'));
    foreach ($old_field->get('dependencies')['config'] as $dependency_name) {
      $this->assertFalse(strpos($dependency_name, 'group.relationship_type'));
      $this->assertFalse(strpos($dependency_name, 'group_relationship'));
    }

    $this->runUpdates();

    // Make sure no field instances linger around.
    $this->assertEmpty($config_factory->listAll('field.field.group_content.'));

    // Check the new field instance properties.
    $new_field = $config_factory->get('field.field.group_relationship.class-group_membership.field_short_field');
    $this->assertSame('group_relationship.class-group_membership.field_short_field', $new_field->get('id'));
    $this->assertSame('group_relationship', $new_field->get('entity_type'));
    foreach ($new_field->get('dependencies')['config'] as $dependency_name) {
      $this->assertFalse(strpos($dependency_name, 'group.content_type'));
      $this->assertFalse(strpos($dependency_name, 'group_content'));
    }
  }

  /**
   * Tests the bundle field map.
   */
  public function testBundleFieldMap(): void {
    // We check both the field manager's map and the key value collection that
    // is used to store the field_config part of said map.
    $bundle_field_map_store = \Drupal::keyValue('entity.definitions.bundle_field_map');
    $entity_field_manager = $this->getEntityFieldManager();

    $base_fields = [
      'id',
      'uuid',
      'langcode',
      'type',
      'uid',
      'gid',
      'entity_id',
      'label',
      'created',
      'changed',
      'plugin_id',
      'group_type',
      'path',
      'default_langcode',
    ];

    $expected_map = [
      'group_roles' => [
        'type' => 'entity_reference',
        'bundles' => ['class-group_membership' => 'class-group_membership'],
      ],
      'field_short_field' => [
        'type' => 'string',
        'bundles' => ['class-group_membership' => 'class-group_membership'],
      ],
      'field_really_long_field_title_00' => [
        'type' => 'string',
        'bundles' => ['class-group_membership' => 'class-group_membership'],
      ],
    ];

    $this->assertEmpty($bundle_field_map_store->get('group_relationship'));
    $this->assertSame($expected_map, $bundle_field_map_store->get('group_content'));
    $this->assertSame($expected_map, $entity_field_manager->getFieldMap()['group_content'], 'Base fields are already gone from group_content map');
    $this->assertSame($base_fields, array_keys($entity_field_manager->getFieldMap()['group_relationship']), 'Base fields are already present in group_relationship map');

    $this->runUpdates();
    $entity_field_manager->clearCachedFieldDefinitions();

    $this->assertEmpty($bundle_field_map_store->get('group_content'));
    $this->assertSame($expected_map, $bundle_field_map_store->get('group_relationship'));
    $this->assertArrayNotHasKey('group_content', $entity_field_manager->getFieldMap(), 'Nothing left in group_content map');
    $this->assertSame(array_merge($base_fields, array_keys($expected_map)), array_keys($entity_field_manager->getFieldMap()['group_relationship']));
  }

  /**
   * Tests view and form modes for relationships.
   */
  public function testViewAndFormModes(): void {
    $config_factory = \Drupal::configFactory();

    foreach (['entity_form_display', 'entity_view_display'] as $mode_key) {
      // Make sure no mode exists already.
      $this->assertEmpty($config_factory->listAll("core.$mode_key.group_relationship."));

      // Check the control values.
      $old_mode = $config_factory->get("core.$mode_key.group_content.class-group_membership.default");
      $this->assertSame('group_content.class-group_membership.default', $old_mode->get('id'));
      $this->assertSame('group_content', $old_mode->get('targetEntityType'));
      $this->assertSame('class-group_membership', $old_mode->get('bundle'));
      foreach ($old_mode->get('dependencies')['config'] as $dependency_name) {
        $this->assertFalse(strpos($dependency_name, 'group.relationship_type'));
        $this->assertFalse(strpos($dependency_name, 'group_relationship'));
      }
    }

    $this->runUpdates();

    foreach (['entity_form_display', 'entity_view_display'] as $mode_key) {
      // Make sure no mode lingers around.
      $this->assertEmpty($config_factory->listAll("core.$mode_key.group_content."));

      // Check the new mode properties.
      $new_mode = $config_factory->get("core.$mode_key.group_relationship.class-group_membership.default");
      $this->assertSame('group_relationship.class-group_membership.default', $new_mode->get('id'));
      $this->assertSame('group_relationship', $new_mode->get('targetEntityType'));
      $this->assertSame('class-group_membership', $new_mode->get('bundle'));
      foreach ($new_mode->get('dependencies')['config'] as $dependency_name) {
        $this->assertFalse(strpos($dependency_name, 'group.content_type'));
        $this->assertFalse(strpos($dependency_name, 'group_content'));
      }
    }
  }

  /**
   * Gets the entity field manager.
   *
   * @return \Drupal\Core\Entity\EntityFieldManagerInterface
   *   The entity field manager.
   */
  protected function getEntityFieldManager(): EntityFieldManagerInterface {
    return \Drupal::service('entity_field.manager');
  }

  /**
   * Gets the last installed schema repository.
   *
   * @return \Drupal\Core\Entity\EntityLastInstalledSchemaRepositoryInterface
   *   The last installed schema repository.
   */
  protected function getLastInstalledSchemaRepository(): EntityLastInstalledSchemaRepositoryInterface {
    return \Drupal::service('entity.last_installed_schema.repository');
  }

}
