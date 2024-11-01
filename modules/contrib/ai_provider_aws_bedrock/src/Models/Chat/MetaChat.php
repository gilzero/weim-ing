<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Chat;

use Drupal\ai\Enum\AiModelCapability;

/**
 * The Meta Chat model.
 */
class MetaChat {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {

    $config['max_gen_len'] = $config['max_tokens'];
    unset($config['max_tokens']);
    $config['max_gen_len']['constraints']['max'] = 2048;
    $config['max_gen_len']['default'] = 512;

    $config['temperature']['default'] = 0.5;

    $config['top_p'] = [
      'type' => 'float',
      'label' => t('Top P'),
      'description' => t('The probability of sampling from the model.'),
      'default' => 0.9,
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
      // If its 405b, then TRUE, otherwise FALSE.
      return strpos($model_id, '405b');
    }
    return TRUE;
  }

}
