<?php

namespace Drupal\xray_audit_insight\Plugin\insights;

use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditInsightPlugin (
 *   id = "views",
 *   label = @Translation("Views not cached"),
 *   description = @Translation("Views not cached"),
 *   sort = 3
 * )
 */
class XrayAuditViewsNotCached extends XrayAuditInsightPluginBase {

  /**
   * List of contrib and core modules with views not cached.
   */
  const EXCLUDED_VIEWS = [
    'redirect_404',
    'watchdog',
    'watchdog_statistics',
  ];

  /**
   * {@inheritdoc}
   */
  public function getInsights(): array {
    return ['views_not_cached' => $this->isSomeViewsNotCached()];
  }

  /**
   * {@inheritdoc}
   */
  public function getInsightsForDrupalReport(): array {
    $build = [];
    $insights = $this->getInsights();

    $title = $this->t('Views cache');
    $value = '';
    $description = '';
    $severity = NULL;

    if (empty($insights['views_not_cached'])) {
      $description = $this->t("Cache is enabled for all views");
    }
    else {
      $value = $this->t(
        'There are views that are not cached, which can generate pages with poor cacheability (<a href="@views_report">Views Report</a>):',
        ['@views_report' => $this->getPathReport('views', 'views')]
          );
      $description = $this->t(
        '@views.',
        ['@views' => implode(', ', $insights['views_not_cached'])]
      );
      $severity = REQUIREMENT_WARNING;
    }

    return [
      'views_not_cached' =>
      $this->buildInsightForDrupalReport(
          $title,
          $value,
          $description,
          $severity),
    ];
  }

  /**
   * Check if there are views that are not cached.
   *
   * @return bool
   *   Views not cached.
   */
  protected function isSomeViewsNotCached(): array {
    $result = [];

    $task_plugin = $this->getInstancedPlugin('views', 'views');
    if (!$task_plugin instanceof XrayAuditTaskPluginInterface) {
      return $result;
    }

    $data = $task_plugin->getDataOperationResult('views');
    foreach ($data['results_table'] as $view) {
      if (!in_array($view['id_view'], self::EXCLUDED_VIEWS) && $view['cache_max_age'] === 'No cache') {
        $result[] = $view['id_view'] . ':' . $view['id_display'];
      }
    }

    return $result;
  }

}
