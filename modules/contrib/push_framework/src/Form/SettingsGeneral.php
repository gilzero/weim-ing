<?php

namespace Drupal\push_framework\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\push_framework\ChannelPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Push Framework settings.
 */
class SettingsGeneral extends Settings {

  /**
   * The channel plugin manager.
   *
   * @var \Drupal\push_framework\ChannelPluginManager
   */
  protected ChannelPluginManager $channelPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->channelPluginManager = $container->get('push_framework.channel.plugin.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['channel_order'] = [
      '#type' => 'details',
      '#title' => $this->t('Channel Order'),
      '#description' => $this->t('Ascending order which channel should be tried first. Remember, a notification can only be pushed once.'),
      '#open' => TRUE,
      '#weight' => 31,
    ];
    foreach ($this->channelPluginManager->getDefinitions() as $id => $definition) {
      $form['channel_order']['order_' . $id] = [
        '#type' => 'number',
        '#title' => $definition['label']->getUntranslatedString(),
        '#default_value' => !$this->pluginConfig->get('order_' . $id) ? 1 : $this->pluginConfig->get('order_' . $id),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    foreach ($this->channelPluginManager->getDefinitions() as $id => $definition) {
      $this->pluginConfig
        ->set('order_' . $id, $form_state->getValue('order_' . $id));
    }
    parent::submitForm($form, $form_state);
  }

}
