<?php

namespace Drupal\xray_audit\Plugin\xray_audit\tasks\SiteStructure;

use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\xray_audit\Form\MenuSelectorForm;
use Drupal\xray_audit\Plugin\XrayAuditTaskPluginBase;
use Drupal\xray_audit\Services\CsvDownloadManagerInterface;
use Drupal\xray_audit\Services\NavigationArchitectureInterface;
use Drupal\xray_audit\Services\PluginRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditTaskPlugin (
 *   id = "navigation_architecture",
 *   label = @Translation("Navigation Architecture"),
 *   description = @Translation("Navigation Architecture."),
 *   group = "site_structure",
 *   sort = 1,
 *   operations = {
 *      "menu" = {
 *          "label" = "Menus",
 *          "description" = "Menu structures.",
 *          "dependencies" = {"menu_link_content"}
 *       }
 *   }
 * )
 */
final class XrayAuditNavigationArchitecturePlugin extends XrayAuditTaskPluginBase {

  /**
   * Service "navigation_architecture".
   *
   * @var \Drupal\xray_audit\Services\NavigationArchitectureInterface
   */
  protected $navigationArchitecture;

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
   * Service "request_stack".
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Service "form_builder".
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\xray_audit\Services\NavigationArchitectureInterface $navigationArchitecture
   *   Service "navigation_architecture".
   * @param \Drupal\xray_audit\Services\PluginRepositoryInterface $pluginRepository
   *   Service "xray_audit.plugin_repository".
   * @param \Drupal\xray_audit\Services\CsvDownloadManagerInterface $csvDownloadManager
   *   Service "xray_audit.csv_download_manager".
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Service "request_stack".
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Service "form_builder".
   */
  public function __construct(
    array $configuration,
    string $plugin_id,
    $plugin_definition,
    NavigationArchitectureInterface $navigationArchitecture,
    PluginRepositoryInterface $pluginRepository,
    CsvDownloadManagerInterface $csvDownloadManager,
    RequestStack $request_stack,
    FormBuilderInterface $form_builder
  ) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->navigationArchitecture = $navigationArchitecture;
    $this->pluginRepository = $pluginRepository;
    $this->csvDownloadManager = $csvDownloadManager;
    $this->requestStack = $request_stack;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('xray_audit.navigation_architecture'),
      $container->get('xray_audit.plugin_repository'),
      $container->get('xray_audit.csv_download_manager'),
      $container->get('request_stack'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDataOperationResult(string $operation = '') {
    switch ($operation) {
      case 'menu':

        $default_data = [
          'menu' => '',
          'items' => [],
          'item_number' => 0,
          'level_max' => 0,
          'show_parent_reference' => FALSE,
        ];

        $query_parameters_values = $this->getQueryParametersFromRequest();

        if (!isset($query_parameters_values['menu'])) {
          if (!isset($this->navigationArchitecture->getMenuList()['main'])) {
            return $default_data;
          }
          $query_parameters_values['menu'] = 'main';
        }

        $menu_architecture = $this->navigationArchitecture->getMenuArchitecture($query_parameters_values['menu']);

        return [
          'menu' => $query_parameters_values['menu'],
          'items' => $menu_architecture['items'] ?? [],
          'item_number' => $menu_architecture['item_number'] ?? 0,
          'level_max' => $menu_architecture['level_max'] ?? 0,
          'show_parent_reference' => $query_parameters_values['show_parent_reference'] ? TRUE : FALSE,
        ];
    }

    return [];
  }

  /**
   * Get data from query parameters.
   *
   * @return mixed[]
   *   Data from query parameters.
   */
  protected function getQueryParametersFromRequest(): array {
    $data = ['menu' => NULL, 'show_parent_reference' => NULL];
    $key_items = array_keys($data);

    /**@var \Symfony\Component\HttpFoundation\Request $request*/
    $request = $this->requestStack->getCurrentRequest();
    if (!$request instanceof Request) {
      return $data;
    }

    foreach ($key_items as $item_key) {
      $data[$item_key] = $request->query->get($item_key, NULL);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
   * @SuppressWarnings(PHPMD.NPathComplexity)
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  public function buildDataRenderArray(array $data, string $operation = '') {
    $build = [];
    $build['form'] = $this->formBuilder->getForm(MenuSelectorForm::class);

    if (empty($data['items'])) {
      return $build;
    }

    $menu_id = $data['menu'];

    $headers = [];
    $rows = [];

    for ($i = 1; $i <= $data['level_max']; $i++) {
      $headers['l_' . $i] = 'L' . $i;
    }

    $headers['level'] = $this->t('Level');
    $headers['enabled'] = $this->t('Enabled');
    $headers['link'] = $this->t('Link');
    $headers['target'] = $this->t('Link description');
    $headers['operation'] = $this->t('Operation');

    foreach ($data['items'] as $item) {
      $row = [];

      for ($i = 1; $i <= $data['level_max']; $i++) {

        switch (TRUE) {
          case  $i < $item['level'] && isset($item['levels'][$i]):

            $row['l_' . $i] = [
              'data' => ($data['show_parent_reference']) ? $item['levels'][$i] : '',
              'class' => ['xray-audit-cell-background-color-gray'],
            ];
            break;

          case $i === $item['level']:
            $row['l_' . $i] = $item['title'];
            break;

          default:
            $row['l_' . $i] = '';
        }

      }

      $row['level'] = $item['level'];
      $row['enabled'] = ($item['enabled']) ? $this->t('Yes') : $this->t('No');
      $row['link'] = !empty($item['link']) ? Link::fromTextAndUrl($item['link']->toString(), $item['link']) : '';
      $row['target'] = empty($item['target']) ? '' : implode(' ', $item['target']);
      $row['operation'] = !empty($item['edit_link']) ? Link::fromTextAndUrl($this->t('Edit'), $item['edit_link']) : '';
      ;

      $rows[] = $row;
    }

    $build['main_table'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#weight' => 30,
      '#attributes' => [
        'class' => [
          'xray-audit__table',
          'xray-audit__sticky-header',
          'alignment-vertical-center',
        ],
      ],
      '#attached' => [
        'library' => [
          'xray_audit/xray_audit',
        ],
      ],
    ];

    if ($menu_id) {
      $build['download'] = [
        '#type' => 'link',
        '#url' => $this->pluginRepository->getTaskPageOperationFromIdOperation(
          'menu',
          [0 => 'download', 'menu' => $menu_id, 'show_parent_reference' => $data['show_parent_reference'] ?? 0]
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
    }

    if ($this->csvDownloadManager->downloadCsv()) {
      $operation = 'menu-' . $menu_id;
      unset($headers['operation']);
      $rows_csv = [];
      foreach ($rows as $key => $row) {
        unset($row['operation']);
        $row['link'] = !empty($row['link']) ? $row['link']->getUrl()->toString() : '';
        foreach ($row as $key_column => &$column) {
          if (str_contains($key_column, 'l_')) {
            $column = is_array($column) && isset($column['data']) ? $column['data'] : $column;
          }
        }

        $rows_csv[$key] = $row;
      }
      $this->csvDownloadManager->createCsv($rows_csv, $headers, $operation);
    }

    return $build;

  }

}
