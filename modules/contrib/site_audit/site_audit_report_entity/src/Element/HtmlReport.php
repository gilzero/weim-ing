<?php

namespace Drupal\site_audit_report_entity\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\json_field\JsonMarkup;
use Drupal\site_audit\Annotation\SiteAuditCheck;
use Drupal\site_audit\Plugin\SiteAuditCheckBase;
use Drupal\site_audit_report_entity\Entity\SiteAuditReport;

/**
 * Provides a HTML report.
 *
 * @RenderElement("site_audit_report_html")
 */
class HtmlReport extends RenderElement implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#report' => '',
      '#langcode' => '',
      '#pre_render' => [[$class, 'preRenderText']],
    ];
  }

  public static function trustedCallbacks() {
    return ['preRenderText'];
  }

  /**
   * Pre-render callback: Renders a JSON text element into #markup.
   *
   * @todo Add JSON formatting libraries.
   */
  public static function preRenderText($element) {

    $report = $element['#report'];

    // Clear out empty entries.
    // @TODO: Figure out why there are empty entries.
    $report = array_filter($report);
    foreach ($report as $section_id => $report_section ) {
      # @TODO: Figure out how to use Normalizer properly.
      $report_section = (object) $report_section;
      $report_section->counts = [
        SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO => 0,
        SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS => 0,
        SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN => 0,
        SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL => 0,
      ];

      $classes = SiteAuditReport::getPercentClasses($report_section->percent);
      $class = implode(' ', $classes);

      $element[$section_id] = array(
        '#type' => 'details',
        '#title' => "<em class='{$class}'></em> $report_section->label  <em>$report_section->percent%</em>",
        'checks' => [
          '#type' => 'table',
        ],
      );

      foreach ($report_section->checks as $check_class => $check) {
        $check = (object) $check;

        // Tally checks.
        $score = $check->score?? 0;
        $report_section->counts[$score]++;

        # @TODO: Remove when plugin_id property is added to site_audit core
        // Find plugin for the listed class.
        $plugins = \Drupal::service('plugin.manager.site_audit_check')
          ->getDefinitions();
        foreach ($plugins as $plugin_id => $plugin_data) {
          if ($check_class == $plugin_data['class']) {
            $check->plugin_id = $plugin_id;
            break;
          }
        }

        $css_classes = SiteAuditReport::getScoreClasses($check->score);
        $css_classes = implode(' ', $css_classes);
        $label = "<em class='{$css_classes}'></em> $check->label";

        $check_element = [
          'icon' => [
            [
              '#markup' => $label,
            ]
          ],
          'column' => [
            'result' => [
              '#type' => 'item',
              '#title' => $check->label,
              '#description' => $check->description,
            ],
          ],
        ];

        // Ensure Result and action have a consistent wrapper.
        $check_element['column']['result'] = [
          '#type' => 'item',
        ];
        if (is_string($check->result)) {
          $check_element['column']['result']['#markup'] = $check->result;
        }
        elseif (is_array($check->result)) {
          $check_element['column']['result']['build'] = $check->result;
        }

        if (is_string($check->action)) {
          $check_element['column']['act'] = [
            '#title' => t('Action'),
            '#type' => 'item',
            '#markup' => $check->action,
          ];
        }
        elseif (is_array($check->action)) {
          $check_element['column']['act'] = [
            '#title' => t('Action'),
            '#type' => 'item',
            'actions' => $check->action,
          ];
        }

        if (!isset($check->plugin_id)) {
          $element[$section_id]['checks'][] = $check_element;
        }
        else {
          $element[$section_id]['checks'][$check->plugin_id] = $check_element;
        }
      }

      unset($report_section->counts[SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO]);
      $widget = '';
      foreach (array_filter($report_section->counts) as $score => $count) {
        $css_class = implode(' ', SiteAuditReport::getScoreClasses($score));
        $widget .= "<span class='check-count'><em class='$css_class'></em> $count</span>";
      }
      $element[$section_id]['#title'] .= "<span class='check-counts'>$widget</span>";
    }
    return $element;
  }

}
