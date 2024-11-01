<?php

namespace Drupal\xray_audit\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Base class for xray_audit_task_plugin plugins.
 */
abstract class XrayAuditTaskPluginBase extends PluginBase implements XrayAuditTaskPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->getValuesFromDefinition('label');
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->getValuesFromDefinition('group');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) ($this->getValuesFromDefinition('description') ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getSort() {
    return $this->getValuesFromDefinition('sort') ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return $this->getValuesFromDefinition('operations') ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchClass(string $batch_id) {
    return $this->getValuesFromDefinition('batches', $batch_id);
  }

  /**
   * {@inheritdoc}
   */
  public function isLocalTaskCase(): bool {
    return (bool) ($this->getValuesFromDefinition('local_task') ?? FALSE);
  }

  /**
   * Get values from plugin definition.
   *
   * @param string $value_parameter
   *   Value parameter.
   * @param string|null $key_value
   *   Key value.
   *
   * @return mixed|null
   *   The value or null.
   */
  protected function getValuesFromDefinition(string $value_parameter, string $key_value = NULL) {
    if (is_array($this->pluginDefinition) && isset($this->pluginDefinition[$value_parameter])) {
      if ($key_value) {
        return $this->pluginDefinition[$value_parameter][$key_value] ?? NULL;
      }
      return $this->pluginDefinition[$value_parameter] ?? NULL;
    }

    if (($this->pluginDefinition instanceof PluginDefinitionInterface) && isset($this->pluginDefinition->{$value_parameter})) {
      if ($key_value) {
        return $this->pluginDefinition->{$value_parameter}[$key_value] ?? NULL;
      }
      return $this->pluginDefinition->{$value_parameter} ?? NULL;
    }

    return NULL;
  }

}
