<?php

namespace Drupal\module_builder_test_component_type\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\module_builder\Entity\ComponentInterface;

/**
 * Defines a test component entity.
 *
 * This has a custom component section form which uses a custom Generate Task
 * handler, in order to show all the different form elements for MTD data.
 *
 * @see \Drupal\module_builder_test_component_type\Form\TestComponentMiscForm
 * @see \Drupal\module_builder_test_component_type\TestGenerateTask
 *
 * @ConfigEntityType(
 *   id = "test_component",
 *   label = @Translation("Test component"),
 *   handlers = {
 *     "list_builder" = "Drupal\module_builder\ModuleBuilderComponentListBuilder",
 *     "component_sections" = "Drupal\module_builder\EntityHandler\ComponentSectionFormHandler",
 *     "form" = {
 *       "default" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "add" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "edit" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "misc" = "Drupal\module_builder_test_component_type\Form\TestComponentMiscForm",
 *       "generate" = "Drupal\module_builder\Form\ComponentGenerateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\module_builder\Routing\ComponentRouteProvider",
 *     },
 *   },
 *   config_prefix = "test_component",
 *   admin_permission = "create modules",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "data",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/test_component/manage/{test_component}",
 *     "collection" = "/admin/config/development/test_component",
 *     "add-form" = "/admin/config/development/test_component/add",
 *     "edit-form" = "/admin/config/development/test_component/manage/{test_component}",
 *     "generate-form" = "/admin/config/development/test_component/manage/{test_component}/generate",
 *     "delete-form" = "/admin/config/development/test_component/manage/{test_component}/delete",
 *   },
 *   code_builder = {
 *     "section_forms" = {
 *       "name" = {
 *         "title" = "Edit %label basic properties",
 *         "op_title" = "Edit basic properties",
 *         "tab_title" = "Name",
 *         "properties" = {
 *           "short_description",
 *           "module_package",
 *           "module_dependencies",
 *         },
 *       },
 *     },
 *   },
 * )
 */
class TestComponent extends ConfigEntityBase implements ComponentInterface {

  /**
   * The test_component ID.
   *
   * @var string
   */
  public $id;

  /**
   * The test_component label.
   *
   * @var string
   */
  public $label;

  /**
   * {@inheritdoc}
   */
  public function getComponentType(): string {
    // No need to do anything special here, as this value is only used to pass
    // to the Generate task, and we use a custom one which doesn't care.
    return 'module';
  }

}
