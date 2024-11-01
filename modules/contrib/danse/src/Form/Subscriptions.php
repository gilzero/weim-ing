<?php

namespace Drupal\danse\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\danse\Service;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Let users to subscribe to available events.
 */
final class Subscriptions extends FormBase {

  /**
   * The DANSE configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * The DANSE service.
   *
   * @var \Drupal\danse\Service
   */
  protected Service $danse;

  /**
   * Subscriptions constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\danse\Service $danse
   *   The DANSE service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Service $danse) {
    $this->config = $config_factory->get('danse.settings');
    $this->danse = $danse;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Subscriptions {
    return new static(
      $container->get('config.factory'),
      $container->get('danse.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'danse_user_subscriptions';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL): array {
    if ($user) {
      $this->danse->buildUserSubscriptionForm($form, $user, FALSE);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Nothing to do, submission is handled in the service.
  }

}
