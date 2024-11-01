<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for setting types with tokens.
 */
abstract class TokenSettingTypeBase extends PatternSettingTypeBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $plugin->setTokenService($container->get('token'));
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $value, PatternDefinitionSetting $def, $form_type) {
    $def = $this->getPatternSettingDefinition();
    $value = $this->getValue($value);
    $description = $def->getDescription() != NULL ? $def->getDescription() : "";

    $form[$def->getName()] = [
      '#type' => 'container',
    ];

    $form[$def->getName()]['input'] = [
      '#type' => 'textfield',
      '#title' => $def->getLabel(),
      '#description' => $description,
      '#default_value' => $this->getValue($value),
      '#attributes' => ['class' => ['js-ui-patterns-settings-show-token-link']],
    ];
    $this->handleInput($form[$def->getName()]['input'], $def, $form_type);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess($value, array $context, PatternDefinitionSetting $def) {
    $return_value = '';

    if (is_array($value) && isset($value['input'])) {
      $value = $value['input'];
    }

    if (is_string($value)) {
      $token_data = [];
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $context['entity'] ?? NULL;
      if ($entity !== NULL) {
        $token_data[$entity->getEntityTypeId()] = $entity;
      }
      try {
        $return_value = $this->getTokenService()->replace($value, $token_data, ['clear' => TRUE]);
      }
      catch (EntityMalformedException $e) {
        if (!ui_patterns_settings_is_layout_builder_route()) {
          throw $e;
        }
        // Do nothing inside layout builder.
      }
    }

    return $return_value;
  }

  /**
   * Sets the token service.
   *
   * @param \Drupal\Core\Utility\Token $token_service
   *   The token service.
   *
   * @return self
   *   The token service.
   */
  public function setTokenService(Token $token_service) {
    $this->tokenService = $token_service;
    return $this;
  }

  /**
   * Gets the token service.
   *
   * @return \Drupal\Core\Utility\Token
   *   The token service.
   */
  public function getTokenService() {
    if (!$this->tokenService) {
      $this->tokenService = \Drupal::token();
    }

    return $this->tokenService;
  }

}
