<?php

namespace Drupal\xray_audit_insight\Plugin\insights;

use Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditInsightPlugin (
 *   id = "internal_page_cache",
 *   label = @Translation("Internal Page Cache status"),
 *   description = @Translation("Internal Page Cache status"),
 *   sort = 4
 * )
 */
class XrayAuditInternalPageCache extends XrayAuditInsightPluginBase {

  /**
   * There is no system to cache pages.
   */
  const NOT_CACHE_ANONYMOUS_USER = 0;

  /**
   * Page cache enable. It could be correct.
   */
  const ONLY_PAGE_CACHE = 1;

  /**
   * Both systems actives, it is not recommended.
   */
  const PAGE_CACHE_AND_PROXY_CACHE = 2;

  /**
   * Only proxy cache, correct set.
   */
  const ONLY_PROXY_CACHE = 3;

  /**
   * {@inheritdoc}
   */
  public function getInsights(): array {
    return ['internal_page_cache' => $this->checkPageCacheActiveAndReverseProxy()];
  }

  /**
   * {@inheritdoc}
   */
  public function getInsightsForDrupalReport(): array {
    $build = [];
    $insights = $this->getInsights();

    $title = $this->t('Internal Page Cache module');
    $value = '';
    $description = '';
    $severity = NULL;

    switch ($insights['internal_page_cache']) {
      case self::ONLY_PAGE_CACHE:
        $value = $this->t('Internal Page Cache is enabled and Proxy Cache is not configured.');
        $description = $this->t('In large sites is recommended to use a Proxy Cache and disable Internal Page Cache.');
        $severity = REQUIREMENT_WARNING;
        break;

      case self::ONLY_PROXY_CACHE:
        $value = $this->t('Internal Page Cache is disabled and Proxy Cache is configured.');
        $description = '';
        $severity = REQUIREMENT_OK;
        break;

      case self::PAGE_CACHE_AND_PROXY_CACHE:
        $value = $this->t('Internal Page Cache is enabled and Proxy Cache is configured.');
        $description = $this->t('The Internal Page Cache module does not need to be enabled if there is a Proxy Cache.');
        $severity = REQUIREMENT_WARNING;
        break;

      case self::NOT_CACHE_ANONYMOUS_USER:
        $value = $this->t('Internal Page Cache is disabled and Proxy Cache is not configured.');
        $description = $this->t('It is recommend to use a Cache system for anonymous users to improve performance.');
        $severity = REQUIREMENT_WARNING;
        break;
    }

    return [
      'internal_page_cache' =>
      $this->buildInsightForDrupalReport(
          $title,
          $value,
          $description,
          $severity),
    ];
  }

  /**
   * Check page cache active and reverse proxy.
   *
   * @return int
   *   Value.
   */
  protected function checkPageCacheActiveAndReverseProxy(): int {
    $page_cache_enabled = $this->moduleHandler->moduleExists('page_cache');
    $reverse_proxy_enabled = (bool) $this->siteSettings->get('reverse_proxy');
    return $this->evaluateStatus($page_cache_enabled, $reverse_proxy_enabled);
  }

  /**
   * Evaluate the result.
   *
   * @param bool $page_cache_enabled
   *   Page cache enable.
   * @param bool $reverse_proxy_enabled
   *   Reverse proxy enabled.
   *
   * @return int
   *   Result.
   */
  protected function evaluateStatus(bool $page_cache_enabled, bool $reverse_proxy_enabled): int {
    if ($page_cache_enabled && $reverse_proxy_enabled) {
      return self::PAGE_CACHE_AND_PROXY_CACHE;
    }
    if ($page_cache_enabled) {
      return self::ONLY_PAGE_CACHE;
    }
    if ($reverse_proxy_enabled) {
      return self::ONLY_PROXY_CACHE;
    }
    return self::NOT_CACHE_ANONYMOUS_USER;
  }

}
