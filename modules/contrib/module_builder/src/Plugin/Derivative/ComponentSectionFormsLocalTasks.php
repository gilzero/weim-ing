<?php

namespace Drupal\module_builder\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines local tasks for component entities.
 */
class ComponentSectionFormsLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates an ComponentSectionFormsLocalTasks object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type->hasHandlerClass('component_sections')) {
        continue;
      }

      // The name form tab.
      $task = $base_plugin_definition;
      $task['title'] = 'Name';
      $task['weight'] = 0;
      $task['route_name'] = "entity.{$entity_type_id}.edit_form";
      $task['base_route'] = "entity.{$entity_type_id}.edit_form";

      $this->derivatives["entity.{$entity_type_id}.edit_form"] = $task;

      $component_sections_handler = $this->entityTypeManager->getHandler($entity_type_id, 'component_sections');
      $section_route_data = $component_sections_handler->getFormTabLocalTasksData();

      // Weight of 0 is used by the name form.
      $weight = 0;

      foreach ($section_route_data as $form_op => $title) {
        $task = $base_plugin_definition;
        $weight++;

        $task['title'] = $title;
        $task['weight'] = $weight;
        $task['route_name'] = "entity.{$entity_type_id}.{$form_op}_form";
        $task['base_route'] = "entity.{$entity_type_id}.edit_form";

        $this->derivatives["entity.{$entity_type_id}.{$form_op}_form"] = $task;
      }
    }

    return $this->derivatives;
  }

}
