<?php

namespace Drupal\xray_audit\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for xray_audit_group_plugin.
 */
abstract class XrayAuditGroupPluginBase extends PluginBase implements XrayAuditGroupPluginInterface, ContainerFactoryPluginInterface {

  /**
   * Plugin manager Xray Audit Groups.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManagerXrayAuditTask;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager_xray_audit_task
   *   Plugin manager xray_audit tasks.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, PluginManagerInterface $plugin_manager_xray_audit_task) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManagerXrayAuditTask = $plugin_manager_xray_audit_task;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin_manager.xray_audit_task')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) ($this->getValuesFromDefinition('description') ?? '');
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
    return (int) ($this->getValuesFromDefinition('sort') ?? 0);
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginTaskDefinitionsThisGroup() {
    $filtered_task_definitions = [];
    $plugin_id = $this->getPluginId();
    $task_definitions = $this->pluginManagerXrayAuditTask->getDefinitions();
    $filtered_task_definitions = array_filter($task_definitions, function ($definition) use ($plugin_id) {
      return isset($definition['group']) && $definition['group'] === $plugin_id;
    });
    if (empty($filtered_task_definitions)) {
      return [];
    }
    usort($filtered_task_definitions, function ($a, $b) {
      return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
    });
    return $filtered_task_definitions;
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
