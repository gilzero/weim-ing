<?php

namespace Drupal\saml_sp_drupal_login\Form;

use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration form.
 */
class SamlSpDrupalLoginConfig extends ConfigFormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;


  /**
   * Constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saml_sp_drupal_login_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saml_sp_drupal_login.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('saml_sp_drupal_login.config');

    $idps = [];
    // List all the IdPs in the system.
    foreach (saml_sp__load_all_idps() as $machine_name => $idp) {
      $idps[$idp->id()] = $idp->label();
    }
    $form['config'] = [
      '#tree' => TRUE,
    ];
    $form['config']['idp'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('IdP'),
      '#description' => $this->t('Choose the IdP to use when authenticating Drupal logins'),
      '#default_value' => $config->get('idp') ?: [],
      '#options' => $idps ?: [],
    ];
    $site_name = $this->config('system.site')->get('name');

    $form['config']['logout'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Single Log Out'),
      '#description'    => $this->t('When logging out of %site_name also log out of the IdP', ['%site_name' => $site_name]),
      '#default_value'  => $config->get('logout'),
    ];

    $form['config']['update_email'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Update Email address'),
      '#description'    => $this->t('If an account can be found on %site_name but the e-mail address differs from the one provided by the IdP update the email on record in Drupal with the new address from the IdP. This will only make a difference is the identifying information from the IdP is not the email address.', ['%site_name' => $site_name]),
      '#default_value'  => $config->get('update_email'),
    ];

    $form['config']['update_language'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Update Language'),
      '#description'    => $this->t("If the account language of %site_name differs from that in the IdP response update to the user's account to match.", ['%site_name' => $site_name]),
      '#default_value'  => $config->get('update_language'),
    ];

    $form['config']['no_account_authenticated_user_role'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Login users without a user account as an authenticated user.'),
      '#description'    => $this->t('If a user is authenticated by the SAML Service Provider but no matching account can be found the user will be logged in as an authenticated user. This will allow users to be authenticated to receive more permissions than an anonymous user but less than a user with any other role.'),
      '#default_value'  => $config->get('no_account_authenticated_user_role') ?? FALSE,
    ];

    $uid = $config->get('no_account_authenticated_user_account');
    $users = $uid ? User::loadMultiple([$uid => $uid]) : [];
    if (isset($users[$uid])) {
      $user = $users[$uid];
    }
    else {
      $user = NULL;
    }
    if (!empty($users)) {
      $default_value = EntityAutocomplete::getEntityLabels($users);
    }
    else {
      $default_value = NULL;
    }

    $form['config']['no_account_authenticated_user_account'] = [
      '#type'               => 'entity_autocomplete',
      '#title'              => $this->t('Authenticated user account'),
      '#description'        => $this->t('This is the account with only the authenticated user role which a user is logged in as if no matching account exists. As this account will be used for all users make sure that this account has only the "Authenticated User" role.'),
      '#default_value'      => $user,
      '#target_type'        => 'user',
      '#states'             => [
        'visible'             => [
          ':input[name="config[no_account_authenticated_user_role]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['config']['force_authentication'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Force authentication'),
      '#description'    => $this->t('Users with a single sign-on session at the IdP are required to re-authenticate to log in here.'),
      '#default_value'  => $config->get('force_authentication'),
    ];

    $form['config']['force_saml_only'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Force SAML Login'),
      '#description'    => $this->t('The User Login form will not be used, when an anonymous user goes to /user they will be automatically redirected to the SAML authentication page.'),
      '#default_value'  => $config->get('force_saml_only'),
    ];

    $account_settings_url = Url::fromRoute('entity.user.admin_form', [], ['absolute' => TRUE])->toString();

    $form['config']['account_request_request_account'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Allow authenticated users without an account to request an account'),
      '#description'    => $this->t('If the <a href="@account_settings_url">account settings</a> specify that only Administrators can create new accounts (currently %require_admin), this overrides that setting and allows users who passed SAML authentication to request an account.', [
        '@account_settings_url' => $account_settings_url,
        '%require_admin' => $this->configFactory->get('user.settings')->get('register') == UserInterface::REGISTER_ADMINISTRATORS_ONLY ? 'TRUE' : 'FALSE',
      ]),
      '#default_value'  => $config->get('account_request_request_account'),
    ];

    $form['config']['account_request_create_account'] = [
      '#type'           => 'checkbox',
      '#title'          => $this->t('Always create an account for authenticated users'),
      '#description'    => $this->t('This overrides the need for administrator approval if it is specified in <a href="@account_settings_url">account settings</a>.', [
        '@account_settings_url' => $account_settings_url,
      ]),
      '#default_value'  => $config->get('account_request_create_account'),
    ];

    $form['config']['logged_in_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Authenticated User Redirect'),
      '#description' => $this->t('When a user who is already logged in tries to go to the page to initiate authentication what page should they be redirected to? begin a path with a leading "/", i.e. "/user", you can also use a route like "@front" or "user.page".', ['@front' => '<front>']),
      '#default_value' => $config->get('logged_in_redirect'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('saml_sp_drupal_login.config');
    $values = $form_state->getValues();

    foreach ($values['config'] as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();
    $this->messenger()->addStatus($this->t('Configuration updated.'));
  }

}
