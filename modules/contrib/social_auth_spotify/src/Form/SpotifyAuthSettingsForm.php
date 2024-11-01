<?php

namespace Drupal\social_auth_spotify\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Drupal\social_auth\Plugin\Network\NetworkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth Spotify.
 */
class SpotifyAuthSettingsForm extends SocialAuthSettingsForm {

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
    return 'social_auth_spotify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_spotify.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?NetworkInterface $network = NULL): array {
    /** @var \Drupal\social_auth\Plugin\Network\NetworkInterface $network */
    $network = $this->networkManager->createInstance('social_auth_spotify');
    $form = parent::buildForm($form, $form_state, $network);

    $config = $this->config('social_auth_spotify.settings');

    $form['network']['#description'] =
      $this->t(
        'You need to first create a Spotify App at <a href="@spotify-dev">@spotify-dev</a>',
        ['@spotify-dev' => 'https://developer.spotify.com/dashboard']
      );

    $form['network']['api_version'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Spotify API version'),
      '#default_value' => $config->get('api_version'),
      '#description' => $this->t('Enter the Spotify API version here. This is likely just 1.'),
    ];

    $form['network']['advanced']['#weight'] = 999;

    $form['network']['advanced']['scopes']['#description'] =
      $this->t(
      'Define any additional scopes to be requested, separated by a comma (e.g.: user_birthday,user_location).<br>
                The scopes \'user-read-email\' and \'user-read-private\' are added by default and always requested.<br>
                You can see the full list of valid scopes and their description <a href="@scopes">here</a>.',
        ['@scopes' => 'https://developer.spotify.com/documentation/web-api/concepts/scopes']
      );

    $form['network']['advanced']['endpoints']['#description'] =
      $this->t(
      'Define the Endpoints to be requested when user authenticates with Spotify for the first time<br>
                Enter each endpoint in different lines in the format <em>endpoint</em>|<em>name_of_endpoint</em>.<br>
                <b>For instance:</b><br>
                //me/top/artists|my_top_artists<br>
                /me/following|my_following'
      );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $api_version = $form_state->getValue('api_version');
    if ($api_version[0] === 'v') {
      $api_version = substr($api_version, 1);
      $form_state->setValue('api_version', $api_version);
    }
    if (!preg_match('/^([1-9]+)$/', $api_version)) {
      $form_state->setErrorByName('api_version', $this->t('Invalid API version. The syntax for API version is for example <em>v1</em>'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->config('social_auth_spotify.settings')
      ->set('api_version', $values['api_version'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
