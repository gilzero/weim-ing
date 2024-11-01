<?php

namespace Drupal\social_auth_amazon\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Drupal\social_auth\Plugin\Network\NetworkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth Amazon.
 */
class AmazonAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected RequestContext $requestContext;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $instance = parent::create($container);
    $instance->requestContext = $container->get('router.request_context');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'social_auth_amazon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_amazon.settings']
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NetworkInterface $network = NULL): array {
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance('social_auth_amazon');
    $form = parent::buildForm($form, $form_state, $network);
    $form['network']['#description'] = $this->t('You need to first create an Amazon app at <a href="@amazon-dev">@amazon-dev</a> by signing in and clicking on "Register New Application"',
      ['@amazon-dev' => 'https://login.amazon.com/manageApps']
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Convert the string of space-separated scopes into an array.
    $scopes = explode(" ", $form_state->getValue('scopes') ?? '');

    // Define the list of valid scopes.
    $valid_scopes = ['', 'profile', 'profile:user_id', 'postal_code'];

    // Check if input contains any invalid scopes.
    for ($i = 0; $i < count($scopes); $i++) {
      if (!in_array($scopes[$i], $valid_scopes, TRUE)) {
        $contains_invalid_scope = TRUE;
      }
    }
    if (isset($contains_invalid_scope)) {
      $form_state->setErrorByName('scope', $this->t('You have entered an invalid scope. Please check and try again.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config('social_auth_amazon.settings')
      ->set('client_id', trim($values['client_id']))
      ->set('client_secret', trim($values['client_secret']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
