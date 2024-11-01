<?php

namespace Drupal\danse_generic\Plugin\Action;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Action plugin to create a new DANSE event with arbitrary data.
 *
 * @Action(
 *   id = "danse_event_arbitrary",
 *   label = @Translation("New DANSE event with arbitrary data"),
 *   description = @Translation("Creates a new DANSE event with arbitrary data.")
 * )
 */
class DanseEventArbitrary extends DanseEventBase {

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::allowed();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function getData(mixed $entity = NULL): mixed {
    if ($this->configuration['is_yaml']) {
      return Yaml::decode($this->configuration['data']);
    }
    return $this->configuration['data'];
  }

  /**
   * Gets the default configuration.
   *
   * @return array
   *   The default configuration.
   */
  public function defaultConfiguration(): array {
    return [
      'data' => '',
      'is_yaml' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['data'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Data'),
      '#default_value' => $this->configuration['data'] ?? '',
      '#weight' => -10,
    ];
    $form['is_yaml'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Interpret data as YAML'),
      '#default_value' => $this->configuration['is_yaml'] ?? FALSE,
      '#weight' => -5,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['data'] = $form_state->getValue('data');
    $this->configuration['is_yaml'] = $form_state->getValue('is_yaml');
    parent::submitConfigurationForm($form, $form_state);
  }

}
