<?php

namespace Drupal\saml_sp\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Entity\EntityDeleteForm;

/**
 * Provides the user interface for deleting an IdP.
 */
class IdpDeleteForm extends EntityDeleteForm {
  /**
   * The IdP to delete.
   *
   * @var array
   */
  protected $idp;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saml_sp_idp_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the Identity Provider %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // This needs to be a valid route otherwise the cancel link won't appear.
    return new Url('entity.idp.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Nevermind');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();
    \Drupal::messenger()->addMessage($this->t('Identity Provider %label has been deleted.', [
      '%label' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
