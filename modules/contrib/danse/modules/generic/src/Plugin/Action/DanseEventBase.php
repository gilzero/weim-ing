<?php

namespace Drupal\danse_generic\Plugin\Action;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\danse_generic\Service;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class for DANSE actions.
 */
abstract class DanseEventBase extends ActionBase implements ConfigurableInterface, ContainerFactoryPluginInterface, PluginFormInterface {

  /**
   * The service class for this module.
   *
   * @var \Drupal\danse_generic\Service
   */
  protected Service $service;

  /**
   * Provides the data for the generic DANSE event.
   *
   * @param mixed|null $entity
   *   The entity, if available.
   *
   * @return mixed
   *   The data.
   */
  abstract protected function getData(mixed $entity = NULL): mixed;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): DanseEventBase {
    // @phpstan-ignore-next-line
    $plugin = new static($configuration, $plugin_id, $plugin_definition);
    $plugin->service = $container->get('danse_generic');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    $this->service->createDanseEvent($this->configuration['topic_id'], $this->configuration['label'], $this->getData($entity));
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'topic_id' => '',
      'label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['topic_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Topic ID'),
      '#default_value' => $this->configuration['topic_id'] ?? '',
      '#description' => $this->t('An ID to identify the type of DANSE event, like a category.'),
      '#weight' => -30,
    ];
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->configuration['label'] ?? '',
      '#description' => $this->t('A label for the DANSE event.'),
      '#weight' => -20,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['topic_id'] = $form_state->getValue('topic_id');
    $this->configuration['label'] = $form_state->getValue('label');
  }

}
