<?php

namespace Drupal\module_builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use DrupalCodeBuilder\Factory;
use DrupalCodeBuilder\Exception\SanityException;

/**
 * Defines a class to build a listing of module builder components.
 */
class ModuleBuilderComponentListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Sanity check the environment and tell the user to go process data if
    // not already done so.
    // TODO: inject.
    \Drupal::service('module_builder.drupal_code_builder')->loadLibrary();
    try {
      Factory::getEnvironment()->verifyEnvironment('hook_data');
    }
    catch (SanityException $e) {
      ExceptionHandler::handleSanityException($e);
    }

    return parent::render();
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Module name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);

    $component_sections_handler = \Drupal::service('entity_type.manager')->getHandler($this->entityTypeId, 'component_sections');
    $form_operations = $component_sections_handler->getOperations();

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('Edit info');
      $operations['edit']['weight'] = 0;
    }

    $weight = 1;
    foreach ($form_operations as $operation_name => $title) {
      $operations[$operation_name] = array(
        'title' => t($title),
        'url' => $entity->toUrl("$operation_name-form"),
        'weight' => $weight,
      );

      $weight++;
    }

    return $operations;
  }

}
