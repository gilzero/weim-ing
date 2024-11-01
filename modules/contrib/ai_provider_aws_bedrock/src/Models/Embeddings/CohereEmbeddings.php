<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Embeddings;

use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiBrokenOutputException;

/**
 * The Cohere Embeddings model.
 */
class CohereEmbeddings {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {
    // cohere.embed-english-v3
    // cohere.embed-multilingual-v3
    // This has to exist for RAG to work, even if its fixed.
    $config['dimensions'] = [
      'type' => 'number',
      'constraints' => [
        'options' => [
          '1024',
        ],
      ],
      'label' => t('Dimensions'),
      'default' => 1024,
      'required' => TRUE,
    ];

    $config['input_type'] = [
      'type' => 'string',
      'constraints' => [
        'options' => [
          'search_document',
          'search_query',
          'classification',
          'clustering',
        ],
      ],
      'label' => t('Input type'),
      'default' => 'search_document',
      'required' => TRUE,
    ];

    $config['truncate'] = [
      'type' => 'string',
      'constraints' => [
        'options' => [
          'NONE',
          'START',
          'END',
        ],
      ],
      'label' => t('Truncate'),
    ];

    $config['embedding_types'] = [
      'type' => 'string',
      'constraints' => [
        'options' => [
          'float',
          'int8',
          'uint8',
          'binary',
          'ubinary',
        ],
      ],
      'label' => t('Embedding Types'),
    ];
  }

  /**
   * Format the input.
   *
   * @param string $input
   *   The input.
   * @param \Drupal\ai\OperationType\GenericType\ImageFile $image
   *   The image.
   * @param array $config
   *   The config.
   * @param string $model_id
   *   The model id.
   *
   * @return array
   *   The body to json encode.
   */
  public static function formatInput($input = "", $image = NULL, $config = [], $model_id = '') {
    // This should actually not be sent as a parameter.
    if (isset($config['dimensions'])) {
      unset($config['dimensions']);
    }

    foreach ($config as $key => $val) {
      if (empty($val)) {
        unset($config[$key]);
      }
    }

    if ($input) {
      $config['texts'] = [
        $input,
      ];
    }
    else {
      throw new AiBadRequestException('You need to give an input text.');
    }

    return $config;
  }

  /**
   * Format the output.
   *
   * @param array $output
   *   The none abstracted output.
   * @param array $config
   *   The config.
   *
   * @return array
   *   The embeddings.
   */
  public static function formatOutput($output, $config = []): array {
    if (empty($output['embeddings'][0])) {
      throw new AiBrokenOutputException('No image data found in the response.');
    }
    return $output['embeddings'][0];
  }

  /**
   * The provider capabilities.
   *
   * @param array $capabilities
   *   The capabilities passed by reference.
   * @param string $model_id
   *   The model id.
   *
   * @return bool
   *   If the model supports the capabilities.
   */
  public static function providerCapabilities($capabilities, $model_id) {
    return TRUE;
  }

}
