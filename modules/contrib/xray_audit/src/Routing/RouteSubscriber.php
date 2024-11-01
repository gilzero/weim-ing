<?php

namespace Drupal\xray_audit\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The plugin manager for tasks.
   *
   * @var \Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager
   */
  protected $pluginManagerTasks;

  /**
   * Constructs a new RouteSubscriber object.
   *
   * @param \Drupal\xray_audit\Plugin\XrayAuditTaskPluginManager $plugin_manager_tasks
   *   The plugin manager for tasks.
   */
  public function __construct(XrayAuditTaskPluginManager $plugin_manager_tasks) {
    $this->pluginManagerTasks = $plugin_manager_tasks;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $task_definitions = $this->pluginManagerTasks->getDefinitions();

    foreach ($task_definitions as $task_definition) {
      if (empty($task_definition['operations'])) {
        continue;
      }

      foreach ($task_definition['operations'] as $operation_id => $operation) {
        $name_route = $operation['route_name'] ?? '';
        $path = $operation['url'] ?? '';
        $requirements = ['_permission' => 'access xray audit reports'];
        $defaults = [
          '_controller' => '\Drupal\xray_audit\Controller\XrayAuditTaskController::build',
          '_title_callback' => '\Drupal\xray_audit\Controller\XrayAuditTaskController::getTitle',
          'task_operation' => $operation_id,
        ];
        $collection->add($name_route, new Route($path, $defaults, $requirements));
      }

    }
  }

}
