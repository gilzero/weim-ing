<?php

namespace Drupal\group\Form;

use Drupal\group\Entity\Form\GroupRelationshipDeleteForm;

/**
 * Provides a form for leaving a group.
 */
class GroupLeaveForm extends GroupRelationshipDeleteForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\group\Entity\GroupRelationshipInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $message = 'Are you sure you want to leave %group?';
    $replace = ['%group' => $this->entity->getGroup()->label()];
    return $this->t($message, $replace);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Leave group');
  }

}
