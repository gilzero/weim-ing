<?php

namespace Drupal\phpmailer_smtp\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\phpmailer_smtp\PluginManager\PhpmailerOauth2PluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form to configure PHPMailer SMTP settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The PHPMailer OAuth2 plugin manager.
   *
   * @var \Drupal\phpmailer_smtp\PluginManager\PhpmailerOauth2PluginManagerInterface
   */
  protected $phpmailerOauth2PluginManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\phpmailer_smtp\PluginManager\PhpmailerOauth2PluginManagerInterface $plugin_manager
   *   The PHPMMailer OAuth2 plugin manager.
   */
  public function __construct(MailManagerInterface $mail_manager, LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, PhpmailerOauth2PluginManagerInterface $plugin_manager) {
    $this->mailManager = $mail_manager;
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->phpmailerOauth2PluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.mail'),
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('plugin.manager.phpmailer_oauth2')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpmailer_smtp_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['phpmailer_smtp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get mutable config to exclude overrides.
    $config = $this->configFactory()->getEditable('phpmailer_smtp.settings');

    $form['server']['smtp_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Primary SMTP server'),
      '#default_value' => $config->get('smtp_host'),
      '#description' => $this->t('The host name or IP address of your primary SMTP server.'),
      '#required' => TRUE,
    ];
    $form['server']['smtp_hostbackup'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Backup SMTP server'),
      '#default_value' => $config->get('smtp_hostbackup'),
      '#description' => $this->t('Optional host name or IP address of a backup server, if the primary server fails.  You may override the default port below by appending it to the host name separated by a colon.  Example: %hostname', ['%hostname' => 'localhost:587']),
    ];
    $form['server']['smtp_port'] = [
      '#type' => 'number',
      '#title' => $this->t('SMTP port'),
      '#size' => 5,
      '#maxlength' => 5,
      '#default_value' => $config->get('smtp_port'),
      '#description' => $this->t('The standard SMTP port is 25. Secure connections (including Google Mail), typically use 465.'),
      '#required' => TRUE,
    ];
    $form['server']['smtp_protocol'] = [
      '#type' => 'select',
      '#title' => $this->t('Use secure protocol'),
      '#default_value' => $config->get('smtp_protocol'),
      '#options' => [
        '' => $this->t('No'),
        'ssl' => $this->t('SSL'),
        'tls' => $this->t('TLS'),
      ],
      '#description' => $this->t('Whether to use an encrypted connection to communicate with the SMTP server.'),
    ];
    if (!function_exists('openssl_open')) {
      $form['server']['smtp_protocol']['#default_value'] = '';
      $form['server']['smtp_protocol']['#disabled'] = TRUE;
      $form['server']['smtp_protocol']['#description'] .= ' ' . $this->t('Note: This option has been disabled since your PHP installation does not seem to have support for OpenSSL.');
      $config->set('smtp_protocol', '')->save();
    }

    $auth_type_options = [
      'basic_auth' => $this->t('Basic Authentication'),
    ];

    $oauth2plugins = $this->phpmailerOauth2PluginManager->getDefinitions();
    if (is_array($oauth2plugins)) {
      foreach ($oauth2plugins as $id => $plugin) {
        $auth_type_options[$id] = $plugin['name']->__toString();
      }
    }

    $form['smtp_authentication_type'] = [
      '#type' => 'select',
      '#title' => $this->t('SMTP authentication type'),
      '#options' => $auth_type_options,
      '#default_value' => $config->get('smtp_authentication_type'),
      '#description' => $this->t("Select SMTP authentication method."),
    ];

    $form['auth'] = [
      '#type' => 'details',
      '#title' => $this->t('SMTP authentication'),
      '#description' => $this->t('Leave both fields empty, if your SMTP server does not require authentication.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#states' => [
        'visible' => [
          'select[name="smtp_authentication_type"]' => ['value' => 'basic_auth'],
        ],
      ],
    ];
    $form['auth']['smtp_username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $config->get('smtp_username'),
    ];
    if (!$config->get('smtp_hide_password')) {
      $form['auth']['smtp_password'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Password'),
        '#default_value' => $config->get('smtp_password'),
      ];
      $form['auth']['smtp_hide_password'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Hide password'),
        '#default_value' => 0,
        '#description' => $this->t("Check this option to permanently hide the plain text password. You may still change the password afterwards, but it won't be displayed anymore."),
      ];
    }
    else {
      $have_password = ($config->get('smtp_password') != '');
      $form['auth']['smtp_password'] = [
        '#type' => 'password',
        '#title' => $have_password ? $this->t('Change password') : $this->t('Password'),
        '#description' => $have_password ? $this->t('Leave empty if you do not intend to change the current password.') : '',
      ];
    }
    $form['auth']['smtp_delete_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Delete password'),
      '#default_value' => 0,
      '#description' => $this->t("Check this option to delete the current password."),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced SMTP settings'),
    ];
    $form['advanced']['smtp_fromname'] = [
      '#type' => 'textfield',
      '#title' => $this->t('"From" name'),
      '#default_value' => $config->get('smtp_fromname'),
      '#description' => $this->t('Enter a name that should appear as the sender for all messages.  If left blank the site name will be used instead: %sitename.', ['%sitename' => $this->config('system.site')->get('name')]),
    ];
    $form['advanced']['smtp_ehlo_host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HELO/EHLO Hostname'),
      '#default_value' => $config->get('smtp_ehlo_host'),
      '#description' => $this->t('The hostname to use as the default HELO/EHLO string and Message-ID header. Leave blank to let PHPMailer determine it.'),
    ];
    $form['advanced']['smtp_always_replyto'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Always set "Reply-To" address'),
      '#default_value' => $config->get('smtp_always_replyto'),
      '#description' => $this->t('Enables setting the "Reply-To" address to the original sender of the message, if unset.  This is required when using Google Mail, which would otherwise overwrite the original sender.'),
    ];
    $form['advanced']['smtp_keepalive'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep connection alive'),
      '#default_value' => $config->get('smtp_keepalive'),
      '#description' => $this->t('Whether to reuse an existing connection during a request.  Improves performance when sending a lot of e-mails at once.'),
    ];
    $form['advanced']['smtp_debug'] = [
      '#type' => 'select',
      '#title' => $this->t('Debug level'),
      '#default_value' => $config->get('smtp_debug'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('Errors only'),
        2 => $this->t('Server responses'),
        4 => $this->t('Full communication'),
      ],
      '#description' => $this->t("Debug the communication with the SMTP server.  You normally shouldn't enable this unless you're trying to debug e-mail sending problems."),
    ];
    $form['advanced']['smtp_debug_log'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Record debugging output in Drupal's log"),
      '#default_value' => $config->get('smtp_debug_log'),
      '#description' => $this->t("If this is checked, the debugging out put that is produced will also be recorded in Drupal's database log."),
      '#states' => [
        'visible' => [
          ':input[name=smtp_debug]' => ['!value' => 0],
        ],
      ],
    ];
    $form['advanced']['smtp_timeout'] = [
      '#type' => 'number',
      '#step' => 1,
      '#title' => $this->t('SMTP timeout'),
      '#default_value' => $config->get('smtp_timeout'),
      '#description' => $this->t('The number of seconds to wait for a response from the SMTP server.'),
    ];

    $form['advanced']['ssl_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced SSL settings'),
      '#description' => $this->t('If you are attempting to connect to a broken or malconfigured secure mail server, you can try toggling one or more of these options. <strong>If changing any of these options allows connection to the server, you should consider either fixing the SSL certificate, or using a different SMTP server, as the connection may be insecure.</strong>'),
    ];
    $ssl_verify_peer = $config->get('smtp_ssl_verify_peer');
    $form['advanced']['ssl_settings']['smtp_ssl_verify_peer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify peer'),
      '#default_value' => isset($ssl_verify_peer) ? $ssl_verify_peer : 1,
      '#description' => $this->t('If this is checked, it will require verification of the SSL certificate being used on the mail server.'),
    ];
    $ssl_verify_peer_name = $config->get('smtp_ssl_verify_peer_name');
    $form['advanced']['ssl_settings']['smtp_ssl_verify_peer_name'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Verify peer name'),
      '#default_value' => isset($ssl_verify_peer_name) ? $ssl_verify_peer_name : 1,
      '#description' => $this->t("If this is checked, it will require verification of the mail server's name in the SSL certificate."),
    ];
    $ssl_allow_self_signed = $config->get('smtp_ssl_allow_self_signed');
    $form['advanced']['ssl_settings']['smtp_ssl_allow_self_signed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow self signed'),
      '#default_value' => isset($ssl_allow_self_signed) ? $ssl_allow_self_signed : 0,
      '#description' => $this->t('If this is checked, it will allow conecting to a mail server with a self-signed SSL certificate. (This requires "Verify peer" to be enabled.)'),
      '#states' => [
        'enabled' => [
          ':input[name="ssl_verify_peer"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['advanced']['envelope_sender_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Envelope Sender Settings'),
      '#description' => $this->t('Specify what should be used in the SMTP <em>MAIL FROM:</em> command. Usually this will be <em>Default</em>'),
    ];
    $form['advanced']['envelope_sender_settings']['smtp_envelope_sender_option'] = [
      '#type' => 'select',
      '#title' => $this->t('Envelope sender'),
      '#default_value' => $config->get('smtp_envelope_sender_option'),
      '#options' => [
        'default' => $this->t('Default'),
        'site_mail' => $this->t('Site mail'),
        'from_address' => $this->t('From header'),
        'other' => $this->t('Other..'),
      ],
    ];
    $form['advanced']['envelope_sender_settings']['smtp_envelope_sender'] = [
      '#type' => 'email',
      '#title' => $this->t('Envelope sender address'),
      '#default_value' => $config->get('smtp_envelope_sender'),
      '#description' => $this->t('Type in an address to use as the envelope sender.'),
      '#states' => [
        'visible' => [
          ':input[name="smtp_envelope_sender_option"]' => ['value' => 'other'],
        ],
      ],
    ];

    $form['test'] = [
      '#type' => 'details',
      '#title' => $this->t('Test configuration'),
    ];
    $form['test']['phpmailer_smtp_test'] = [
      '#type' => 'email',
      '#title' => $this->t('Recipient'),
      '#default_value' => '',
      '#description' => $this->t('Type in an address to have a test e-mail sent there.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (intval($form_state->getValue('smtp_port') == 0)) {
      $form_state->setErrorByName('smtp_port', $this->t('You must enter a valid SMTP port number.'));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Save the configuration changes.
    $phpmailer_smtp_config = $this->config('phpmailer_smtp.settings');
    $phpmailer_smtp_config->set('smtp_host', $values['smtp_host'])
      ->set('smtp_hostbackup', $values['smtp_hostbackup'])
      ->set('smtp_port', $values['smtp_port'])
      ->set('smtp_protocol', $values['smtp_protocol'])
      ->set('smtp_ssl_verify_peer', $values['smtp_ssl_verify_peer'])
      ->set('smtp_ssl_verify_peer_name', $values['smtp_ssl_verify_peer_name'])
      ->set('smtp_ssl_allow_self_signed', $values['smtp_ssl_allow_self_signed'])
      ->set('smtp_username', $values['smtp_username'])
      ->set('smtp_authentication_type', $values['smtp_authentication_type'])
      ->set('smtp_fromname', $values['smtp_fromname'])
      ->set('smtp_ehlo_host', $values['smtp_ehlo_host'])
      ->set('smtp_always_replyto', $values['smtp_always_replyto'])
      ->set('smtp_keepalive', $values['smtp_keepalive'])
      ->set('smtp_debug', $values['smtp_debug'])
      ->set('smtp_debug_log', $values['smtp_debug_log'])
      ->set('smtp_timeout', $values['smtp_timeout'])
      ->set('smtp_envelope_sender_option', $values['smtp_envelope_sender_option'])
      ->set('smtp_envelope_sender', $values['smtp_envelope_sender']);

    // Only save option if it is present.
    if (!empty($values['smtp_hide_password'])) {
      $phpmailer_smtp_config->set('smtp_hide_password', $values['smtp_hide_password']);
    }

    // Only save the password if it is not empty.
    if (!empty($values['smtp_password'])) {
      $phpmailer_smtp_config->set('smtp_password', $values['smtp_password']);
    }

    // Check option to delete the password.
    if (!empty($values['smtp_delete_password'])) {
      $phpmailer_smtp_config->set('smtp_password', '');
    }

    $phpmailer_smtp_config->save();

    // Send a test email message, if an email address was entered.
    if ($values['phpmailer_smtp_test']) {
      $this->sendTestEmail($values);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * Sends a test email.
   *
   * @param array $values
   *   Array of form values.
   */
  private function sendTestEmail(array $values) {
    // Since this is being sent to an email address that may not necessarily
    // be tied to a user account, use the site's default language.
    $langcode = $this->languageManager->getDefaultLanguage()->getId();

    // Prepare the message without sending.
    $message = $this->mailManager->mail('phpmailer_smtp', 'test', $values['phpmailer_smtp_test'], $langcode, [], NULL, FALSE);
    $message['subject'] = (string) $this->t('PHPMailer SMTP test email');
    $message['body'] = (string) $this->t('Your site is properly configured to send emails using the PHPMailer library.');

    // Send the message using PHPMailer SMTP.
    $phpMailerSmtp = $this->mailManager->createInstance('phpmailer_smtp');
    $phpMailerSmtp->mail($message);

    // Some users may not have the dblog module enabled.
    if ($this->moduleHandler->moduleExists('dblog')) {
      $watchdog_url = Link::createFromRoute($this->t('Check the logs'), 'dblog.overview');
      $this->messenger()->addMessage($this->t('A test e-mail has been sent to %email. @watchdog-url for any error messages.', [
        '%email' => $values['phpmailer_smtp_test'],
        '@watchdog-url' => $watchdog_url->toString(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('A test e-mail has been sent to %email.', [
        '%email' => $values['phpmailer_smtp_test'],
      ]));
    }
  }

}
