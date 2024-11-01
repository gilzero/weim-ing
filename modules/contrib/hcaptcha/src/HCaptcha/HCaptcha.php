<?php


namespace Drupal\hcaptcha\HCaptcha;


class HCaptcha
{
  const SITE_VERIFY_URL = 'https://hcaptcha.com/siteverify';

  protected $attributes = array(
    'class' => 'h-captcha',
    'data-sitekey' => '',
    'data-theme' => '',
    'data-size' => '',
    'data-tabindex' => 0,
  );

  protected $siteKey = '';
  protected $secretKey = '';
  protected $errors = array();
  private $success = false;
  private $validated;
  private $requestMethod;

  public function __construct($site_key, $secret_key, $attributes = array(), RequestMethod $requestMethod = null) {
    $this->siteKey = $site_key;
    $this->secretKey = $secret_key;
    $this->requestMethod = $requestMethod;

    if (!empty($attributes) && is_array($attributes)) {
      foreach ($attributes as $name => $attribute) {
        if (isset($this->attributes[$name])) $this->attributes[$name] = $attribute;
      }
    }
  }

  /**
   * Build the hCaptcha captcha form.
   * @return mixed
   */
  public function getWidget($validation_function) {
    // Captcha requires TRUE to be returned in solution.
    $widget['solution'] = true;
    $widget['captcha_validate'] = $validation_function;
    $widget['form']['captcha_response'] = array(
      '#type' => 'hidden',
      '#value' => 'hCaptcha no captcha',
    );

    // As the validate callback does not depend on sid or solution, this
    // captcha type can be displayed on cached pages.
    $widget['cacheable'] = true;

    $widget['form']['hcaptcha_widget'] = array(
      '#markup' => '<div' . $this->getAttributesString() . '></div>',
    );
    return $widget;
  }

  public function validate($response_token, $remote_ip = '', $max_score = 0.8) {
    $query = array(
      'secret' => $this->secretKey,
      'response' => $response_token,
      'remoteip' => $remote_ip,
    );
    $this->validated = $this->requestMethod->submit(self::SITE_VERIFY_URL, array_filter($query));

    if (isset($this->validated->score)) {
      if ($this->validated->score <= $max_score) {
        $this->success = TRUE;
      }
      else {
        $this->errors = [t('Score for the response (@score) is above the acceptable max score (@max_score).', [
          '@score' => $this->validated->score,
          '@max_score' => $max_score,
        ])];
      }
    }
    elseif (isset($this->validated->success)) {
      if ($this->validated->success === TRUE) {
        // Verified!
        $this->success = TRUE;
      }
      else {
        $this->errors = $this->getResponseErrors();
      }
    }
  }

  public function isSuccess() {
    return $this->success;
  }

  public function getErrors() {
    return $this->errors;
  }

  public function getResponseErrors() {
    // Error code reference, https://hcaptcha.com/docs#server
    $errors = array();
    if (isset($this->validated->{'error-codes'}) && is_array($this->validated->{'error-codes'})) {
      $error_codes = $this->getErrorCodes();
      foreach ($this->validated->{'error-codes'} as $code) {
        if (!isset($error_codes[$code])) {
          $code = 'unknown-error';
        }
        $errors[] = $error_codes[$code];
      }
    }
    return $errors;
  }

  public function getErrorCodes() {
    $error_codes = array(
      'missing-input-secret' => t('Your secret key is missing.'),
      'invalid-input-secret' => t('Your secret key is invalid or malformed.'),
      'sitekey-secret-mismatch' => t('Your site key is invalid for your secret key.'),
      'missing-input-response' => t('The response parameter (verification token) is missing.'),
      'invalid-input-response' => t('The response parameter (verification token) is invalid or malformed.'),
      'bad-request' => t('The request is invalid or malformed.'),
      'bad-response' => t('Did not receive a 200 from the service.'),
      'connection-failed' => t('Could not connect to service.'),
      'unknown-error' => t('Not a success, but no error codes received.'),
    );
    return $error_codes;
  }

  public function getAttributesString() {
    $attributes = array_filter($this->attributes);
    foreach ($attributes as $attribute => &$data) {
      $data = implode(' ', (array) $data);
      $data = $attribute . '="' . htmlspecialchars($data, ENT_QUOTES, 'UTF-8') . '"';
    }
    return $attributes ? ' ' . implode(' ', $attributes) : '';
  }
}
