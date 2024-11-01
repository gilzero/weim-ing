<?php

namespace Drupal\matomo_reports\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\matomo_reports\MatomoData;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration settings for matomo server and connection information.
 */
class MatomoReportsSettings extends ConfigFormBase {

  /**
   * The HttpClient.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a MatomoReportsSettings object.
   *
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HttpClient.
   */
  public function __construct(ClientInterface $httpClient) {
    $this->httpClient = $httpClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'matomo_reports.matomoreportssettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matomo_reports_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('matomo_reports.matomoreportssettings');
    $form['matomo_reports_server'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Matomo report server'),
    ];
    $form['matomo_reports_server']['matomo_server_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matomo Server URL'),
      '#description' => $this->t('The URL to your Matomo base directory, e.g., &quot;https://analytics.example.com/matomo/&quot;.'),
      '#maxlength' => 255,
      '#size' => 80,
      '#default_value' => $config->get('matomo_server_url'),
    ];
    $form['matomo_reports_server']['matomo_server_url_ignore_ssl'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do not verify Matomo Server SSL certificate'),
      '#description' => $this->t('Check this if your Matomo server uses a self-signed SSL certificate. This is a potential security risk, and should only be used for testing or development purposes.'),
      '#default_value' => $config->get('matomo_server_url_ignore_ssl'),
    ];
    $form['token_auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Token auth'),
      '#description' => $this->t('To see matomo reports in Drupal you need a <strong>token_auth</strong> value. You can find it in the  <strong>Users</strong> tab under the <strong>Settings</strong> link in your Matomo account or ask your Matomo server administrator.'),
    ];
    $form['token_auth']['matomo_reports_token_auth'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matomo authentication string'),
      '#description' => $this->t('Leave blank if you prefer each user setting their own, or paste it here to have a global <strong>token_auth</strong>. If anonymous users have view permissions in Matomo you can set this value to <strong>anonymous</strong>. Users still need &quot;Access Matomo reports&quot; permission to see the reports in Drupal.'),
      '#maxlength' => 40,
      '#size' => 40,
      '#default_value' => $config->get('matomo_reports_token_auth'),
    ];
    $form['matomo_reports_sites'] = [
      '#type' => 'details',
      '#title' => $this->t('Allowed sites'),
      '#description' => $this->t('List sites you want restrict your users access to.'),
    ];
    $sites = MatomoData::getSites($config->get('matomo_reports_token_auth'));
    $allowed_sites_desc = $this->t('List accessible sites id separated by a comma. Example: &quot;1,4,12&quot;. Leave blank to let users see all sites accessible on matomo server with current token auth (highly recommended in case of per user token auth).');
    if (is_array($sites) && count($sites)) {
      if ($config->get('matomo_reports_token_auth')) {
        $allowed_sites_desc .= ' ' . $this->t('Sites currently accessible with global token_auth are:');
      }
      else {
        $allowed_sites_desc .= ' ' . $this->t('Sites current accessible as anonymous are:');
      }
      foreach ($sites as $site) {
        $allowed_sites_desc .= '<br />' . (int) $site['idsite'] . ' - ' . Html::escape($site['name']);
      }
    }
    else {
      $allowed_sites_desc .= ' ' . $this->t('No accessible sites are available with current global token auth. Please check your token auth is correct and that it has view permission on Matomo server.');
    }
    $form['matomo_reports_sites']['matomo_reports_allowed_sites'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed sites'),
      '#description' => $allowed_sites_desc,
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('matomo_reports_allowed_sites'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $url = $form_state->getValue('matomo_server_url');
    if (!empty($url)) {

      if (substr($url, -strlen('/')) !== '/') {
        $url .= '/';
        $form_state->setValueForElement($form['matomo_reports_server']['matomo_server_url'], $url);
      }
      $url = $url . 'piwik.php';
      try {
        $options = [];
        if ($form_state->getValue('matomo_server_url_ignore_ssl')) {
          $options += [
            'curl' => [
              CURLOPT_SSL_VERIFYHOST => FALSE,
              CURLOPT_SSL_VERIFYPEER => FALSE,
            ],
          ];
        }
        $result = $this->httpClient->get($url, $options);
        if ($result->getStatusCode() != 200) {
          $form_state->setErrorByName('matomo_server_url', $this->t('The validation of "@url" failed with error "@error" (HTTP code @code).', [
            '@url' => UrlHelper::filterBadProtocol($url),
            '@error' => $result->getReasonPhrase(),
            '@code' => $result->getStatusCode(),
          ]));
        }
      }
      catch (RequestException $exception) {
        $form_state->setErrorByName('matomo_server_url', $this->t('The validation of "@url" failed with an exception "@error" (HTTP code @code).', [
          '@url' => UrlHelper::filterBadProtocol($url),
          '@error' => $exception->getMessage(),
          '@code' => $exception->getCode(),
        ]));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('matomo_reports.matomoreportssettings')
      ->set('matomo_report_server', $form_state->getValue('matomo_report_server'))
      ->set('matomo_server_url', $form_state->getValue('matomo_server_url'))
      ->set('token_auth', $form_state->getValue('token_auth'))
      ->set('matomo_reports_token_auth', $form_state->getValue('matomo_reports_token_auth'))
      ->set('matomo_reports_sites', $form_state->getValue('matomo_reports_sites'))
      ->set('matomo_reports_allowed_sites', $form_state->getValue('matomo_reports_allowed_sites'))
      ->set('matomo_server_url_ignore_ssl', $form_state->getValue('matomo_server_url_ignore_ssl'))
      ->save();
  }

}
