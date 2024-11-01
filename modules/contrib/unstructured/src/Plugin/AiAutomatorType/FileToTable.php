<?php

namespace Drupal\unstructured\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\ai_automator\PluginBaseClasses\ExternalBase;
use Drupal\ai_automator\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\unstructured\UnstructuredApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for a tablefield field.
 */
#[AiAutomatorType(
  id: 'unstructured_io_tablefield',
  label: new TranslatableMarkup('Unstructured API: File to Table'),
  field_rule: 'tablefield',
  target: '',
)]
class FileToTable extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The Unstructured API.
   */
  public UnstructuredApi $unstructuredApi;

  /**
   * The entity type manager.
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * Construct an image field.
   *
   * @param array $configuration
   *   Inherited configuration.
   * @param string $plugin_id
   *   Inherited plugin id.
   * @param mixed $plugin_definition
   *   Inherited plugin definition.
   * @param \Drupal\unstructured\UnstructuredApi $unstructuredApi
   *   The unstructured API.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UnstructuredApi $unstructuredApi,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->unstructuredApi = $unstructuredApi;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('unstructured.api'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public $title = 'Unstructured API: File to Table';

  /**
   * {@inheritDoc}
   */
  public function needsPrompt() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function advancedMode() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function placeholderText() {
    return "";
  }

  /**
   * {@inheritDoc}
   */
  public function checkIfEmpty($value) {
    // If first value is empty.
    if (empty($value[0]['value'][0][0]) && empty($value[1])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function allowedInputs() {
    return [
      'file',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function extraAdvancedFormFields(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, FormStateInterface $formState, array $defaultValues = []) {
    $form['automator_unstructured_strategy'] = [
      '#type' => 'select',
      '#title' => 'Strategy',
      '#options' => [
        'auto' => $this->t('Auto'),
        'fast' => $this->t('Fast'),
        'hi_res' => $this->t('Hi-Res'),
      ],
      '#description' => $this->t('The strategy to use to partition PDF/Image.'),
      '#default_value' => $defaultValues['automator_unstructured_strategy'] ?? 'auto',
    ];

    $form['automator_unstructured_hires_model'] = [
      '#type' => 'select',
      '#title' => 'Hi-Res Model',
      '#options' => [
        'default' => $this->t('Default'),
        'detectron2_onnx' => $this->t('Detectron2 with ONNX'),
        'yolox' => $this->t('YOLOX'),
        'yolox_quantized' => $this->t('YOLOX Quantized'),
        'chipper' => $this->t('Chipper'),
      ],
      '#description' => $this->t('The Hi-Res model to use.'),
      '#default_value' => $defaultValues['automator_unstructured_hires_model'] ?? 'default',
      '#states' => [
        'visible' => [
          ':input[name="automator_unstructured_strategy"]' => ['value' => 'hi_res'],
        ],
      ],
    ];

    return $form;
  }


  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    $values = [];
    foreach ($entity->{$automatorConfig['base_field']} as $entityWrapper) {
      if ($entityWrapper->entity) {
        $fileEntity = $entityWrapper->entity;
        $extract = [
          'extract_image_block_types' => [
            'Table',
          ],
        ];
        if ($automatorConfig['unstructured_strategy'] !== 'auto') {
          $extract['strategy'] = $automatorConfig['unstructured_strategy'];
        }
        if ($automatorConfig['unstructured_hires_model'] !== 'default') {
          $extract['hi_res_model_name'] = $automatorConfig['unstructured_hires_model'];
        }
        $response = $this->unstructuredApi->structure($fileEntity, $extract);

        foreach ($response as $result) {
          if (isset($result['metadata']['text_as_html'])) {
            $values[] = $this->getTableField($result['metadata']['text_as_html'], $result['metadata']['page_name'] ?? '');
          }
        }
      }
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function verifyValue(ContentEntityInterface $entity, $value, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    // Should be a table.
    if (isset($value['value'][0][0])) {
      return TRUE;
    }
    // Otherwise it is ok.
    return FALSE;
  }

  /**
   * Get the table from the HTML.
   *
   * @param string $html
   *   The html.
   * @param string $caption
   *   The caption.
   *
   * @return array
   *   A rendered table field
   */
  public function getTableField($html, $caption = '') {
    $rows = explode('</tr>', $html);
    $longest = [];
    $renderRows = [];
    foreach ($rows as $rowKey => $row) {
      $cols = str_contains($row, '</td>') ? explode('</td>', $row) : explode('</th>', $row);
      foreach ($cols as $key => $col) {
        $value = trim(strip_tags($col));
        if (empty($value)) {
          continue;
        }
        $renderRows[$rowKey][$key] = $value;
        // Get the longest string in the column.
        if (!isset($longest[$key]) || strlen($value) > $longest[$key]) {
          $longest[$key] = strlen($value);
        }
      }
    }
    $table = [
      'caption' => $caption,
      'rebuild' => [
        'cols' => count($renderRows[0]),
        'rows' => count($renderRows),
      ],
      'value' => $renderRows,
    ];
    return $table;
  }

}
