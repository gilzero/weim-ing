<?php

namespace Drupal\xray_audit_insight\Plugin;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for xray_audit_insight_plugin plugins.
 */
abstract class XrayAuditInsightPluginBase extends PluginBase implements XrayAuditInsightPluginInterface, ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The module settings.
   */
  const MODULE_SETTINGS = 'xray_audit_insight.settings';

  /**
   * Plugin manager xray.
   *
   * @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager
   */
  protected $pluginManagerTaskXray;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Render.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Site settings.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $siteSettings;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instanced_object = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instanced_object->pluginManagerTaskXray = $container->get('plugin_manager.xray_audit_task');
    $instanced_object->configFactory = $container->get('config.factory');
    $instanced_object->renderer = $container->get('renderer');
    $instanced_object->moduleHandler = $container->get('module_handler');
    $instanced_object->siteSettings = $container->get('settings');
    return $instanced_object;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->getValuesFromDefinition('label');
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
  public function isActive(): bool {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $this->configFactory;

    $config_object = $config_factory->get(static::MODULE_SETTINGS);
    if (!$config_object instanceof ImmutableConfig) {
      return TRUE;
    }

    $excluded_insights = $config_object->get('excluded_insights') ?? [];
    return !in_array($this->getPluginId(), $excluded_insights);
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
  protected function getValuesFromDefinition(string $value_parameter, string $key_value = '') {
    if (is_array($this->pluginDefinition) && isset($this->pluginDefinition[$value_parameter])) {
      if ($key_value !== '') {
        return $this->pluginDefinition[$value_parameter][$key_value] ?? NULL;
      }
      return $this->pluginDefinition[$value_parameter] ?? NULL;
    }

    if (($this->pluginDefinition instanceof PluginDefinitionInterface) && isset($this->pluginDefinition->{$value_parameter})) {
      if ($key_value !== '') {
        return $this->pluginDefinition->{$value_parameter}[$key_value] ?? NULL;
      }
      return $this->pluginDefinition->{$value_parameter} ?? NULL;
    }

    return NULL;
  }

  /**
   * Build the array to pass to Drupal report.
   *
   * @param mixed $title
   *   Title.
   * @param mixed $value
   *   Value.
   * @param mixed $description
   *   Description.
   * @param mixed $severity
   *   Severity.
   *
   * @return array
   *   The array to send to Drupal report.
   */
  protected function buildInsightForDrupalReport($title, $value, $description, $severity = REQUIREMENT_WARNING): array {
    $build = [];
    if ($title) {
      $build['title'] = 'Xray Audit: ' . $title;
    }
    if ($value) {
      $build['value'] = $value;
    }
    if ($description) {
      $build['description'] = $description;
    }
    if (is_int($severity)) {
      $build['severity'] = $severity;
    }

    return $build;
  }

  /**
   * Get the xray plugin instance.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $operation
   *   Operation.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface|null
   *   Return the plugin instance.
   */
  protected function getInstancedPlugin(string $plugin_id, string $operation): ?XrayAuditTaskPluginInterface {
    if ($this->getTaskPluginDefinition($plugin_id, $operation) === NULL) {
      return NULL;
    }

    $plugin_manger = $this->pluginManagerTaskXray;
    /** @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface $instance */
    $instance = $plugin_manger->createInstance($plugin_id);
    return $instance;
  }

  /**
   * Get the task plugin definition.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $operation
   *   Operation.
   *
   * @return array|null
   *   Return the plugin definition.
   */
  protected function getTaskPluginDefinition(string $plugin_id, string $operation): ?array {
    $plugin_manger = $this->pluginManagerTaskXray;
    $plugin_definitions = $plugin_manger->getDefinitions();
    if (!isset($plugin_definitions[$plugin_id]) || !isset($plugin_definitions[$plugin_id]['operations'][$operation])) {
      return NULL;
    }
    return $plugin_definitions[$plugin_id];
  }

  /**
   * Get the path report.
   *
   * @param string $plugin_id
   *   Plugin id.
   * @param string $operation
   *   Operation.
   *
   * @return string|null
   *   Return the path report.
   */
  protected function getPathReport(string $plugin_id, string $operation): ?string {
    $definition = $this->getTaskPluginDefinition($plugin_id, $operation);
    if ($definition === NULL) {
      return NULL;
    }
    return $definition['operations'][$operation]['url'] ?? NULL;
  }

}
