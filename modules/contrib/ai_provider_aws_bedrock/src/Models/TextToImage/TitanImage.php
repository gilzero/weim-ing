<?php

namespace Drupal\ai_provider_aws_bedrock\Models\TextToImage;

use Drupal\ai\Exception\AiBrokenOutputException;
use Drupal\ai\OperationType\GenericType\ImageFile;

/**
 * The Titan Image model.
 */
class TitanImage {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   */
  public static function providerConfig(&$config) {
    $config['resolution'] = [
      'type' => 'string',
      'constraints' => [
        'options' => [
          '1024x1024',
          '768x768',
          '512x512',
          '768x1152',
          '384x576',
          '1152x768',
          '576x384',
          '768x1280',
          '384x640',
          '1280x768',
          '640x384',
          '896x1152',
          '448x576',
          '1152x896',
          '576x448',
          '768x1408',
          '384x704',
          '1408x768',
          '704x384',
          '640x1408',
          '320x704',
          '1408x640',
          '704x320',
          '1152x640',
          '1173x640',
        ],
      ],
      'label' => t('Resolution'),
    ];

    $config['numberOfImages'] = [
      'type' => 'number',
      'default' => 1,
      'label' => t('Number of Images'),
      'constraints' => [
        'min' => 1,
        'max' => 5,
      ],
    ];

    $config['cfgScale'] = [
      'type' => 'float',
      'default' => 8.0,
      'label' => t('CFG Scale'),
      'constraints' => [
        'min' => 1.1,
        'max' => 10,
      ],
    ];
  }

  /**
   * Format the input.
   *
   * @param string $input
   *   The input.
   * @param array $config
   *   The config.
   *
   * @return array
   *   The body to json encode.
   */
  public static function formatInput($input, $config) {
    $payload = [
      'taskType' => 'TEXT_IMAGE',
      'textToImageParams' => [
        'text' => $input,
      ],
    ];
    foreach ($config as $key => $value) {
      if ($key == 'resolution' && $value) {
        $parts = explode('x', $value);
        $payload['imageGenerationConfig']['width'] = (int) $parts[0];
        $payload['imageGenerationConfig']['height'] = (int) $parts[1];
      }
      elseif ($value) {
        $payload['imageGenerationConfig'][$key] = (int) $value;
      }
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
   * @return \Drupal\ai\OperationType\GenericType\ImageFile[]
   *   The text to image output.
   */
  public static function formatOutput($output, $config = []): array {
    $images = [];
    if (empty($output['images'][0])) {
      throw new AiBrokenOutputException('No image data found in the response.');
    }
    foreach ($output['images'] as $data) {
      $images[] = new ImageFile(base64_decode($data), 'image/png', 'dalle.png');
    }
    return $images;
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
