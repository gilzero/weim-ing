<?php

namespace Drupal\whatsapp_in\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Initialize Class WhatsappForm.
 */
class WhatsappForm extends ConfigFormBase {

  /**
   * The HTTP client to fetch country codes.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a new WhatsappForm.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
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
  public function getFormId() {
    return 'whatsapp_number_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['whatsapp.settings'];
  }

  /**
   * Fetch country codes from an external API or use a static list.
   *
   * @return array
   *   An array of country codes.
   */
  protected function fetchCountryCodes() {
    $country_codes = [];

    try {
      $response = $this->httpClient->get('https://restcountries.com/v3.1/all');
      $data = json_decode($response->getBody(), TRUE);

      if (!empty($data)) {
        foreach ($data as $country) {
          if (isset($country['idd']['root']) && isset($country['idd']['suffixes'])) {
            foreach ($country['idd']['suffixes'] as $suffix) {
              $code = $country['idd']['root'] . $suffix;
              $country_codes[$code] = $country['name']['common'] . " ($code)";
            }
          }
        }
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('whatsapp_in')->error($e->getMessage());
    }

    return $country_codes;
  }

  /**
   * Extract the longest matching country code from the phone number.
   *
   * @param string $phone_number
   *   The phone number including the country code.
   * @param array $country_codes
   *   The list of country codes.
   *
   * @return string
   *   The extracted country code.
   */
  protected function extractCountryCode($phone_number, $country_codes) {
    $matched_code = '';
    foreach ($country_codes as $code => $name) {
      if (strpos($phone_number, $code) === 0 && strlen($code) > strlen($matched_code)) {
        $matched_code = $code;
      }
    }
    return $matched_code;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('whatsapp.settings');

    // Fetch country codes from the API.
    $country_codes = $this->fetchCountryCodes();

    // Extract the saved phone number and country code.
    $full_phone_number = $config->get('phone_number');

    // Check if $full_phone_number is not null before processing
    if (!empty($full_phone_number)) {
      $default_country_code = $this->extractCountryCode($full_phone_number, $country_codes);
      $default_phone_number = substr($full_phone_number, strlen($default_country_code));
    } else {
      // Handle case where phone number is null
      $default_country_code = '';
      $default_phone_number = '';
    }

    $form['country_code'] = [
      '#type' => 'select',
      '#title' => $this->t('Country Code'),
      '#description' => $this->t('Select your country code.'),
      '#options' => $country_codes,
      '#default_value' => $default_country_code,
      '#required' => TRUE,
    ];

    $form['phone_number'] = [
      '#type' => 'tel',
      '#title' => $this->t('Mobile Number'),
      '#description' => $this->t('Enter the mobile number without the country code.'),
      '#default_value' => $default_phone_number,
      '#maxlength' => 15,
      '#size' => 15,
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $phone_number = $form_state->getValue('phone_number');

    if (!preg_match('/^\d+$/', $phone_number)) {
      $form_state->setErrorByName('phone_number', $this->t('Please enter a valid mobile number without special characters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country_code = $form_state->getValue('country_code');
    $phone_number = $form_state->getValue('phone_number');
    $this->config('whatsapp.settings')
      // Combine country code and phone number before saving.
      ->set('phone_number', $country_code . $phone_number)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
