<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Chat;

use Drupal\ai\Enum\AiModelCapability;

/**
 * The Titan Chat model.
 */
class TitanChat {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {

    $config['maxTokenCount'] = $config['max_tokens'];
    unset($config['max_tokens']);
    switch ($model_id) {
      case strpos($model_id, 'amazon.titan-text-express') === 0:
      case 'amazon.titan-tg1-large':
        $config['maxTokenCount']['constraints']['max'] = 8192;
        $config['maxTokenCount']['default'] = 4096;
        break;

      case strpos($model_id, 'amazon.titan-text-lite') === 0:
        $config['maxTokenCount']['constraints']['max'] = 4096;
        $config['maxTokenCount']['default'] = 2048;
        break;

      case strpos($model_id, 'amazon.titan-text-premier') === 0:
        $config['maxTokenCount']['constraints']['max'] = 3072;
        $config['maxTokenCount']['default'] = 1024;
        break;
    }

    $config['topP'] = [
      'type' => 'float',
      'label' => t('Top P'),
      'description' => t('The probability of sampling from the model.'),
      'default' => 0.7,
      'constraints' => [
        'min' => 0.1,
        'max' => 1.0,
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
      // If it's premier, then TRUE, otherwise FALSE.
      return strpos($model_id, 'premier');
    }
    return TRUE;
  }

}
