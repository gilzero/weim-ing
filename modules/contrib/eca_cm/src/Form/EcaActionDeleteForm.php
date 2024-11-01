<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for deleting an ECA action.
 */
class EcaActionDeleteForm extends EcaPluginDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_action_delete';
  }

  /**
   * {@inheritdoc}
   */
  protected string $type = 'action';

  /**
   * {@inheritdoc}
   */
  protected function getTypeLabel(): MarkupInterface {
    return $this->t('Action');
  }

}
