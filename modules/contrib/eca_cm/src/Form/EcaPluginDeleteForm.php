<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Entity\Eca;

/**
 * Form for deleting a configured plugin from an ECA config.
 */
abstract class EcaPluginDeleteForm extends EcaPluginForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Eca $eca = NULL, ?PluginInspectionInterface $plugin = NULL, ?string $config_key = NULL, ?array $config_array = NULL) {
    $form = parent::buildForm($form, $form_state, $eca, $plugin, $config_key, $config_array);

    $form['#attributes']['class'][] = 'confirmation';
    $name = $this->configArray['label'] ?? $this->plugin->getPluginDefinition()['label'];
    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h2>' . $this->t('You are about to delete @type "%name" from the ECA configuration %id.', [
        '@type' => $this->getTypeLabel(),
        '%name' => $name,
        '%id' => $this->eca->id(),
      ]) . '</h2>',
    ];
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('This action cannot be undone.') . '</p>',
    ];

    unset($form[$this->type], $form['actions']['delete']);

    $form['actions']['submit']['#value'] = $this->t('Confirm');
    $form['actions']['submit']['#submit'] = ['::delete'];
    $form['actions']['submit']['#button_type'] = 'danger';
    $weight = $form['actions']['submit']['#weight'];
    $weight += 10;
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#attributes' => [
        'class' => ['button'],
      ],
      '#weight' => $weight++,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array &$form, FormStateInterface $form_state): void {
    if (!$this->eca->access('delete')) {
      return;
    }
    $config_arrays = $this->eca->get($this->type . 's');
    $config_array = $config_arrays[$this->configKey];
    unset($config_arrays[$this->configKey]);
    $this->eca->set($this->type . 's', $config_arrays);
    $this->eca->save();
    $name = $config_array['label'] ?? $this->plugin->getPluginDefinition()['label'];
    $this->messenger->addStatus($this->t('The @type "%name" has been successfully removed.', [
      '@type' => $this->getTypeLabel(),
      '%name' => $name,
    ]));
    $form_state->setRedirect("entity.eca.edit_form", [
      'eca' => $this->eca->id(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->eca->access('delete')) {
      $form_state->setError($form, $this->t('You don\'t have permission to manage this configuration.'));
    }
  }

}
