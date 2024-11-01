<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for configuring an ECA condition plugin.
 */
class EcaConditionForm extends EcaPluginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_condition';
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
