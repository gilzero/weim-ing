<?php

namespace Drupal\simple_block;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_block\Entity\SimpleBlock;

/**
 * Base form for simple block clone forms.
 */
class SimpleBlockCloneForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\simple_block\SimpleBlockInterface $simple_block */
    $simple_block = $this->getEntity();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => ConfigEntityStorage::MAX_ID_LENGTH,
      '#default_value' => $simple_block->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $simple_block->id() . '_clone',
      '#maxlength' => ConfigEntityStorage::MAX_ID_LENGTH,
      '#machine_name' => [
        'source' => ['title'],
        'exists' => SimpleBlock::class . '::load',
        'label' => $this->t('Internal name'),
      ],
      '#disabled' => FALSE,
      '#title' => $this->t('Internal name'),
      '#description' => $this->t('A unique internal name. Can only contain lowercase letters, numbers, and underscores.'),
      '#required' => TRUE,
    ];
    $form['content'] = [
      '#type' => 'text_format',
      '#format' => $simple_block->getContent()['format'],
      '#title' => $this->t('Content'),
      '#default_value' => $simple_block->getContent()['value'],
      '#required' => TRUE,
      '#description' => $this->t('Global tokens are allowed.'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->entityTypeManager->getStorage('simple_block')->load($form_state->getValue('id'))) {
      $form_state->setErrorByName('id', $this->t('The machine-readable name is already in use. It must be unique.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $simple_block = $this->entityTypeManager->getStorage('simple_block')->create([
      'id' => $form_state->getValue('id'),
      'title' => $form_state->getValue('title'),
      'content' => $form_state->getValue('content'),
    ]);
    $simple_block->save();

    $arguments = ['%id' => $simple_block->id()];
    $this->messenger()->addStatus($this->t('Block %id has been added.', $arguments));

    // Return back to the list page.
    $form_state->setRedirect('entity.simple_block.collection');

    return SAVED_NEW;
  }

}
