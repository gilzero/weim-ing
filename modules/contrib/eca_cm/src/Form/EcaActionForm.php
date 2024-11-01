<?php

namespace Drupal\eca_cm\Form;

use Drupal\Component\Render\MarkupInterface;

/**
 * Form for configuring an ECA action plugin.
 */
class EcaActionForm extends EcaPluginForm {

  /**
   * {@inheritdoc}
   */
  protected static array $skipValidation = [
    'action_send_email_action',
    'node_assign_owner_action',
    'eca_tamper:find_replace_regex',
    'eca_tamper:keyword_filter',
    'eca_tamper:math',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eca_cm_action';
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
