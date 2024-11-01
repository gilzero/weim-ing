<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Embeddings;

use Drupal\ai\Exception\AiBadRequestException;
use Drupal\ai\Exception\AiBrokenOutputException;

/**
 * The Titan embeddings model.
 */
class TitanEmbeddings {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {
    $config['dimensions'] = [
      'type' => 'number',
      'constraints' => [
        'options' => [
          '1024',
          '512',
          '256',
        ],
      ],
      'label' => t('Dimensions'),
      'default' => 1024,
    ];
    // If its image, then its another format.
    if (strpos($model_id, 'amazon.titan-embed-image') === 0) {
      unset($config['dimensions']);
      $config['outputEmbeddingLength'] = [
        'type' => 'number',
        'constraints' => [
          'options' => [
            '1024',
            '384',
            '256',
          ],
        ],
        'label' => t('Output Embedding Length'),
        'default' => 1024,
      ];
    }
    // And if its v1, nothing.
    if (strpos($model_id, 'amazon.titan-embed-text-v1') === 0) {
      unset($config['dimensions']);
    }
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
    if ($input) {
      $payload['inputText'] = $input;
    }
    elseif ($image && strpos($model_id, 'amazon.titan-embed-image') === 0) {
      $payload['inputImage'] = $image->getAsBase64EncodedString('');
    }
    else {
      throw new AiBadRequestException('You need to give an input text or an image or you used as image in a none-image embeddings model.');
    }
    if (strpos($model_id, 'amazon.titan-embed-image') === 0) {
      $payload['embeddingConfig']['outputEmbeddingLength'] = (int) $config['outputEmbeddingLength'];
    }
    elseif (strpos($model_id, 'amazon.titan-embed-text-v1') !== 0) {
      $payload['dimensions'] = (int) $config['dimensions'];
    }
    return $payload;
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
    if (empty($output['embedding'])) {
      throw new AiBrokenOutputException('No image data found in the response.');
    }
    return $output['embedding'];
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
