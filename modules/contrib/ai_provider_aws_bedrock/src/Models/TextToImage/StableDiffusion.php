<?php

namespace Drupal\ai_provider_aws_bedrock\Models\TextToImage;

use Drupal\ai\Exception\AiBrokenOutputException;
use Drupal\ai\OperationType\GenericType\ImageFile;

/**
 * The Stable Diffusion model.
 */
class StableDiffusion {

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
          '1152x896',
          '1216x832',
          '1536x640',
          '640x1536',
          '832x1216',
          '896x1152',
        ],
      ],
      'label' => t('Resolution'),
    ];

    $config['cfg_scale'] = [
      'type' => 'number',
      'default' => 7,
      'label' => t('CFG Scale'),
      'constraints' => [
        'min' => 0,
        'max' => 35,
      ],
    ];

    $config['steps'] = [
      'type' => 'number',
      'default' => 30,
      'label' => t('Steps'),
      'constraints' => [
        'min' => 10,
        'max' => 150,
      ],
    ];

    $config['style_preset'] = [
      'type' => 'string',
      'constraints' => [
        'options' => [
          '3d-model',
          'analog-film',
          'anime',
          'cinematic',
          'comic-book',
          'digital-art',
          'enhance',
          'fantasy-art',
          'isometric',
          'line-art',
          'low-poly',
          'modeling-compound',
          'neon-punk',
          'origami',
          'photographic',
          'pixel-art',
          'tile-texture',
        ],
      ],
      'label' => t('Style Preset'),
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
      'text_prompts' => [
        ['text' => $input],
      ],
    ];
    foreach ($config as $key => $value) {
      if ($key == 'resolution' && $value) {
        $parts = explode('x', $value);
        $payload['width'] = (int) $parts[0];
        $payload['height'] = (int) $parts[1];
      }
      elseif ($value) {
        $payload[$key] = $key == 'style_preset' ? $value : (int) $value;
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
    if (empty($output['artifacts'][0]['base64'])) {
      throw new AiBrokenOutputException('No image data found in the response.');
    }
    $images = [];
    foreach ($output['artifacts'] as $image) {
      $images[] = new ImageFile(base64_decode($image['base64']), 'image/png', 'bedrock.png');
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
