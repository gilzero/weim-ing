<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Chat;

use Drupal\ai\Enum\AiModelCapability;

/**
 * The Ai21 Chat model.
 */
class Ai21Chat {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {

    $config['maxTokens'] = $config['max_tokens'];
    unset($config['max_tokens']);
    $config['maxTokens']['constraints']['max'] = 8191;
    $config['maxTokens']['default'] = 200;

    $config['topP'] = [
      'type' => 'float',
      'label' => t('Top P'),
      'default' => 0.5,
      'constraints' => [
        'min' => 0.1,
        'max' => 1.0,
        'step' => 0.001,
      ],
    ];
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
      // If its grande, then TRUE, otherwise FALSE.
      return strpos($model_id, 'grande');
    }
    return TRUE;
  }

}
