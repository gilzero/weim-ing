<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\Packages;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\CsvDownloadManagerInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of themes status.
 *
 * @XrayAuditTaskPlugin (
 *   id = "themes",
 *   label = @Translation("Themes Status (sync config files)"),
 *   description = @Translation("Themes status against sync configuration files (include config splits)."),
 *   group = "package",
 *   sort = 1,
 *   operations = {
 *      "themes" = {
 *          "label" = "Themes",
 *          "description" = ""
 *       }
 *    },
 * )
 */
class XrayAuditThemesPlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "theme_handler".
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

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
   * Service "config.factory".
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Service "theme_handler".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   * @param \Drupal\xray_audit\Services\CsvDownloadManagerInterface $csvDownloadManager
   *   Service "xray_audit.csv_download_manager".
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Service "config.factory".
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    ThemeHandlerInterface $themeHandler,
    PluginRepositoryInterface $pluginRepository,
    CsvDownloadManagerInterface $csvDownloadManager,
    ConfigFactoryInterface $configFactory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeHandler = $themeHandler;
    $this->pluginRepository = $pluginRepository;
    $this->csvDownloadManager = $csvDownloadManager;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme_handler'),
      $container->get('xray_audit.plugin_repository'),
      $container->get('xray_audit.csv_download_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {

      case 'themes':
        return $this->getThemeStatus();

    }
    return [];

  }

  /**
   * Modules custom and contrib.
   *
   * @return array
   *   Data.
   */
  protected function getThemeStatus() {
    $resultTable = [];
    $list_themes_installed = $this->themeHandler->listInfo();
    if (empty($list_themes_installed)) {
      return $resultTable;
    }
    $default_admin_theme = $this->configFactory->get('system.theme')->get('admin');
    $default_admin_theme_name = $this->themeHandler->getName($default_admin_theme);
    // Build the data.
    foreach ($list_themes_installed as $machine_name => $theme) {
      $info = $theme->info;
      $group = (isset($info['package']) && stristr($info['package'], 'core')) ? 'core' : 'not_core';
      $resultTable[$group][$machine_name] = [
        'machine_name' => $machine_name,
        'project' => $info['project'] ?? '',
        'Theme' => $info['name'],
        'Default' => $info['name'] == $this->themeHandler->getDefault() || $info['name'] == $default_admin_theme_name ? $this->t('Yes') : $this->t('No'),
        'version' => $info['version'] ?? '-',
        'group' => $group,
      ];
    }

    return $resultTable;

  }

  /**
   * {@inheritDoc}
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $group_info = [];

    if (empty($data)) {
      return [
        "#markup" => $this->t("Not data found,
              maybe the configuration files have not been exported."),
      ];
    }

    $headerTable = [
      $this->t('Machine name'),
      $this->t('Project'),
      $this->t('Theme'),
      $this->t('Used'),
      $this->t('Version'),
    ];

    $group_info['core'] = [
      'title' => $this->t("Core Themes"),
      'description' => $this->t("Themes belong to the core"),
    ];
    $group_info['not_core'] = [
      'title' => $this->t("Themes custom and contrib"),
      'description' => $this->t("Themes custom and contrib"),
    ];

    $builds = [];
    foreach ($data as $type => $group_themes) {
      // Sort the modules by package.
      $key_values = array_column($group_themes, 'Theme');
      array_multisort($key_values, SORT_ASC, $group_themes);

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
            '#rows' => $group_themes,
          ],
        ],
      ];
    }
    $builds['download'] = [
      '#type' => 'link',
      '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation('themes', ['download']),
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
        'project',
        'theme',
        'machine_name',
        'used',
        'version',
        'core or custom and contrib',
      ];

      $csvData = $this->getAllDataAtOnce($operation);
      $operation = $operation ?? '';
      $this->csvDownloadManager->createCsv($csvData, $headers, $operation);
    }

    return $builds;
  }

  /**
   * Returns all module's data in one array.
   *
   * @param string|null $operation
   *   Operation.
   *
   * @return array
   *   All data.
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
