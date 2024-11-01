<?php

declare(strict_types=1);

namespace Drupal\pf_notifications\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\pf_notifications\Ajax\ResetCommand;
use Drupal\pf_notifications\Service\BaseInterface;
use Drupal\pf_notifications\Service\KeysManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remove VAPID keys confirmation modal.
 */
class ResetSubscriptionsForm extends ConfirmFormBase {

  /**
   * Theme handler service.
   *
   * @var \Drupal\pf_notifications\Service\KeysManagerInterface
   */
  protected KeysManagerInterface $keysManager;

  /**
   * Push notifications service.
   *
   * @var \Drupal\pf_notifications\Service\BaseInterface
   */
  protected BaseInterface $service;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->keysManager = $container->get('pf_notifications.keys_manager');
    $instance->service = $container->get('pf_notifications.base');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'pf_notifications_reset_keys';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to remove VAPID keys?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('pf_notifications.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): MarkupInterface {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): MarkupInterface {
    return $this->t('All notifications subscriptions will be removed for all users and they would need to subscribe again.');
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = [
      'callback' => '::ajaxResetKeys',
      'progress' => [
        'type' => 'throbber',
        'message' => $this->t('Clearing existing subscriptions...'),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRebuild();
  }

  /**
   * Reset keys ajax callback.
   *
   * @param array<string, string|array<string, mixed>> $form
   *   This form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object for this form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with a few commands executed.
   */
  public function ajaxResetKeys(array &$form, FormStateInterface $form_state): AjaxResponse {
    // WARNING: This will remove all subscriptions for all users.
    $this->clearKeys();
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    $query = [
      'pf-reset' => 1,
      'destination' => Url::fromRoute('<current>', [], ['absolute' => TRUE])->toString(),
    ];

    $redirect_url = Url::fromRoute('pf_notifications.settings', [], [
      'absolute' => TRUE,
      'query' => $query,
    ])->toString();

    // Clear some cache.
    $this->service->invalidateCacheTags();
    $this->service->invalidateCacheTags('danse');

    $response->addCommand(new ResetCommand('', 'pf_notifications_reset', [
      'redirect' => $redirect_url,
    ]));
    return $response;
  }

  /**
   * Delete VAPID keys.
   */
  private function clearKeys(): void {

    try {

      // Delete all subscription data from DANSE array in users_data table.
      // @todo Make this runs in a batch.
      $this->service->deleteAll();

      $this->keysManager->deleteKey('vapid_public');
      $this->keysManager->deleteKey('vapid_private');
      $this->messenger()
        ->addStatus($this->t('VAPID keys and subscriptions cleared and all the existing subscriptions deleted. You may generate a new pair of keys now.'));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('@error', [
        '@error' => $e->getMessage(),
      ]));
    }

  }

}
