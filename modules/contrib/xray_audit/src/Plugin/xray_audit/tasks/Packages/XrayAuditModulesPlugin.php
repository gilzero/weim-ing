<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\Packages;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\CsvDownloadManagerInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of modules status.
 *
 * @XrayAuditTaskPlugin (
 *   id = "modules",
 *   label = @Translation("Modules"),
 *   description = @Translation("Report on the modules installed."),
 *   group = "package",
 *   sort = 1,
 *   local_task = 1,
 *   operations = {
 *      "all_modules_report" = {
 *          "label" = "All modules report",
 *          "description" = "List of all modules (core, contrib, and custom) with information on whether they are enabled and the recommended version."
 *       },
 *      "contrib_modules_report" = {
 *           "label" = "Contrib modules report",
 *           "description" = "List of contrib modules grouped by installation level (root, site, profile)."
 *        }
 *    },
 * )
 */
class XrayAuditModulesPlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "config.storage.sync".
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStoreSync;

  /**
   * Service "config.storage".
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStore;

  /**
   * Service "extension.list.module".
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

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
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $configStoreSync
   *   Service "config.storage.sync".
   * @param \Drupal\Core\Config\StorageInterface $configStore
   *   Service "config.storage".
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   Service "extension.list.module".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   * @param \Drupal\xray_audit\Services\CsvDownloadManagerInterface $csvDownloadManager
   *   Service "xray_audit.csv_download_manager".
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler.
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    StorageInterface $configStoreSync,
    StorageInterface $configStore,
    ModuleExtensionList $extensionListModule,
    PluginRepositoryInterface $pluginRepository,
    CsvDownloadManagerInterface $csvDownloadManager,
    ModuleHandlerInterface $module_handler
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configStoreSync = $configStoreSync;
    $this->configStore = $configStore;
    $this->extensionListModule = $extensionListModule;
    $this->pluginRepository = $pluginRepository;
    $this->csvDownloadManager = $csvDownloadManager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.storage.sync'),
      $container->get('config.storage'),
      $container->get('extension.list.module'),
      $container->get('xray_audit.plugin_repository'),
      $container->get('xray_audit.csv_download_manager'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    $data = [];
    switch ($operation) {

      case 'all_modules_report':
      case 'contrib_modules_report':
        $cid = $this->getPluginId() . ':modules_report';
        $data = $this->pluginRepository->getCachedData($cid);
        if (empty($data)) {
          $data = $this->getModuleStatus();
          if (!empty($data)) {
            // Duration cache object one hour.
            $this->pluginRepository->setCacheTempInv($cid, $data, 3600);
          }
          else {
            $data = [];
          }
        }
        break;

    }

    return $data;
  }

  /**
   * Modules custom and contrib.
   *
   * @return array
   *   Data.
   */
  protected function getModuleStatus() {
    $resultTable = [];
    $list_modules_installed = $this->extensionListModule->getList();
    $modules_actives = $this->getModulesActivesByConfigSync();
    if (empty($list_modules_installed) || empty($modules_actives)) {
      return $resultTable;
    }

    $projects_update_info = $this->getProjectsUpdateInfo();

    // Build the data.
    foreach ($list_modules_installed as $machine_name => $module) {
      $group_and_subgroup = $this->determineModuleGroup($module);
      $group = $group_and_subgroup['group'];
      $info = $module->info;

      // Two methods to check if a module is a submodule:
      // 1. For released versions of modules (from Drupal.org), check the 'project' property.
      // 2. For development versions, the 'project' property may not exist, so alternative approaches may be needed.
      // Note: In some cases, it might not be possible to reliably determine if a module is a submodule,
      // only with an expensive logic.
      $submodule = FALSE;
      if (!empty($info['project'])) {
        $submodule = $info['project'] !== $machine_name;
      } else {
        $path_name = $module->getPath();
        if (substr_count($path_name, 'modules/') > 1) {
          $submodule = TRUE;
        }
      }

      $resultTable[$group][$machine_name] = [
        'package' => $info['package'] ?? '',
        'project' => $info['project'] ?? '',
        'module' => $info['name'],
        'machine_name' => "$machine_name",
        'description' => $info['description'] ?? '',
        'used' => isset($modules_actives[$machine_name]) ? $this->t('Yes') : $this->t('No'),
        'enabled_by' => $modules_actives[$machine_name] ?? '',
        'version' => $info['version'] ?? '-',
        'recommended_version' => $projects_update_info[$machine_name]['recommended'] ?? '',
        'group' => $group,
        'subgroup' => $group_and_subgroup['subgroup'] ?? '',
        'submodule' => $submodule
      ];
    }

    return $resultTable;

  }

  /**
   * {@inheritDoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $builds = [];

    switch ($operation) {
      case 'all_modules_report':
        $builds = $this->buildDataRenderAllModulesReport($data);
        break;

      case 'contrib_modules_report':
        $builds = $this->buildDataRenderContribModulesReport($data);
        break;

    }
    return $builds;

  }

  /**
   * Build data  render Contrib Modules Report.
   *
   * @param array $data
   *   Data.
   *
   * @return array
   *   Data.
   */
  public function buildDataRenderContribModulesReport(array $data) {
    $operation = 'contrib_modules_report';

    // Remove unneeded columns.
    foreach ($data as &$group) {
      foreach($group as &$module) {
        unset($module['submodule']);
      }
    }

    $group_info = [];

    if (empty($data)) {
      return [
        "#markup" => $this->t("Not data found."),
      ];
    }

    $headerTable = [
      $this->t('Package'),
      $this->t('Project'),
      $this->t('Module'),
      $this->t('Machine name'),
      $this->t('Description'),
      $this->t('Used'),
      $this->t('Activated by'),
      $this->t('Version'),
      $this->t('Recommended version'),
      $this->t('Level'),
    ];

    $group_info['root'] = [
      'title' => $this->t("Installed at the root level"),
    ];

    $group_info['site'] = [
      'title' => $this->t("Installed at site level"),
    ];

    $group_info['profile'] = [
      'title' => $this->t("Installed at profile level"),
    ];

    $group_info['unknown'] = [
      'title' => $this->t("Installed at unknown level"),
    ];

    $contrib_modules = $data['contrib'];

    // Grouped by subgroup.
    $contrib_modules_grouped_by_subtype = [];
    foreach ($contrib_modules as $contrib_module) {
      $type = 'unknown';

      switch (TRUE) {
        case 'root' === $contrib_module['subgroup']:
          $type = 'root';
          break;

        case strpos($contrib_module['subgroup'], 'site') === 0:
          $type = 'site';
          break;

        case 'profile' === $contrib_module['subgroup']:
          $type = 'profile';
          break;
      }

      unset($contrib_module['group']);

      if (!isset($contrib_modules_grouped_by_subtype[$type])) {
        $contrib_modules_grouped_by_subtype[$type] = [];
      }
      $contrib_modules_grouped_by_subtype[$type][] = $contrib_module;
    }

    $builds = [];
    foreach ($contrib_modules_grouped_by_subtype as $type => $group_modules) {
      // Sort the modules by package.
      $key_values = array_column($group_modules, 'package');
      array_multisort($key_values, SORT_ASC, $group_modules);

      $builds[$type] = [
        '#theme' => 'details',
        '#title' => $group_info[$type]['title'],
        '#attributes' => ['class' => ['package-listing']],
        '#summary_attributes' => [],
        '#children' => [
          'tables' => [
            '#theme' => 'table',
            '#header' => $headerTable,
            '#rows' => $group_modules,
          ],
        ],
      ];
    }

    $builds['download'] = [
      '#type' => 'link',
      '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation($operation, ['download']),
      '#title' => $this->t('Download'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
        ],
      ],
    ];
    if ($this->csvDownloadManager->downloadCsv()) {

      $headers = [
        'Package',
        'Project',
        'Module',
        'Machine name',
        'Description',
        'Used',
        'Activated by',
        'Version',
        'Recommended version',
      ];

      $csvData = $this->getAllDataAtOnce($operation);
      $operation = $operation ?? '';
      $this->csvDownloadManager->createCsv($csvData, $headers, $operation);
    }

    return $builds;

  }

  /**
   * Build data  render All Modules Report.
   *
   * @param array $data
   *   Data.
   *
   * @return array
   *   Data.
   */
  public function buildDataRenderAllModulesReport(array $data) {
    $operation = 'all_modules_report';

    $group_info = [];

    if (empty($data)) {
      return [
        "#markup" => $this->t("Not data found."),
      ];
    }

    $headerTable = [
      $this->t('Package'),
      $this->t('Project'),
      $this->t('Module'),
      $this->t('Machine name'),
      $this->t('Description'),
      $this->t('Used'),
      $this->t('Activated by'),
      $this->t('Version'),
      $this->t('Recommended version'),
      $this->t('Type'),
    ];

    $group_info['core'] = [
      'title' => $this->t("Modules core"),
      'description' => $this->t("Modules belong to the core"),
    ];

    $group_info['contrib'] = [
      'title' => $this->t("Modules contrib"),
      'description' => $this->t("Modules contrib"),
    ];

    $group_info['custom'] = [
      'title' => $this->t("Modules custom"),
      'description' => $this->t("Modules custom"),
    ];

    $group_info['profile'] = [
      'title' => $this->t("Profiles"),
      'description' => $this->t("Profiles"),
    ];

    $group_info['other'] = [
      'title' => $this->t("Others"),
      'description' => $this->t("Others"),
    ];

    $builds = [];
    foreach ($data as $type => $group_modules) {
      // Remove element subgroup from this report.
      $group_modules = array_map(function ($module) {
        if (!is_array($module)) {
          return $module;
        }
        unset($module['subgroup']);
        unset($module['submodule']);
        return $module;
      }, $group_modules);

      // Sort the modules by package.
      $key_values = array_column($group_modules, 'package');
      array_multisort($key_values, SORT_ASC, $group_modules);

      $builds[$type] = [
        '#theme' => 'details',
        '#title' => $group_info[$type]['title'],
        '#description' => $group_info[$type]['description'],
        '#attributes' => ['class' => ['package-listing']],
        '#summary_attributes' => [],
        '#children' => [
          'tables' => [
            '#theme' => 'table',
            '#header' => $headerTable,
            '#rows' => $group_modules,
          ],
        ],
      ];
    }
    $builds['download'] = [
      '#type' => 'link',
      '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation($operation, ['download']),
      '#title' => $this->t('Download'),
      '#attributes' => [
        'class' => [
          'button',
          'button--primary',
          'button--small',
        ],
      ],
    ];
    if ($this->csvDownloadManager->downloadCsv()) {

      $headers = [
        'Package',
        'Project',
        'Module',
        'Machine name',
        'Description',
        'Used',
        'Activated by',
        'Version',
        'Recommended version',
        'Core, contrib, custom or other',
      ];

      $csvData = $this->getAllDataAtOnce($operation);
      $operation = $operation ?? '';
      $this->csvDownloadManager->createCsv($csvData, $headers, $operation);
    }

    return $builds;

  }

  /**
   * Get the modules actives by configuration files or from database.
   *
   * @return array
   *   Status modules.
   */
  protected function getModulesActivesByConfigSync(): array {
    // Set the module actives checking the configuration files.
    $modules_actives = [];

    // This service get the config objects from exported configuration files.
    $service_config_store = $this->configStoreSync;

    // If there is no configuration exported, get them from database.
    // In this case, the modules actives in the site will show as actives by
    // core.extension although, they have actually
    // been activated by a config split.
    // In multi sites the configuration files are more reliable.
    if (!$service_config_store->exists('core.extension')) {
      $service_config_store = $this->configStore;
    }

    // Get the name of all the config splits configuration files.
    $all_config_splits = $service_config_store->listAll('config_split.config_split.');

    // Add the core.extension (modules enable by default).
    $all_config_splits[] = 'core.extension';

    // Storage the active modules.
    foreach ($all_config_splits as $config_split_name) {
      $config_split = $service_config_store->read($config_split_name);
      if (empty($config_split['module'])) {
        continue;
      }
      $module_names = array_keys($config_split['module']);
      foreach ($module_names as $name) {
        if (!isset($modules_actives[$name])) {
          $modules_actives[$name] = '';
        }
        else {
          $modules_actives[$name] .= ', ';
        }
        $modules_actives[$name] .= $config_split_name;
      }
    }

    return $modules_actives;

  }

  /**
   * Determines to which group the module belongs.
   *
   * @param \Drupal\Core\Extension\Extension $module
   *   Module.
   *
   * @return array
   *   Module group and subgroup.
   */
  protected function determineModuleGroup(Extension $module):array {
    $group = '';
    $subgroup = '';
    $module_path = $module->getPath();
    $module_info = $module->info;

    switch (TRUE) {
      case (isset($module->origin) && $module->origin === 'core'):
        $group = 'core';
        break;

      case strpos($module_path, 'modules/contrib') !== FALSE:
        $group = 'contrib';
        break;

      case strpos($module_path, 'modules/custom') !== FALSE:
        $group = 'custom';
        break;

      case strpos($module_path, 'profiles/') !== FALSE:
        $group = 'profile';
        break;

      // At last resource to check if contrib or not, check info added by
      // drupal.
      case !empty($module_info['project']):
        $group = 'contrib';
        break;

      // And if none of the conditions before were met and the module
      // is inside anywhere in modules just consider it custom.
      case strpos($module_path, 'modules/') !== FALSE:
        $group = 'custom';
        break;

      default:
        $group = 'other';
        break;
    }

    if ($group === 'contrib') {

      switch (TRUE) {

        // It is a site.
        case strpos($module_path, 'sites/') === 0:
          $path_elements = explode('/', $module_path);
          $site = $path_elements[1];
          $subgroup = 'site: ' . $site;
          break;

        // It is in the profile folder installed.
        case strpos($module_path, 'profiles/') === 0:
          $subgroup = 'profile';
          break;

        // It is in modules contrib folder.
        case strpos($module_path, 'modules/') === 0:
          $subgroup = 'root';
          break;
      }

    }

    return ['group' => $group, 'subgroup' => $subgroup, 'module_path' => $module_path];
  }

  /**
   * Get projects update info.
   *
   * @return array
   *   Projects update info.
   */
  protected function getProjectsUpdateInfo(): array {
    $projects_update_info = [];

    if ($this->moduleHandler->moduleExists('update')) {
      $available = update_get_available(TRUE);

      if (!empty($available) && function_exists('update_calculate_project_data')) {
        $projects_update_info = update_calculate_project_data($available);
      }
    }

    return $projects_update_info;
  }

  /**
   * Returns all module's data in one array.
   *
   * @param string|null $operation
   *   Operation.
   *
   * @return array
   *   All data from operation.
   */
  public function getAllDataAtOnce(?string $operation): array {
    $data_per_group = $this->getDataOperationResult($operation);
    $data = [];

    foreach ($data_per_group as $values) {
      foreach ($values as $value) {
        $data[] = $value;
      }
    }
    return $data;
  }

}
