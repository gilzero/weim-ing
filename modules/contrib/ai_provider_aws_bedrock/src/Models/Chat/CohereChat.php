<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Chat;

use Drupal\ai\Enum\AiModelCapability;

/**
 * The Cohere Chat model.
 */
class CohereChat {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {
    // cohere.command-text
    // cohere.command-light.
    $config['max_tokens']['constraints']['max'] = 4096;
    $config['max_tokens']['default'] = 200;

    $config['temperature']['default'] = 0.9;
    $config['temperature']['constraints']['max'] = 5;

    $config['p'] = [
      'type' => 'float',
      'label' => t('Top P'),
      'default' => 0.75,
      'constraints' => [
        'min' => 0.1,
        'max' => 1.0,
        'step' => 0.001,
      ],
    ];

    if (strpos($model_id, 'cohere.command-p') == 0) {
      $config['temperature']['default'] = 0.5;
      $config['temperature']['constraints']['max'] = 1;
    }

    $config['p'] = [
      'type' => 'number',
      'label' => t('Top P'),
      'default' => 0,
      'constraints' => [
        'min' => 0,
        'max' => 500,
      ],
    ];

    $config['k'] = [
      'type' => 'number',
      'label' => t('Top P'),
      'default' => 0,
      'constraints' => [
        'min' => 0,
        'max' => 500,
      ],
    ];

    if (strpos($model_id, 'cohere.command-p') !== 0) {
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
    }
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
    if (in_array(AiModelCapability::ChatWithImageVision, $capabilities)) {
      return FALSE;
    }
    if (in_array(AiModelCapability::ChatJsonOutput, $capabilities)) {
      // If its light, then FALSE, otherwise TRUE.
      return strpos($model_id, 'cohere.command-light') === FALSE;
    }
    return TRUE;
  }

}
