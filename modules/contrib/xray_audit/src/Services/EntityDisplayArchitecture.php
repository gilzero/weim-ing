<?php

namespace Drupal\xray_audit\Services;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Retrieve data from entities about display modes.
 */
class EntityDisplayArchitecture implements EntityDisplayArchitectureInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\xray_audit\Services\EntityArchitectureInterface
   */
  protected $entityArchitectureService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $rendererService;

  /**
   * List of computed fields.
   *
   * @var mixed[]
   */
  protected $computed = [];

  /**
   * List of displays.
   *
   * @var mixed[]
   */
  protected $displays = [];

  /**
   * Construct service Extractor Display Modes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Service Entity Type Manager.
   * @param \Drupal\xray_audit\Services\EntityArchitectureInterface $entityArchitectureService
   *   Service Entity Architecture.
   * @param \Drupal\Core\Render\RendererInterface $rendererService
   *   Drupal renderer service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityArchitectureInterface $entityArchitectureService, RendererInterface $rendererService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityArchitectureService = $entityArchitectureService;
    $this->rendererService = $rendererService;
  }

  /**
   * {@inheritdoc}
   */
  public function getData(string $entity_type, string $entity): array {
    $data = [];
    $bundles_data = [];
    $type_entities = $this->entityTypeManager->getStorage($entity_type)->loadMultiple();
    foreach ($type_entities as $type_entity) {
      $data_item = $type_entity->id() ?? '';
      $data_item = (string) $data_item;
      $bundles_data[$type_entity->id()] = [
        'entity' => $entity,
        'bundle' => $type_entity->id(),
        'bundle_label' => $type_entity->label(),
        'data' => $this->entityArchitectureService->getDataForViewDisplayArchitecture($entity, $data_item),
      ];
    }
    $data = array_merge($data, $this->preprocessSummaries($bundles_data));
    $data['main_table'] = $this->preprocessMainTable($bundles_data);
    return $data;
  }

  /**
   * Preprocess the data for summary tables.
   *
   * @param array $bundles_data
   *   Data about field entities and displays.
   *
   * @return array[]
   *   Data preprocessed.
   */
  protected function preprocessSummaries(array $bundles_data) {
    // bundle, display, counts.
    $summary_bundle_display = [];

    // Display bundles, counts.
    $summary_display_bundles = [];

    foreach ($bundles_data as $bundle_data) {
      if (empty($bundle_data['data']['displays'])) {
        continue;
      }
      $displays = array_keys($bundle_data['data']['displays']);
      $summary_bundle_display[$bundle_data['bundle']] = [
        'label' => $bundle_data['bundle_label'],
        'displays' => implode(', ', $displays),
        'count' => count($displays),
      ];
      foreach ($displays as $display) {
        if (!isset($summary_display_bundles[$display])) {
          $summary_display_bundles[$display] = [
            'display' => $display,
            'bundles' => $bundle_data['bundle'],
            'count' => 0,
          ];
          continue;
        }
        $summary_display_bundles[$display]['bundles'] .= ', ' . $bundle_data['bundle'];
        $summary_display_bundles[$display]['count']++;
      }

    }
    return [
      'summary_bundle_display' => $summary_bundle_display,
      'summary_display_bundles' => $summary_display_bundles,
    ];

  }

  /**
   * Preprocess the data for main table.
   *
   * @param array $bundles_data
   *   Data about field entities and displays.
   *
   * @return mixed[]
   *   Data for main table.
   */
  protected function preprocessMainTable(array $bundles_data) {
    $rows = [];
    foreach ($bundles_data as $bundle_data) {
      $rows[] = $this->mainTableRow($bundle_data);
    }
    $headers = array_merge(['label' => 'Type', 'fields' => 'Fields'], $this->displays);
    return [
      'headers' => $headers,
      'rows' => $rows,
      'computed' => implode(', ', $this->computed),
    ];

  }

  /**
   * Build the rows for the main table.
   *
   * @param array $bundle_data
   *   Data about field entities and displays.
   *
   * @return mixed[]
   *   Data rows.
   */
  protected function mainTableRow(array $bundle_data) {
    $row = [];
    // Set columns.
    $row['label'] = new FormattableMarkup("<p>@bundle</p>", ['@bundle' => $bundle_data['bundle_label']]);
    $row['fields'] = $this->mainTableFieldColumn($bundle_data['data']['fields']);
    if (!isset($bundle_data['data']['displays'])) {
      return $row;
    }
    foreach ($bundle_data['data']['displays'] as $display_id => $display) {
      if (!isset($this->displays[$display_id])) {
        $display_label = str_replace('_', ' ', $display_id);
        $display_label = ucfirst($display_label);
        $this->displays[$display_id] = $display_label;
      }
      $popup_label = $bundle_data['bundle_label'] . ' (' . $bundle_data['bundle'] . ') - ' . $this->displays[$display['mode']];
      $row[$display_id] = $this->mainTableCellForDisplays($display, $bundle_data['data']['fields'], $popup_label);
    }
    return $row;

  }

  /**
   * Preprocess the data for field column.
   *
   * @param array $fields
   *   Field data.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Cell in Formattable Markup.
   */
  protected function mainTableFieldColumn(array $fields) {
    $data_paragraphs = [];
    foreach ($fields as $field) {
      if ($this->excludeField($field)) {
        continue;
      }
      $data_paragraphs[] = [
        'selector_id' => $this->buildFieldSelector($field['content']['entity'], $field['content']['bundle'], $field['content']['machine_name']),
        'first_text' => $field['content']['label'] . ' (' . $field['content']['machine_name'] . '): ',
        'second_text' => $field['content']['data_type_settings'],
      ];
    }
    return $this->mainTableCellForFields($data_paragraphs);

  }

  /**
   * Preprocess the data for display columns.
   *
   * @param array $display
   *   Display.
   * @param array $fields
   *   Field data.
   * @param string $popup_label
   *   Text for the popup header.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Cell in Formattable Markup.
   */
  protected function mainTableCellForDisplays(array $display, array $fields, string $popup_label) {
    $field = reset($fields);
    $entity = $field['entity'];
    $bundle = $field['bundle'];
    $data_paragraphs = [];
    $example_link = '';

    foreach ($display['processed'] as $display_field) {
      $machine_name = $display_field['field_id'];

      if (!isset($fields[$machine_name])) {
        $this->computed[$machine_name] = $machine_name;
        $firs_text = "$machine_name (computed)";
      }
      else {
        $firs_text = "{$fields[$machine_name]['label']} ({$machine_name})";
      }

      $data_paragraphs[] = [
        'selector_id' => $this->buildFieldSelector($entity, $bundle, $machine_name),
        'first_text' => $firs_text,
        'second_text' => $display_field['settings'],
      ];
    }

    if (!empty($data_paragraphs)) {
      $example_link = $this->getExampleLink($entity, $bundle, $popup_label, $display['mode']);
    }
    return $this->mainTableCellForFields($data_paragraphs, $example_link);

  }

  /**
   * Build a Markup object for a cell.
   *
   * @param array $items
   *   Items (selector_id, first_text, second_text).
   * @param string $example_link
   *   Link to an example entity.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   Cell in Formattable Markup.
   */
  protected function mainTableCellForFields(array $items, string $example_link = '') {
    $paragraphs = '';
    $place_holders = [];
    $count = 0;
    foreach ($items as $item) {
      $ph_selector_id = "@ph_selector_id_{$count}";
      $ph_first_txt = "@ph_first_txt_{$count}";
      $ph_second_txt = "@ph_second_txt_{$count}";

      $paragraphs .= "<p data-highlight-target=\"{$ph_selector_id}\" class=\"xray-audit__field {$ph_selector_id}\"><b>{$ph_first_txt}</b>";
      if (!empty($item['second_text'])) {
        $paragraphs .= " {$ph_second_txt}";
        $place_holders[$ph_second_txt] = $item['second_text'];
      }
      $paragraphs .= '<p>';

      $place_holders[$ph_selector_id] = $item['selector_id'];
      $place_holders[$ph_first_txt] = $item['first_text'];

      $count++;
    }
    $paragraphs .= $example_link;

    return new FormattableMarkup($paragraphs, $place_holders);

  }

  /**
   * Build a selector id for a field.
   *
   * @param string $entity
   *   Entity type.
   * @param string $bundle
   *   Bundle.
   * @param string $field_name
   *   Field name.
   *
   * @return string
   *   Selector id.
   */
  protected function buildFieldSelector(string $entity, string $bundle, string $field_name) : string {
    return $entity . '_' . $bundle . '__' . $field_name;

  }

  /**
   * Check if the field should be excluded from report display.
   *
   * @param array $field
   *   Field data.
   *
   * @return bool
   *   TRUE if the field should be excluded.
   */
  protected function excludeField(array $field) {
    // Check that the fields can be showed in the front.
    if ($field['is_view_display_configurable'] === TRUE) {
      return FALSE;
    }

    if ($field['view_display_options'] === NULL) {
      return TRUE;
    }

    if (isset($field['view_display_options']['region']) && $field['view_display_options']['region'] === 'hidden') {
      return TRUE;
    }

    return FALSE;

  }

  /**
   * Provide a link to a random item according to the given parameters.
   *
   * @param string $entity_type
   *   Entity name.
   * @param string $entity_bundle
   *   Entity type name.
   * @param string $popup_label
   *   Text for the popup header.
   * @param string $display_mode
   *   Display mode string.
   *
   * @return string
   *   HTML string with the link.
   */
  protected function getExampleLink(string $entity_type, string $entity_bundle, string $popup_label, string $display_mode = 'default') {
    $route = 'xray_audit.example_popup';
    $route_options = [
      'entity_type' => $entity_type,
      'entity_bundle' => $entity_bundle,
      'view_mode' => $display_mode,
    ];

    $link = Link::createFromRoute($this->t('See example'), $route, $route_options)->toRenderable();

    if ($entity_type === 'node' && ($display_mode === 'default' || $display_mode === 'full')) {
      $link['#attributes']['target'] = '_blank';
    }
    else {
      $link['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'minHeight' => '300',
          'height' => '650',
          'width' => '75%',
          'title' => $popup_label,
          'draggable' => TRUE,
          'autoResize' => FALSE,
          'dialogClass' => 'xray-audit--popup',
          'classes' => [
            'ui-dialog-content' => 'xray-audit--popup-content',
            'ui-dialog-titlebar' => 'xray-audit--popup-title',
          ],
        ]),
      ];
    }

    return $this->rendererService->render($link);
  }

}
