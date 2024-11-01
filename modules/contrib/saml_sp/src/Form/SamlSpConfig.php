<?php

namespace Drupal\saml_sp\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use OneLogin\Saml2\Utils;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration form.
 */
class SamlSpConfig extends ConfigFormBase {

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('messenger')
    );
  }

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, TypedConfigManagerInterface|null $typedConfigManager, MessengerInterface $messenger,) {
    parent::__construct($configFactory, $typedConfigManager);
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saml_sp_config_sp';
  }

  /**
   * Check if config variable is overridden by the settings.php.
   *
   * @param string $name
   *   SAML SP settings key.
   *
   * @return bool
   *   Boolean.
   */
  protected function isOverridden($name) {
    return $this->configFactory->get('saml_sp.settings')->hasOverrides($name);
  }

  /**
   * Return the overridden value (if overridden)
   *
   * @param string $name
   *   The name of the config setting.
   *
   * @return array|mixed|null
   *   The value the config setting.
   */
  protected function overriddenValue($name) {
    if ($this->isOverridden($name)) {
      // Return the overridden value.
      $value = $this->configFactory->get('saml_sp.settings')->get($name);
    }
    else {
      // Return the value in the database.
      $value = $this->configFactory->getEditable('saml_sp.settings')
        ->get($name);
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('saml_sp.settings');
    $values = $form_state->getValues();
    $this->configRecurse($config, $values['contact'], 'contact');
    $this->configRecurse($config, $values['organization'], 'organization');
    $this->configRecurse($config, $values['security'], 'security');
    if (!$this->isOverridden('strict')) {
      $config->set('strict', (boolean) $values['strict']);
    }
    if (!$this->isOverridden('debug')) {
      $config->set('debug', (boolean) $values['debug']);
    }
    if (!$this->isOverridden('key_location')) {
      $config->set('key_location', trim($values['key_location']));
    }
    if (!$this->isOverridden('cert_location')) {
      $config->set('cert_location', trim($values['cert_location']));
    }
    if (!$this->isOverridden('new_cert_location')) {
      $config->set('new_cert_location', trim($values['new_cert_location']));
    }
    if (!$this->isOverridden('entity_id')) {
      $config->set('entity_id', $values['entity_id']);
    }
    if (!$this->isOverridden('assertion_urls')) {
      $config->set('assertion_urls', $values['assertion_urls']);
    }
    if (!$this->isOverridden('valid_until')) {
      $config->set('valid_until', trim($values['valid_until']));
    }

    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ensure the cert and key files are provided and exist in the system if
    // signed or encryption options require them.
    $values = $form_state->getValues();

    $org_keys = ['name', 'display_name', 'url'];
    $org_missing = [];
    foreach ($org_keys as $key) {
      if (empty($values['organization'][$key])) {
        $org_missing[] = $key;
      }
    }
    if (!empty($org_missing) && $org_missing !== $org_keys) {
      foreach ($org_missing as $key) {
        $form_state->setError($form['organization'][$key], $this->t('The %field must be provided for %org.', [
          '%field' => $form['organization'][$key]['#title'],
          '%org' => $form['organization']['#title'],
        ]));
      }
    }

    if (
      $values['security']['authnRequestsSigned'] ||
      $values['security']['logoutRequestSigned'] ||
      $values['security']['logoutResponseSigned'] ||
      $values['security']['wantNameIdEncrypted'] ||
      $values['security']['signMetaData']
    ) {
      foreach (['key_location', 'cert_location'] as $key) {
        $file_path = trim($values[$key]);
        if (empty($file_path)) {
          $form_state->setError($form[$key], $this->t('The %field must be provided.', ['%field' => $form[$key]['#title']]));
        }
        elseif (!file_exists($file_path)) {
          $form_state->setError($form[$key], $this->t('The %input file does not exist.', ['%input' => $values[$key]]));
        }
      }
    }

    if (!empty($values['valid_until']) && strtolower($values['valid_until']) !== '<certificate>') {
      try {
        $dti = new \DateTimeImmutable($values['valid_until']);
      } catch (\Throwable $e) {
        // PHP 8 throws a ValueError, but handle it the same as PHP 7.
        $dti = FALSE;
      }
      if (!$dti) {
        $form_state->setError($form['valid_until'], $this->t('Cannot parse the "Valid until" date'));
      }
    }
  }

  /**
   * Recursively go through the set values to set the configuration.
   */
  protected function configRecurse($config, $values, $base = '') {
    foreach ($values as $var => $value) {
      if (!empty($base)) {
        $v = $base . '.' . $var;
      }
      else {
        $v = $var;
      }
      if (!is_array($value) &&
        // don't save the value if it is overridden,.
        !$this->isOverridden($v)
        // It is pointless.
      ) {
        $config->set($v, $value);
      }
      else {
        $this->configRecurse($config, $value, $v);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['saml_sp.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['entity_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity ID'),
      '#description' => $this->t(
        'This is the unique name that the Identity Providers will know your site as. Defaults to the login page %login_url',
        [
          '%login_url' => Url::fromRoute('user.page', [], ['absolute' => TRUE])
            ->toString(),
        ]),
      '#default_value' => $this->overriddenValue('entity_id'),
    ];

    $endpoint_url = Url::fromRoute('saml_sp.consume', [], [
      'language' => FALSE,
      'alias' => TRUE,
      'absolute' => TRUE,
    ]);

    $form['assertion_urls'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Assertion Urls'),
      '#description' => $this->t('You can have multiple urls that the SAML AuthNRequest can have the user returned to. The default is "@default_url" but additional can be provided. This is especially useful if the same Drupal site is being used for multiple domains. One per line.', ['@default_url' => $endpoint_url->toString()]),
      '#default_value' => $this->overriddenValue('assertion_urls') ?: $endpoint_url->toString(),
    ];

    $form['contact'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Contact Information'),
      '#description' => $this->t('Information to be included in the federation metadata.'),
      '#tree' => TRUE,
    ];
    $form['contact']['technical'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Technical'),
    ];
    $form['contact']['technical']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->overriddenValue('contact.technical.name'),
      '#disabled' => $this->isOverridden('contact.technical.name'),
    ];
    $form['contact']['technical']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->overriddenValue('contact.technical.email'),
      '#disabled' => $this->isOverridden('contact.technical.email'),
    ];
    $form['contact']['support'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Support'),
    ];
    $form['contact']['support']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $this->overriddenValue('contact.support.name'),
      '#disabled' => $this->isOverridden('contact.support.name'),
    ];
    $form['contact']['support']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $this->overriddenValue('contact.support.email'),
      '#disabled' => $this->isOverridden('contact.support.email'),
    ];

    $form['organization'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Organization'),
      '#description' => $this->t('Organization information for the federation metadata. If you provide any values you must provide all values.'),
      '#tree' => TRUE,
    ];
    $form['organization']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('This is a short name for the organization'),
      '#default_value' => $this->overriddenValue('organization.name'),
      '#disabled' => $this->isOverridden('organization.name'),
      '#states' => [
        'required' => [
          [
            ':input[name="organization[display_name]"]' => ['filled' => TRUE],
          ],
          'or',
          [
            ':input[name="organization[url]"]' => ['filled' => TRUE],
          ],
        ],
      ],
    ];
    $form['organization']['display_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Display Name'),
      '#description' => $this->t('This is a long name for the organization'),
      '#default_value' => $this->overriddenValue('organization.display_name'),
      '#disabled' => $this->isOverridden('organization.display_name'),
      '#states' => [
        'required' => [
          [
            ':input[name="organization[name]"]' => ['filled' => TRUE],
          ],
          'or',
          [
            ':input[name="organization[url]"]' => ['filled' => TRUE],
          ],
        ],
      ],
    ];
    $form['organization']['url'] = [
      '#type' => 'url',
      '#title' => $this->t('URL'),
      '#description' => $this->t('This is a URL for the organization'),
      '#default_value' => $this->overriddenValue('organization.url'),
      '#disabled' => $this->isOverridden('organization.url'),
      '#states' => [
        'required' => [
          [
            ':input[name="organization[display_name]"]' => ['filled' => TRUE],
          ],
          'or',
          [
            ':input[name="organization[name]"]' => ['filled' => TRUE],
          ],
        ],
      ],
    ];

    $form['strict'] = [
      '#type' => 'checkbox',
      '#title' => t('Strict Protocol'),
      '#description' => t('SAML 2 Strict protocol will be used.'),
      '#default_value' => $this->overriddenValue('strict'),
      '#disabled' => $this->isOverridden('strict'),
    ];

    $form['security'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Security'),
      '#tree' => TRUE,
    ];
    $form['security']['offered'] = [
      '#markup' => $this->t('Signatures and Encryptions Offered:'),
    ];
    $form['security']['nameIdEncrypted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('NameID Encrypted'),
      '#description' => $this->t('Offering encryption will require you to provide a certificate and key.'),
      '#default_value' => $this->overriddenValue('security.nameIdEncrypted'),
      '#disabled' => $this->isOverridden('security.nameIdEncrypted'),
    ];
    $form['security']['authnRequestsSigned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Authn Requests Signed'),
      '#description' => $this->t('Offering to sign requests will require you to provide a certificate and key.'),
      '#default_value' => $this->overriddenValue('security.authnRequestsSigned'),
      '#disabled' => $this->isOverridden('security.authnRequestsSigned'),
    ];
    $form['security']['logoutRequestSigned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logout Requests Signed'),
      '#description' => $this->t('Offering to sign requests will require you to provide a certificate and key.'),
      '#default_value' => $this->overriddenValue('security.logoutRequestSigned'),
      '#disabled' => $this->isOverridden('security.logoutRequestSigned'),
    ];
    $form['security']['logoutResponseSigned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Logout Response Signed'),
      '#description' => $this->t('Offering to sign responses will require you to provide a certificate and key.'),
      '#default_value' => $this->overriddenValue('security.logoutResponseSigned'),
      '#disabled' => $this->isOverridden('security.logoutResponseSigned'),
    ];

    $form['security']['required'] = [
      '#markup' => $this->t('Signatures and Encryptions Required:'),
    ];
    $form['security']['wantMessagesSigned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Want Messages Signed'),
      '#description' => $this->t('Requiring messages to be signed will require the IdP configuration to have a certificate.'),
      '#default_value' => $this->overriddenValue('security.wantMessagesSigned'),
      '#disabled' => $this->isOverridden('security.wantMessagesSigned'),
    ];
    $form['security']['wantAssertionsSigned'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Want Assertions Signed'),
      '#description' => $this->t('Requiring assertions to be signed will require the IdP configuration to have a certificate.'),
      '#default_value' => $this->overriddenValue('security.wantAssertionsSigned'),
      '#disabled' => $this->isOverridden('security.wantAssertionsSigned'),
    ];
    $form['security']['wantNameIdEncrypted'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Want NameID Encrypted'),
      '#description' => $this->t('Requiring the NameId to be encrypted will require the IdP configuration to have a certificate.'),
      '#default_value' => $this->overriddenValue('security.wantNameIdEncrypted'),
      '#disabled' => $this->isOverridden('security.wantNameIdEncrypted'),
    ];
    $form['security']['metadata'] = [
      '#markup' => $this->t('Metadata:'),
    ];

    $form['security']['signMetaData'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sign Meta Data'),
      '#description' => $this->t('Signing your metadata will require you to provide a certificate and key.'),
      '#default_value' => $this->overriddenValue('security.signMetaData'),
      '#disabled' => $this->isOverridden('security.signMetaData'),
    ];
    $form['security']['signatureAlgorithm'] = [
      '#type' => 'select',
      '#title' => $this->t('Signature Algorithm'),
      '#description' => $this->t('What algorithm do you want used for messages signatures?'),
      '#options' => [
        /*
        XMLSecurityKey::DSA_SHA1 => 'DSA SHA-1',
        XMLSecurityKey::HMAC_SHA1 => 'HMAC SHA-1',
        /**/
        XMLSecurityKey::RSA_SHA1 => 'SHA-1',
        XMLSecurityKey::RSA_SHA256 => 'SHA-256',
        XMLSecurityKey::RSA_SHA384 => 'SHA-384',
        XMLSecurityKey::RSA_SHA512 => 'SHA-512',
      ],
      '#default_value' => $this->overriddenValue('security.signatureAlgorithm'),
      '#disabled' => $this->isOverridden('security.signatureAlgorithm'),
    ];
    $form['security']['lowercaseUrlencoding'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Lowercase Url Encoding'),
      /*
      '#description'    => $this->t(""),
      /**/
      '#default_value' => $this->overriddenValue('security.lowercaseUrlencoding'),
      '#disabled' => $this->isOverridden('security.lowercaseUrlencoding'),
    ];

    $form['cert_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Certificate Location'),
      '#description' => $this->t('The location of the X.509 certificate file on the server. This must be a location that PHP can read.'),
      '#default_value' => $this->overriddenValue('cert_location'),
      '#disabled' => $this->isOverridden('cert_location'),
      '#states' => [
        'required' => [
          ['input[name="security[authnRequestsSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutRequestSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutResponseSigned]"' => ['checked' => TRUE]],
          ['input[name="security[wantNameIdEncrypted]"' => ['checked' => TRUE]],
          ['input[name="security[signMetaData]"' => ['checked' => TRUE]],
        ],
      ],
      '#suffix' => $this->certInfo($this->overriddenValue('cert_location')),
    ];

    $form['key_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Location'),
      '#description' => $this->t('The location of the X.509 key file on the server. This must be a location that PHP can read.'),
      '#default_value' => $this->overriddenValue('key_location'),
      '#disabled' => $this->isOverridden('key_location'),
      '#states' => [
        'required' => [
          ['input[name="security[authnRequestsSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutRequestSigned]"' => ['checked' => TRUE]],
          ['input[name="security[logoutResponseSigned]"' => ['checked' => TRUE]],
          ['input[name="security[wantNameIdEncrypted]"' => ['checked' => TRUE]],
          ['input[name="security[signMetaData]"' => ['checked' => TRUE]],
        ],
      ],
    ];

    $form['new_cert_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('New Certificate Location'),
      '#description' => $this->t('The location of the x.509 certificate file on the server. If the certificate above is about to expire add your new certificate here after you have obtained it. This will add the new certificate to the metadata to let the IdP know of the new certificate. This must be a location that PHP can read.'),
      '#default_value' => $this->overriddenValue('new_cert_location'),
      '#disabled' => $this->isOverridden('new_cert_location'),
      '#suffix' => $this->certInfo($this->overriddenValue('new_cert_location')),
    ];

    $form['valid_until'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valid until'),
      '#description' => $this->t('When should this metadata expire? You may specify a fixed date and time in a format that PHP can parse, a relative time such as "+1 year", or the special value "&lt;certificate&gt;" to use the expiration date of the certificate above.'),
      '#default_value' => $this->overriddenValue('valid_until'),
      '#disabled' => $this->isOverridden('valid_until'),
    ];

    $form['metadata'] = [
      '#type' => 'fieldset',
      '#collapsed' => TRUE,
      '#collapsible' => TRUE,
      '#title' => $this->t('Metadata'),
      '#description' => $this->t('This is the Federation Metadata for this SP, please provide this to the IdP to create a Relying Party Trust (RPT)'),
    ];

    $error = FALSE;
    try {
      $metadata = saml_sp__get_metadata();
      if (is_array($metadata)) {
        if (isset($metadata[1])) {
          $errors = $metadata[1];
        }
        $metadata = $metadata[0];
      }
    } catch (\Exception $e) {
      $this->messenger->addMessage($this->t('Attempt to create metadata failed: %message.', [
        '%message' => $e->getMessage(),
      ]), MessengerInterface::TYPE_ERROR);
      $metadata = '';
      $form['metadata']['none'] = [
        '#markup' => $this->t('There is currently no metadata because of the following error: %error. Please resolve the error and return here for your metadata.', ['%error' => $e->getMessage()]),
      ];
    }

    if ($metadata) {
      $form['metadata']['data'] = [
        '#type' => 'textarea',
        '#title' => $this->t('XML Metadata'),
        '#description' => $this->t(
          'This metadata can also be accessed <a href="@url" target="_blank">here</a>',
          [
            '@url' => Url::fromRoute('saml_sp.metadata')->toString(),
          ]),
        '#disabled' => TRUE,
        '#rows' => 20,
        '#default_value' => trim($metadata),
      ];
    }

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn on debugging'),
      '#description' => $this->t('Some debugging messages will be shown.'),
      '#default_value' => $this->overriddenValue('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Retrieves pertinent certificate data and output in a string for display.
   *
   * @param string $cert_location
   *   The location of the certificate file.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|false
   *   Certificate information, or false if the it can't be read or parsed.
   */
  private function certInfo($cert_location) {
    if (!empty($cert_location) && file_exists($cert_location) && function_exists('openssl_x509_parse')) {
      $encoded_cert = trim(file_get_contents($cert_location));
      $cert = openssl_x509_parse(Utils::formatCert($encoded_cert));
      // Flatten the issuer array.
      if (!empty($cert['issuer'])) {
        foreach ($cert['issuer'] as $key => &$value) {
          if (is_array($value)) {
            $value = implode('/', $value);
          }
        }
      }

      if ($cert) {
        $info = $this->t('Name: %cert-name<br/>Issued by: %issuer<br/>Valid: %valid-from - %valid-to', [
          '%cert-name' => $cert['name'] ?? '',
          '%issuer' => isset($cert['issuer']) && is_array($cert['issuer']) ? implode('/', $cert['issuer']) : '',
          '%valid-from' => isset($cert['validFrom_time_t']) ? date('c', $cert['validFrom_time_t']) : '',
          '%valid-to' => isset($cert['validTo_time_t']) ? date('c', $cert['validTo_time_t']) : '',
        ]);
        return $info;
      }
    }
    return FALSE;
  }

}
