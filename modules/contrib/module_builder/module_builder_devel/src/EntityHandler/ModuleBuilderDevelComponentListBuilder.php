<?php

namespace Drupal\module_builder_devel\EntityHandler;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\module_builder\ModuleBuilderComponentListBuilder;

/**
 * Overrides the default list builder to link to issues.
 *
 * Any module entity whose ID is of the form 'test_1234' is assumed to be
 * related to an issue, where the issue ID is the numeric suffix. The length
 * of the issue number is used to guess whether the issue is for Code Builder
 * (short number) or Module Builder (longer number).
 */
class ModuleBuilderDevelComponentListBuilder extends ModuleBuilderComponentListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $module_name = $entity->id();
    if (preg_match('@test_\d+@', $module_name)) {
      $issue_number = preg_replace('@^[^\d]+@', '', $module_name);

      $base_url = match (TRUE) {
        strlen($issue_number) <= 4 => 'https://github.com/drupal-code-builder/drupal-code-builder/issues/',
        strlen($issue_number) > 6 => 'https://www.drupal.org/project/module_builder/issues/',
      };

      $row['label'] = '<a href="' . $base_url . $issue_number . '">' . $entity->label() . '</a>';
      $row['label'] = Link::fromTextAndUrl($entity->label(), Url::fromUri($base_url . $issue_number));
    }

    return $row;
  }

}
