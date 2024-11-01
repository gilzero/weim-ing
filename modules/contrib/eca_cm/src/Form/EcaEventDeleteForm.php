<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for deleting an ECA event.
 */
class EcaEventDeleteForm extends EcaPluginDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_event_delete';
  }

  /**
   * {@inheritdoc}
   */
  protected string $type = 'event';

  /**
   * {@inheritdoc}
   */
  protected function getTypeLabel(): MarkupInterface {
    return $this->t('Event');
  }

}
