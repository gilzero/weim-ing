<?php

namespace Drupal\phpmailer_smtp\Plugin\PhpmailerOauth2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for PHPMailer Oauth2 plugins.
 */
abstract class PhpmailerOauth2PluginBase extends PluginBase implements PhpmailerOauth2PluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    $this->checkKeyDefined('name');

    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    $this->checkKeyDefined('id');

    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthOptions() {

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {}

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {}

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {}

}
