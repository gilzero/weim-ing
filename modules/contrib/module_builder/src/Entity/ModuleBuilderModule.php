<?php

namespace Drupal\module_builder\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the module_builder entity.
 *
 * @ConfigEntityType(
 *   id = "module_builder_module",
 *   label = @Translation("Module"),
 *   handlers = {
 *     "list_builder" = "Drupal\module_builder\ModuleBuilderComponentListBuilder",
 *     "component_sections" = "Drupal\module_builder\EntityHandler\ComponentSectionFormHandler",
 *     "form" = {
 *       "default" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "add" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "edit" = "Drupal\module_builder\Form\ModuleNameForm",
 *       "hooks" = "Drupal\module_builder\Form\ModuleHooksForm",
 *       "misc" = "Drupal\module_builder\Form\ModuleMiscForm",
 *       "adopt" = "Drupal\module_builder\Form\ComponentAdoptForm",
 *       "generate" = "Drupal\module_builder\Form\ComponentGenerateForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\module_builder\Routing\ComponentRouteProvider",
 *     },
 *   },
 *   config_prefix = "component",
 *   admin_permission = "create modules",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "location",
 *     "data",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/development/module_builder/manage/{module_builder_module}",
 *     "collection" = "/admin/config/development/module_builder",
 *     "add-form" = "/admin/config/development/module_builder/add",
 *     "edit-form" = "/admin/config/development/module_builder/manage/{module_builder_module}",
 *     "adopt-form" = "/admin/config/development/module_builder/manage/{module_builder_module}/adopt",
 *     "generate-form" = "/admin/config/development/module_builder/manage/{module_builder_module}/generate",
 *     "adopt-form" = "/admin/config/development/module_builder/manage/{module_builder_module}/adopt",
 *     "delete-form" = "/admin/config/development/module_builder/manage/{module_builder_module}/delete",
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
 *           "lifecycle",
 *         },
 *       },
 *       "hooks" = {
 *         "title" = "Edit %label hooks",
 *         "op_title" = "Edit hooks",
 *         "tab_title" = "Hooks",
 *         "properties" = {
 *           "hook_implementation_type",
 *           "hooks",
 *         },
 *       },
 *       "plugins" = {
 *         "title" = "Edit %label plugins",
 *         "op_title" = "Edit plugins",
 *         "tab_title" = "Plugins",
 *         "properties" = {
 *           "plugins",
 *           "plugin_types",
 *         },
 *       },
 *       "entities" = {
 *         "title" = "Edit %label entity types",
 *         "op_title" = "Edit entity types",
 *         "tab_title" = "Entity types",
 *         "properties" = {
 *           "content_entity_types",
 *           "config_entity_types",
 *         },
 *       },
 *       "routes_forms" = {
 *         "title" = "Edit %label routes and forms",
 *         "op_title" = "Edit routes and forms",
 *         "tab_title" = "Routes & forms",
 *         "properties" = {
 *           "router_items",
 *           "dynamic_routes",
 *           "settings_form",
 *           "forms",
 *         },
 *       },
 *       "tests" = {
 *         "title" = "Edit %label tests",
 *         "op_title" = "Edit tests",
 *         "tab_title" = "Tests",
 *         "properties" = {
 *           "phpunit_tests",
 *         },
 *       },
 *     },
 *   },
 * )
 */
class ModuleBuilderModule extends ConfigEntityBase implements ComponentInterface {

  /**
   * The module_builder ID.
   *
   * @var string
   */
  public $id;

  /**
   * The module_builder label.
   *
   * @var string
   */
  public $label;

  /**
   * The module write location.
   *
   * For defaults if left empty, see \Drupal\module_builder\ModuleFileWriter.
   *
   * @var string
   */
  public $location = '';

  /**
   * {@inheritdoc}
   */
  public function getComponentType(): string {
    // TODO: move this to the entity annotation when we generalize to other
    // components.
    return 'module';
  }

}
