<?php

namespace Drupal\site_audit_send\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_audit_send\RestClient;

/**
 * Used for Site Audit Config Form altering.
 * @see site_audit_send_form_site_audit_config_form_alter()
 */
class ConfigForm {
  public static function alterForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('site_audit_send.settings');

    # @TODO: Move to site_audit config form.
    $intervals = [60, 900, 1800, 3600, 7200, 10800, 21600, 32400, 43200, 64800, 86400, 172800, 259200, 604800, 1209600, 2419200];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($intervals, $intervals));
    $options = [0 => t('Never')] + $period;

    $form['cron_config'] = [
      '#title' => t('Recurring Audits'),
      '#type' => 'details',
      '#open' => TRUE,
    ];
    $form['cron_config']['cron_save_interval'] = [
      '#type' => 'select',
      '#title' => t('Save report every'),
      '#description' => t('Regularly save audit reports.'),
      '#default_value' => $config->get('cron_save_interval'),
      '#options' => $options,
    ];
    $form['cron_config']['cron_send_interval'] = [
      '#type' => 'select',
      '#title' => t('Send report every'),
      '#description' => t('Regularly send audit reports to the configured remote server.'),
      '#default_value' => $config->get('cron_send_interval'),
      '#options' => $options,
    ];
    $form['cron_config']['cron_delete_interval'] = [
      '#type' => 'select',
      '#title' => t('Delete reports older than'),
      '#description' => t('Regularly delete audit reports.'),
      '#default_value' => $config->get('cron_delete_interval'),
      '#options' => $options,
    ];

    $form['remote_config'] = [
      '#type' => 'details',
      '#title' => t('Remote Settings'),
      '#open' => TRUE,
    ];
    $form['remote_config']['remote_url'] = [
      '#type' => 'url',
      '#title' => t('Remote Server URL'),
      '#default_value' => $config->get('remote_url'),
      '#description' => t('Enter a URL to send reports to. If using a Site Audit Server, the endpoint will look like https://MYSITE/api/site-audit?api-key=xyz. To retrieve an API key, visit My Account > Key authentication on the Site Audit Server.'),
      '#attributes' => [
        'placeholder' => 'https://www.example.com/api/site-audit',
      ],
    ];
    $form['remote_config']['remote_label'] = [
      '#type' => 'textfield',
      '#title' => t('Remote Report Label'),
      '#default_value' => $config->get('remote_label'),
      '#description' => t('The label to use when sending remote reports.'),
    ];
    $form['#validate'][] = '\Drupal\site_audit_send\Form\ConfigForm::validateForm';
    $form['#submit'][] = '\Drupal\site_audit_send\Form\ConfigForm::submitForm';
  }
  /**
   * validator for site_audit_config_form
   */
  public static function validateForm(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('remote_url')) && !UrlHelper::isValid($form_state->getValue('remote_url'), TRUE)) {
      $form_state->setErrorByName('remote_url', t('Enter a valid URL.'));
    }
    elseif (!empty($form_state->getValue('remote_url'))) {
      $client = \Drupal::service('site_audit_send.rest_client');
      $test_response = $client->testUrl($form_state->getValue('remote_url'));
      $headers = $test_response->getHeaders();
      if (isset($headers['Message'])) {
        $message = $headers['Message'][0] ? : '';
      }
      else {
        $message = t('No Message');
      }

      switch ($test_response->getStatusCode()) {
        case 200:
          \Drupal::messenger()->addMessage(t('Successfully posted to remote server. Message: :message', [
            ':message' => $message,
          ]));
          break;

        case 403:
          $form_state->setErrorByName('remote_url', t('Unable to connect to remote API (Response code 403 Access Denied.) Check your API key and try again.'));
          break;

        case 404:
          $form_state->setErrorByName('remote_url', t('Unable connect to remote API (Response code 404 Not Found). Check the path and try again.'));
          break;

        # Fake http code, set in RestClient.php
        case 599:
          $form_state->setErrorByName('remote_url', t('Unable connect to remote API. Check the url and try again.'));
          break;

        default:
          $form_state->setErrorByName('remote_url', t('Unable to connect to remote API: :message (Response Code: :code :phrase)', [
            ':code' => $test_response->getStatusCode(),
            ':phrase' => $test_response->getReasonPhrase(),
            ':message' => $message,
          ]));
          break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('site_audit_send.settings')
      ->set('remote_url', $form_state->getValue('remote_url'))
      ->set('remote_label', $form_state->getValue('remote_label'))
      ->set('cron_send_interval', $form_state->getValue('cron_send_interval'))
      ->set('cron_save_interval', $form_state->getValue('cron_save_interval'))
      ->set('cron_delete_interval', $form_state->getValue('cron_delete_interval'))
      ->save();
  }
}
