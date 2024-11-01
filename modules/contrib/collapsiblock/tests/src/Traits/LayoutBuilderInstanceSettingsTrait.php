<?php

namespace Drupal\Tests\collapsiblock\Traits;

use Drupal\layout_builder\SectionComponent;

/**
 * Simplify working with Collapsiblock and layout builder components.
 */
trait LayoutBuilderInstanceSettingsTrait {

  /**
   * Get whether or not the title is shown for a given Layout Builder Component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The Layout Builder Section Component to get the title visibility of.
   *
   * @return bool
   *   TRUE if the title is visible for the given Layout Builder Component;
   *   FALSE otherwise.
   */
  protected function getLayoutBuilderComponentTitleVisibility(SectionComponent $component) {
    $configuration = $component->get('configuration');
    return boolval($configuration['label_display']);
  }

  /**
   * Get the value of a Collapsiblock setting for a Layout Builder Component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The Layout Builder Section Component to get the Collapsiblock setting
   *   for.
   * @param string $key
   *   The name of the Collapsiblock setting to get the value of.
   *
   * @return mixed
   *   The value of the given Collapsiblock setting for the given Layout Builder
   *   Section Component.
   */
  protected function getLayoutBuilderInstanceSetting(SectionComponent $component, $key = '') {
    $collapsiblockSettings = $component->get('collapsiblock') ?: [];
    return $collapsiblockSettings[$key];
  }

  /**
   * Set whether or not the title is shown for a given Layout Builder Component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The Layout Builder Section Component to change the title visibility of.
   * @param bool $newValue
   *   The new value for the title visibility. TRUE means the title is visible;
   *   FALSE means the title is hidden.
   */
  protected function setLayoutBuilderComponentTitleVisibility(SectionComponent $component, $newValue) {
    $configuration = $component->get('configuration');
    $configuration['label_display'] = boolval($newValue);
    $component->setConfiguration($configuration);
  }

  /**
   * Set the value of a Collapsiblock setting for a Layout Builder component.
   *
   * @param \Drupal\layout_builder\SectionComponent $component
   *   The Layout Builder Section Component to set the Collapsiblock setting
   *   for.
   * @param mixed $newValue
   *   The new value for the given Collapsiblock setting for the given Layout
   *   Builder Section Component.
   * @param string $key
   *   The name of the Collapsiblock setting to change.
   */
  protected function setLayoutBuilderInstanceSetting(SectionComponent $component, $newValue, $key = '') {
    $collapsiblockSettings = $component->get('collapsiblock') ?: [];
    $collapsiblockSettings[$key] = $newValue;
    $component->set('collapsiblock', $collapsiblockSettings);
  }

}
