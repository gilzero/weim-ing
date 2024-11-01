<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\SiteStructure;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "views",
 *   label = @Translation("Views"),
 *   description = @Translation("Views."),
 *   group = "site_structure",
 *   sort = 2,
 *   operations = {
 *      "views" = {
 *          "label" = "Views",
 *          "description" = "Enabled views and cache configuration.",
 *          "dependencies" = {"views"}
 *       }
 *   }
 * )
 */
final class XrayAuditViewsPlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "xray_audit.plugin_repository".
   *
   * @var \Drupal\xray_audit\Services\PluginRepositoryInterface
   */
  protected $pluginRepository;

  /**
   * Service "xray_audit.csv_download_manager".
   *
   * @var \Drupal\xray_audit\Services\CsvDownloadManagerInterface
   */
  protected $csvDownloadManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $object_plugin = new static($configuration, $plugin_id, $plugin_definition);
    $object_plugin->pluginRepository = $container->get('xray_audit.plugin_repository');
    $object_plugin->csvDownloadManager = $container->get('xray_audit.csv_download_manager');
    $object_plugin->entityTypeManager = $container->get('entity_type.manager');
    $object_plugin->moduleHandler = $container->get('module_handler');
    $object_plugin->messenger = $container->get('messenger');

    return $object_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    return $this->getViewsData();
  }

  /**
   * Get data from views.
   *
   * @return array
   *   Views data.
   */
  protected function getViewsData(): array {
    $active_views_cache_configurations = [];

    $view_storage = $this->entityTypeManager->getStorage('view');
    $views = $view_storage->loadMultiple();

    foreach ($views as $view) {
      if (!($view instanceof ViewEntityInterface && $view->status())) {
        continue;
      }

      $view_id = $view->id();
      if (empty($view_id)) {
        continue;
      }

      $executable_view = Views::getView((string) $view_id);
      if (!$executable_view instanceof ViewExecutable) {
        continue;
      }

      $displays = $executable_view->storage->get('display');
      foreach ($displays as $display) {
        $active_views_cache_configurations[] = $this->getDisplayData($view, $executable_view, $display);
      }
    }

    // Sort by module and view id.
    usort($active_views_cache_configurations, function ($a, $b) {
      if ($a['module'] === $b['module']) {
        // If ages are equal, compare by height.
        return $a['id_view'] <=> $b['id_view'];
      }
      return $a['module'] <=> $b['module'];
    });

    return [
      'header_table' => [
        $this->t('Module'),
        $this->t('View ID'),
        $this->t('Display ID'),
        $this->t('View label'),
        $this->t('Display label'),
        $this->t('Plugin display'),
        $this->t('Cache plugin'),
        $this->t('Cache duration'),
        $this->t('Cache Tags'),
      ],
      'results_table' => $active_views_cache_configurations,
    ];
  }

  /**
   * Get display data.
   *
   * @param \Drupal\views\ViewEntityInterface $view
   *   View object.   *.
   * @param \Drupal\views\ViewExecutable $viewExecutable
   *   Display object.   *.
   * @param array $display
   *   Display data.
   *
   * @return array
   *   Display data.
   */
  protected function getDisplayData(ViewEntityInterface $view, ViewExecutable $viewExecutable, array $display): array {

    $data = [
      'module' => $view->get('module'),
      'id_view' => $viewExecutable->id(),
      'id_display' => $display['id'],
      'label_view' => $view->label(),
      'label_display' => $display['display_title'],
      'plugin_display' => $display['display_plugin'],
      'cache_plugin_id' => '',
      'cache_max_age' => '',
      'cache_tags' => '',
    ];

    $viewExecutable->setDisplay($display['id']);
    $display_object = $viewExecutable->getDisplay();

    if (!$display_object instanceof DisplayPluginBase) {
      return $data;
    }

    // Cache.
    $cache_plugin = $display_object->getPlugin('cache');
    if ($cache_plugin instanceof CachePluginBase) {
      $cache_max_age = $cache_plugin->getCacheMaxAge();

      switch ($cache_max_age) {
        case 0:
          $cache_max_age = 'No cache';
          break;

        case -1:
          $cache_max_age = 'Cache permanent';
          break;

        default:
          $cache_max_age = $cache_max_age . ' seconds';
          break;
      }

      $data['cache_plugin_id'] = $cache_plugin->getPluginId();
      $data['cache_max_age'] = $cache_max_age;
      $data['cache_tags'] = implode(', ', $cache_plugin->getCacheTags());
    }

    return $data;

  }

  /**
   * {@inheritdoc}
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $headers = $data['header_table'] ?? [];

    /** @var array<mixed> $definition */
    $definition = $this->getPluginDefinition();

    $rows = $data['results_table'] ?? [];

    // We only add the column operation if module views_ui is enabled.
    // If not we add a message to enable the module.
    if ($this->moduleHandler->moduleExists('views_ui')) {
      $destination = $definition['operations']['views']['url'] ?? '';
      $this->addOperationColumnTotable($headers, $rows, $destination);
    } else {
      // I want to add a drupal message in Drupal 10.
      $this->messenger->addWarning(
        $this->t('If you want to have the Operations column and access view editing, please enable the Views UI module.')
      );
    }

    $build = [];
    $build['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#weight' => 10,
      '#sticky' => TRUE,
    ];

    $build['download'] = [
      '#type' => 'link',
      '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation(
        'views',
        ['download']
      ),
      '#title' => $this->t('Download'),
      '#weight' => 5,
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
        ],
      ],
    ];

    if ($this->csvDownloadManager->downloadCsv()) {
      $this->csvDownloadManager->createCsv($data['results_table'], $data['header_table'], $operation);
    }

    return $build;
  }

  /**
   * Add operation column to table.
   *
   * @param array $headers
   *   Headers.
   * @param array $rows
   *   Rows
   * @param string $destination
   *   Destination.
   */
  protected function addOperationColumnTotable(array &$headers, array &$rows, string $destination) {
    $headers[] = $this->t('Operation');
    foreach ($rows as &$row) {
      // Create the URL object for the edit link.
      $edit_url = Url::fromRoute('entity.view.edit_display_form', [
        'view' => $row['id_view'],
        'display_id' => $row['id_display'],
      ], [
        'query' => ['destination' => $destination],
      ]);

      // Check if the URL is accessible for the current user.
      if ($edit_url->access()) {
        // Only if accessible, create the link.
        $row['edit_link'] = Link::fromTextAndUrl($this->t('Edit'), $edit_url);
      }
      else {
        // If the user does not have access to the edit link, leave it blank or handle accordingly.
        $row['edit_link'] = $this->t('No access to edit');
      }
    }
  }

}
