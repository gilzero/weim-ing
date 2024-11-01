<?php

namespace Drupal\xray_audit_insight\Plugin\insights;

use Drupal\xray_audit\Plugin\XrayAuditTaskPluginInterface;
use Drupal\xray_audit_insight\Plugin\XrayAuditInsightPluginBase;

/**
 * Plugin implementation of queries_data_node.
 *
 * @XrayAuditInsightPlugin (
 *   id = "modules_not_enabled",
 *   label = @Translation("Modules Not enabled"),
 *   description = @Translation("Modules not enabled"),
 *   sort = 1
 * )
 */
class XrayAuditEntityModulesNotEnabledPlugin extends XrayAuditInsightPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getInsightsForDrupalReport(): array {
    $cases = [];
    $insights = $this->getInsights();
    if ($insights['modules_not_enabled'] === TRUE) {
      foreach ($insights['modules'] as $type => $modules) {
        if (empty($modules)) {
          continue;
        }

        $title = $this->t('<i>Modules :type not enabled</i>', [':type' => $type]);
        $description = implode(', ', $modules);

        $cases[$type] = [];
        $cases[$type]['title'] = [
          '#markup' => $title,
          '#prefix' => '<dt>',
          '#suffix' => '</dt>',
        ];
        $cases[$type]['description'] = [
          '#prefix' => '<dd>',
          '#suffix' => '</dd>',
        ];
        $cases[$type]['description']['content'] = [
          '#markup' => $description,
        ];
      }
    }

    $title = $this->t('Modules not enabled');
    $value = '';
    $description = '';
    $severity = NULL;

    if (empty($cases)) {
      $value = $this->t("All the custom and contrib modules installed are enabled in some context.");
    }
    else {
      $value = $this->t(
        'The following custom and/or contrib modules are not enabled (<a href="@module_report">Modules Report</a>):',
        ['@module_report' => $this->getPathReport('modules', 'all_modules_report')]
      );
      $description = $this->renderer->renderPlain($cases);
      $severity = REQUIREMENT_WARNING;
    }

    return [
      'modules_not_enabled' => $this->buildInsightForDrupalReport($title, $value, $description, $severity),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getInsights(): array {
    return $this->areModulesNotEnabled();
  }

  /**
   * Modules is not enabled.
   *
   * @return array
   *   Modules enables.
   */
  protected function areModulesNotEnabled(): array {
    $disabled_modules = [
      'modules_not_enabled' => FALSE,
      'modules' => [
        'custom' => [],
        'contrib' => [],
      ],
    ];

    $task_plugin = $this->getInstancedPlugin('modules', 'all_modules_report');
    if (!$task_plugin instanceof XrayAuditTaskPluginInterface) {
      return FALSE;
    }

    $data = $task_plugin->getDataOperationResult('all_modules_report');

    if (empty($data)) {
      return $disabled_modules;
    }

    foreach (['custom', 'contrib'] as $type) {
      if (empty($data[$type])) {
        continue;
      }
      $this->processTypeModulesIsNotEnabled($data, $type, $disabled_modules);
    }

    return $disabled_modules;
  }

  /**
   * Check if there are custom modules that are not enabled.
   */
  protected function processTypeModulesIsNotEnabled($data, $type, array &$disabled_modules) {
    foreach ($data[$type] as $module) {
      if ($this->isModuleDisabledAndNotSubmodule($module)) {
        $disabled_modules['modules'][$type][] = $module['module'];
        $disabled_modules['modules_not_enabled'] = TRUE;
      }
    }
  }

  /**
   * Check if there are custom modules that are not enabled.
   *
   * @return bool
   *   Custom modules not enabled.
   */
  protected function isModuleDisabledAndNotSubmodule($module): bool {
    if (!isset($module['enabled_by']) || !isset($module['submodule'])) {
      return FALSE;
    }
    return $module['enabled_by'] === '' && $module['submodule'] === FALSE;
  }

}
