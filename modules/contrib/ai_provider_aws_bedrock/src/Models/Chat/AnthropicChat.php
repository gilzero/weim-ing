<?php

namespace Drupal\ai_provider_aws_bedrock\Models\Chat;

use Drupal\ai\Enum\AiModelCapability;

/**
 * The Anthropic Chat model.
 */
class AnthropicChat {

  /**
   * The provider config.
   *
   * @param array $config
   *   The config passed by reference.
   * @param string $model_id
   *   The model id.
   */
  public static function providerConfig(&$config, $model_id) {

    $config['max_tokens']['default'] = 1024;
    $config['temperature']['default'] = 0;

    $config['top_p'] = [
      'type' => 'float',
      'label' => t('Top P'),
      'description' => t('In nucleus sampling, Anthropic Claude computes the cumulative distribution over all the options for each subsequent token in decreasing probability order and cuts it off once it reaches a particular probability specified by top_p. You should alter either temperature or top_p, but not both.'),
      'default' => 0.999,
      'constraints' => [
        'min' => 0.1,
        'max' => 1.0,
        'step' => 0.001,
      ],
    ];

    $config['top_k'] = [
      'type' => 'number',
      'label' => t('Top K'),
      'description' => t('Only sample from the top K options for each subsequent token'),
      'constraints' => [
        'min' => 0,
        'max' => 500,
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
      // Return TRUE if the model has claude-3 in its id.
      return strpos($model_id, 'claude-3') !== FALSE;
    }
    if (in_array(AiModelCapability::ChatJsonOutput, $capabilities)) {
      // Return TRUE if the model has claude-3 in its id.
      return strpos($model_id, 'claude-3') !== FALSE;
    }
    return TRUE;
  }

}
