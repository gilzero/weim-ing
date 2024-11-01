<?php

namespace Drupal\xray_audit_insight\Plugin;

/**
 * Interface for xray_audit_query_data plugins.
 */
interface XrayAuditInsightPluginInterface {

  /**
   * Get the label.
   *
   * @return string
   *   Label.
   */
  public function label(): string;

  /**
   * If it is active.
   *
   * @return bool
   *   It is active.
   */
  public function isActive(): bool;

  /**
   * Get sort.
   *
   * @return int
   *   The position.
   */
  public function getSort();

  /**
   * Insights.
   *
   * @return array
   *   The insights in array format.
   */
  public function getInsights(): array;

  /**
   * Create the insights for Drupal Report.
   *
   * @return array
   *   The insights in array format.
   */
  public function getInsightsForDrupalReport(): array;

}
