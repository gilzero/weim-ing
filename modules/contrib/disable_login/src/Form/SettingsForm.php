<?php

namespace Drupal\disable_login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The request stack.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                               ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler')
     );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'disable_login.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'disable_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('disable_login.settings');
    $form['disable_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable public access to login page without secret key'),
      '#description' => $this->t('Use this to turn on/off protection on the login page'),
      '#default_value' => $config->get('disable_login'),
    ];
    $default_qs = $config->get('querystring');
    if (empty($default_qs)) {
      $default_qs = 'key';
    }
    $form['querystring'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Querystring name'),
      '#maxlength' => 255,
      '#size' => 128,
      '#description' => $this->t('The name of the querystring to look for the secret key'),
      '#default_value' => $default_qs,
    ];
    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#maxlength' => 255,
      '#size' => 128,
      '#description' => $this->t('The value of the secret key to access the login page'),
      '#default_value' => $config->get('secret'),
    ];
    $secret_key = $config->get('secret');
    // Allow other modules to alter the key with custom logic
    // for example cycle through keys or based on month etc.
    $this->moduleHandler->alter('disable_login_key', $secret_key);
    if ($secret_key != $config->get('secret')) {
      $form['current_secret'] = [
        '#type' => 'item',
        '#title' => $this->t('Altered secret key'),
        '#markup' => $secret_key,
        '#description' => $this->t('The secret key is altered by another module on this site'),
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $settings = $this->config('disable_login.settings');
    $settings
      ->set('disable_login', $form_state->getValue('disable_login'))
      ->set('querystring', $form_state->getValue('querystring'))
      ->set('secret', $form_state->getValue('secret'));
    $settings->save();
  }

}
