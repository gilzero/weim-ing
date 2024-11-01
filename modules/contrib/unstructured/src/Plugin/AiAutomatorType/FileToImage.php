<?php

namespace Drupal\unstructured\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\ai_automator\PluginBaseClasses\ExternalBase;
use Drupal\ai_automator\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\file\Entity\File;
use Drupal\file\FileRepositoryInterface;
use Drupal\unstructured\UnstructuredApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for a images field.
 */
#[AiAutomatorType(
  id: 'unstructured_io_images',
  label: new TranslatableMarkup('Unstructured API: File to Image'),
  field_rule: 'image',
  target: 'file',
)]
class FileToImage extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The Unstructured API.
   */
  public UnstructuredApi $unstructuredApi;

  /**
   * The entity type manager.
   */
  public EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file system.
   */
  public FileSystemInterface $fileSystem;

  /**
   * The file repository.
   */
  public FileRepositoryInterface $fileRepository;

  /**
   * The token.
   */
  public Token $token;

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
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\file\FileRepositoryInterface $fileRepository
   *   The file repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    UnstructuredApi $unstructuredApi,
    EntityTypeManagerInterface $entityTypeManager,
    FileSystemInterface $fileSystem,
    FileRepositoryInterface $fileRepository,
    Token $token,
  ) {
    $this->unstructuredApi = $unstructuredApi;
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;
    $this->token = $token;
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
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.repository'),
      $container->get('token')
    );
  }

  /**
   * {@inheritDoc}
   */
  public $title = 'Unstructured API: File to Images';

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
  public function extraAdvancedFormFields(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, FormStateInterface $formState, array $defaulValues = []) {
    $form['automator_unstructured_strategy'] = [
      '#type' => 'select',
      '#title' => 'Strategy',
      '#options' => [
        'auto' => $this->t('Auto'),
        'fast' => $this->t('Fast'),
        'hi_res' => $this->t('Hi-Res'),
      ],
      '#description' => $this->t('The strategy to use to partition PDF/Image.'),
      '#default_value' => $defaulValues['automator_unstructured_strategy'] ?? 'auto',
    ];

    return $form;
  }


  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $interpolatorConfig, $delta = 0) {
    $values = [];
    $config = $fieldDefinition->getConfig($entity->bundle())->getSettings();
    $filePath = $this->token->replace($config['uri_scheme'] . '://' . rtrim($config['file_directory'], '/'));
    foreach ($entity->{$interpolatorConfig['base_field']} as $entityWrapper) {
      if ($entityWrapper->entity) {
        $fileEntity = $entityWrapper->entity;
        $extract = [
          'extract_image_block_types' => [
            'Image',
          ],
        ];
        if ($interpolatorConfig['unstructured_strategy'] !== 'auto') {
          $extract['strategy'] = $interpolatorConfig['unstructured_strategy'];
        }
        if ($interpolatorConfig['unstructured_hires_model'] !== 'default') {
          $extract['hi_res_model_name'] = $interpolatorConfig['unstructured_hires_model'];
        }
        $response = $this->unstructuredApi->structure($fileEntity, $extract);
        foreach ($response as $result) {
          if ($result['type'] == 'Image') {
            $file = $this->generateImageFile($result, $filePath);
            $values[] = [
              'target_id' => $file->id(),
              'alt' => $result['metadata']['filename'],
              'title' => $result['metadata']['filename'],
            ];
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
    // Target id has to exists.
    if (empty($value['target_id'])) {
      return FALSE;
    }
    // Otherwise it is ok.
    return TRUE;
  }

  /**
   * Generate image file from base64.
   *
   * @param array $data
   *   The data to format.
   * @param string $directory
   *   The directory to save the file.
   *
   * @return \Drupal\file\Entity\File
   *   The file entity.
   */
  public function generateImageFile(array $data, $directory): File {
    // Generate a filename.
    $extension = 'jpg';
    switch ($data['metadata']['image_mime_type']) {
      case 'image/png':
        $extension = 'png';
        break;

      case 'image/gif':
        $extension = 'gif';
        break;
    }
    $filename = $data['metadata']['filename'] . '.' . microtime(TRUE) .  '.' . $extension;
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $file = $this->fileRepository->writeData(base64_decode($data['metadata']['image_base64']), $directory . '/' . $filename, FileSystemInterface::EXISTS_RENAME);
    $file->set('status', File::STATUS_PERMANENT);
    $file->save();
    return $file;
  }
}
