<?php

namespace Drupal\xray_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\xray_audit\Form\FlushCacheForm;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for the list of groups.
 */
final class XrayAuditHomeController extends ControllerBase {

  /**
   * Plugin repository.
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs of Controller.
   *
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $plugin_repository
   *   Plugin manager for Groups.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Service renderer.
   */
  public function __construct(PluginRepositoryInterface $plugin_repository, RendererInterface $renderer) {
    $this->pluginRepository = $plugin_repository;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self(
      $container->get('xray_audit.plugin_repository'),
      $container->get('renderer'),
    );
  }

  /**
   * Builds the home response.
   *
   * @return array
   *   Render array.
   */
  public function build(): array {
    $build = [];
    $groupDefinitions = $this->pluginRepository->getGroupPluginDefinitions();
    if (empty($groupDefinitions)) {
      return [];
    }
    usort($groupDefinitions, function ($a, $b) {
      return ($a['sort'] ?? 0) <=> ($b['sort'] ?? 0);
    });

    foreach ($groupDefinitions as $group_definition) {
      // The different tasks.
      $build[] = [
        '#theme' => 'admin_block',
        '#block' => [
          'title' => (string) $group_definition['label'],
          'content' => [
            '#theme' => 'admin_block_content',
            '#content' => $this->buildItems($group_definition),
          ],
          'description' => (string) $group_definition['description'],
        ],
      ];
    }

    $build['form'] = $this->formBuilder()->getForm(FlushCacheForm::class);

    return $build;
  }

  /**
   * Build items for the group.
   *
   * @param array $group_definition
   *   Group definition.
   *
   * @return array
   *   Build array.
   */
  public function buildItems(array $group_definition): array {
    $build = [];

    $group_plugin_instance = $this->pluginRepository->getInstancePluginGroup($group_definition['id']);

    if ($group_plugin_instance === NULL) {
      return $build;
    }

    $task_definitions = $group_plugin_instance->getPluginTaskDefinitionsThisGroup();

    if (empty($task_definitions)) {
      return $build;
    }

    foreach ($task_definitions as $task_definition) {

      if (empty($task_definition['operations'])) {
        continue;
      }

      if (empty($task_definition['local_task'])) {
        $this->buildAllOperations($build, $task_definition);
        continue;
      }

      $this->buildLocalTaskCase($build, $task_definition);
    }

    return $build;
  }

  /**
   * Add items to the build array in the local task case (tabs).
   *
   * @param array $build
   *   Build array.
   * @param array $task_definition
   *   Task definition.
   */
  protected function buildLocalTaskCase(array &$build, array $task_definition) {
    // Only add the url of the firs item.
    $operation = reset($task_definition['operations']);
    $build[] = $this->buildTaskItem($task_definition['label'] ?? '', $operation['route_name'] ?? '', (string) ($task_definition['description'] ?? ''));
  }

  /**
   * Add items to the build array when all operations work independently.
   *
   * @param array $build
   *   Build array.
   * @param array $task_definition
   *   Task definition.
   */
  protected function buildAllOperations(array &$build, array $task_definition) {
    foreach ($task_definition['operations'] as $operation) {
      if (!empty($operation['not_show'])) {
        continue;
      }
      $build[] = $this->buildTaskItem($operation['label'] ?? '', $operation['route_name'] ?? '', (string) ($operation['description'] ?? ''));
    }
  }

  /**
   * Build task item.
   *
   * @param string $title
   *   Title of the operation.
   * @param string $routeName
   *   Route name.
   * @param string $description
   *   Description of the operation.
   *
   * @return array
   *   Task item.
   */
  protected function buildTaskItem(string $title, string $routeName, string $description): array {
    return [
      'title' => $title,
      'url' => Url::fromRoute($routeName),
      'localized_options' => [],
      'description' => $description,
    ];
  }

}
