<?php

declare(strict_types=1);

namespace Drupal\azure_ai_faq_bot\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Azure AI FAQ Bot settings.
 */
final class AzureAIFAQBotForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'azure_ai_faq_bot_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['azure_ai_faq_bot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['direct_line_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Direct Line Secret'),
      '#default_value' => $this->config('azure_ai_faq_bot.settings')->get('direct_line_secret'),
      '#description' => $this->t('To obtain a Direct Line secret, go to the Azure portal and navigate to the Direct Line channel configuration page for your bot in Secret keys.'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('azure_ai_faq_bot.settings')
      ->set('direct_line_secret', $form_state->getValue('direct_line_secret'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
