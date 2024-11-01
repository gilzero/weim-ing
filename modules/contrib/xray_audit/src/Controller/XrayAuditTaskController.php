<?php

namespace Drupal\xray_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for XrayAudit Task routes.
 */
final class XrayAuditTaskController extends ControllerBase {

  /**
   * Plugin repository.
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

  /**
   * Constructs of Controller.
   *
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $plugin_repository
   *   Plugin manager for Groups.
   */
  public function __construct(PluginRepositoryInterface $plugin_repository) {
    $this->pluginRepository = $plugin_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('xray_audit.plugin_repository')
    );
  }

  /**
   * Return a render array with the results of the task.
   *
   * @param string $task_operation
   *   Task plugin operation.
   *
   * @return array
   *   Render array.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function build(string $task_operation): array {
    $task_operation = str_replace('-', '_', $task_operation);
    $task_plugin = $this->getTaskPluginFromOperation($task_operation);
    $data = $task_plugin->getDataOperationResult($task_operation);
    return $task_plugin->buildDataRenderArray($data, $task_operation);
  }

  /**
   * Init a batch process.
   *
   * @param string $task_plugin
   *   Task plugin.
   * @param string $batch_process
   *   Batch process.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response if the batch is progressive. No return value otherwise.
   */
  public function buildBatchProcess(string $task_plugin, string $batch_process): RedirectResponse {
    $task_plugin = $this->getTaskPluginFromTaskParameter($task_plugin);
    $batch_method = $task_plugin->getBatchClass($batch_process);
    if ($batch_method === NULL || !method_exists($task_plugin, $batch_method)) {
      throw new NotFoundHttpException();
    }
    return $task_plugin->$batch_method();
  }

  /**
   * Return a render array with the results of the task.
   *
   * @param string $task_operation
   *   The task operation.
   *
   * @return string
   *   Title.
   *
   * @SuppressWarnings(PHPMD.UnusedFormalParameter)
   */
  public function getTitle(string $task_operation): string {
    $task_operation = str_replace('-', '_', $task_operation);
    $task_plugin = $this->getTaskPluginFromOperationParameter($task_operation);
    if ($task_plugin === NULL) {
      return '';
    }

    if ($task_plugin->isLocalTaskCase()) {
      return $task_plugin->label();
    }

    return $task_plugin->getOperations()[$task_operation]['label'] ?? '';
  }

  /**
   * Gets the $task_plugin parameter.
   *
   * @param string $task_plugin
   *   Task plugin.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface
   *   Plugin.
   */
  public function getTaskPluginFromTaskParameter($task_plugin): XrayAuditTaskPluginInterface {
    return $this->pluginRepository->getInstancePluginTask($task_plugin);
  }

  /**
   * Gets the task_plugin from  operation parameter.
   *
   * @param string $task_operation
   *   Task plugin.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface
   *   Plugin.
   */
  public function getTaskPluginFromOperationParameter($task_operation): ?XrayAuditTaskPluginInterface {
    // Reconvert the operation word.
    $plugin = $this->pluginRepository->getInstancePluginTaskFromOperation($task_operation);
    return empty($plugin) ? NULL : $plugin;
  }

  /**
   * Gets the $task_plugin parameter.
   *
   * @param string $operation
   *   Operation.
   *
   * @return \Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface
   *   Plugin.
   */
  public function getTaskPluginFromOperation(string $operation): XrayAuditTaskPluginInterface {
    $task_plugin = $this->getTaskPluginFromOperationParameter($operation);
    if ($task_plugin === NULL) {
      throw new NotFoundHttpException();
    }
    return $task_plugin;
  }

}
