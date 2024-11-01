<?php

namespace Drupal\unstructured\Plugin\AiAutomatorType;

use Drupal\ai_automator\PluginBaseClasses\ExternalBase;
use Drupal\ai_automator\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\unstructured\Formatters\HtmlFormatter;
use Drupal\unstructured\Formatters\MarkdownFormatter;
use Drupal\unstructured\Formatters\TextFormatter;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\unstructured\UnstructuredApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileToTextBase extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

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
  public function allowedInputs() {
    return [
      'file',
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function extraAdvancedFormFields(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, FormStateInterface $formState, array $defaultValues = []) {
    $form['automator_unstructured_elements'] = [
      '#type' => 'checkboxes',
      '#title' => 'Elements',
      '#options' => [
        'FigureCaption' => $this->t('Figure Caption'),
        'NarrativeText' => $this->t('Narrative Text'),
        'ListItem' => $this->t('List Item'),
        'Title' => $this->t('Title'),
        'Address' => $this->t('Address'),
        'PageBreak' => $this->t('Page Break'),
        'Header' => $this->t('Header'),
        'Footer' => $this->t('Footer'),
        'UncategorizedText' => $this->t('Uncategorized Text'),
        'Image' => $this->t('Image'),
        'Formula' => $this->t('Formula'),
      ],
      '#description' => $this->t('Choose the elements to extract. If nothing is chosen, all elements will be extracted.'),
      '#default_value' => $defaultValues['automator_unstructured_elements'] ?? [],
    ];

    $form['automator_unstructured_output_format'] = [
      '#type' => 'select',
      '#title' => 'Output Format',
      '#options' => [
        'text' => $this->t('Pure Text'),
        'markdown' => $this->t('Markdown'),
        'html' => $this->t('HTML'),
      ],
      '#description' => $this->t('The format of the output.'),
      '#default_value' => $defaultValues['automator_unstructured_output_format'] ?? 'text',
    ];

    $form['automator_unstructured_split'] = [
      '#type' => 'select',
      '#options' => [
        'none' => 'No Split',
        'page' => 'Page Split',
        'element' => 'Element Split',
      ],
      '#title' => 'Split in multiple fields',
      '#description' => $this->t('If you want to split up the result in multiple fields.'),
      '#default_value' => $defaultValues['automator_unstructured_split'] ?? 'none',
    ];

    $form['automator_unstructured_strategy'] = [
      '#type' => 'select',
      '#title' => 'Strategy',
      '#options' => [
        'auto' => $this->t('Auto'),
        'fast' => $this->t('Fast'),
        'hi_res' => $this->t('Hi-Res'),
        'ocr_only' => $this->t('OCR Only'),
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

    $form['automator_unstructured_images'] = [
      '#type' => 'checkbox',
      '#title' => 'Extract Images',
      '#description' => $this->t('Check this to extract images from PDF/image files as well.'),
      '#default_value' => $defaultValues['automator_unstructured_images'] ?? TRUE,
    ];

    $form['automator_unstructured_bypass_unstructured'] = [
      '#type' => 'checkbox',
      '#title' => 'Bypass on perfect match',
      '#description' => $this->t('This will bypass an API call if the input file is a text and the output is text, input file is md and the output is markdown or input file is html and the output is html.'),
      '#default_value' => $defaultValues['automator_unstructured_bypass_unstructured'] ?? TRUE,
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
        // Check if bypass is on.
        if ($automatorConfig['unstructured_bypass_unstructured']) {
          $fileType = $fileEntity->getMimeType();
          $outputType = $automatorConfig['unstructured_output_format'];
          if (
            ($fileType === 'text/plain' && $outputType === 'text') ||
            ($fileType === 'text/markdown' && $outputType === 'markdown') ||
            ($fileType === 'text/html' && $outputType === 'html')
          ) {
            $values[] = file_get_contents($fileEntity->getFileUri());
            continue;
          }
        }
        $extract = [];
        if ($automatorConfig['unstructured_images']) {
          $extract = [
            'extract_image_block_types' => [
              'Image',
              'Table',
            ],
          ];
        }
        if ($automatorConfig['unstructured_strategy'] !== 'auto') {
          $extract['strategy'] = $automatorConfig['unstructured_strategy'];
        }
        if ($automatorConfig['unstructured_hires_model'] !== 'default') {
          $extract['hi_res_model_name'] = $automatorConfig['unstructured_hires_model'];
        }
        $response = $this->unstructuredApi->structure($fileEntity, $extract);

        switch ($automatorConfig['unstructured_output_format']) {
          case 'markdown':
            $formatter = new MarkdownFormatter();
            break;

          case 'html':
            $formatter = new HtmlFormatter();
            break;

          default:
            $formatter = new TextFormatter();
            break;
        }
        $merge = $formatter->format($response, $automatorConfig['unstructured_split']);
        $values = array_merge($values, $merge);
      }
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function verifyValue(ContentEntityInterface $entity, $value, FieldDefinitionInterface $fieldDefinition, $automatorConfig) {
    // Should be a string.
    if (!is_string($value)) {
      return FALSE;
    }
    // Otherwise it is ok.
    return TRUE;
  }

}
