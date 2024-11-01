<?php

namespace Drupal\site_audit_report_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Datetime\Element\Datetime;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\site_audit\Renderer\Html;

/**
 * Plugin implementation of the 'text_default' formatter.
 *
 * @FieldFormatter(
 *   id = "site_audit_report_html",
 *   label = @Translation("HTML Report"),
 *   field_types = {
 *     "map",
 *   }
 * )
 */
class SiteAuditDataHtmlFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // The ProcessedText element already handles cache context & tag bubbling.
    // @see \Drupal\filter\Element\ProcessedText::preRenderText()
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'site_audit_report_html',
        '#report' => $item->getValue(),
        '#langcode' => $langcode,
      ];
    }
    return $elements;
  }

}
