<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for deleting an ECA condition.
 */
class EcaConditionDeleteForm extends EcaPluginDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_condition_delete';
  }

  /**
   * {@inheritdoc}
   */
  protected string $type = 'condition';

  /**
   * {@inheritdoc}
   */
  protected function getTypeLabel(): MarkupInterface {
    return $this->t('Condition');
  }

}
