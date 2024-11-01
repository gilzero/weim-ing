<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for configuring an ECA event plugin.
 */
class EcaEventForm extends EcaPluginForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_event';
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
