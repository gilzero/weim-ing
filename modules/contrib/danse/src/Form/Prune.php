<?php

namespace Drupal\danse\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\danse\Cron;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Enable or disable emergency mode on devices.
 */
class Prune extends ConfirmFormBase {

  /**
   * The danse cron service.
   *
   * @var \Drupal\danse\Cron
   */
  protected Cron $cronService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->cronService = $container->get('danse.cron');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  final public function getFormId(): string {
    return 'danse_prune';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('danse.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to prune DANSE records according to your settings?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $batch = [
      'title' => t('Pruning DANSE records...'),
      'operations' => [],
    ];
    $this->cronService->prune($batch);
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
