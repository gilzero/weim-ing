<?php

declare(strict_types=1);

namespace Drupal\regcode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\regcode\RegistrationCodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore alphanum hexadec

/**
 * Form for creation of registration codes.
 */
class RegcodeAdminCreateForm extends FormBase {

  /**
   * The registration_code service.
   *
   * @var \Drupal\regcode\RegistrationCodeInterface
   */
  protected $registrationCode;

  /**
   * Constructs a RegcodeAdminCreateForm.
   *
   * @param \Drupal\regcode\RegistrationCodeInterface $registration_code
   *   The registration_code service.
   */
  public function __construct(RegistrationCodeInterface $registration_code) {
    $this->registrationCode = $registration_code;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('registration_code'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'regcode_admin_create';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $regcode_config = $this->config('regcode.settings');

    // Basics.
    $form = [];
    $form['regcode_create'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General settings'),
    ];

    $form['regcode_create']['regcode_create_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration code'),
      '#description' => $this->t('Leave blank to have code generated. Used as prefix when <em>Number of codes</em> is greater than 1.'),
    ];

    $form['regcode_create']['regcode_create_maxuses'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum uses'),
      '#description' => $this->t('How many times this code can be used to register (enter 0 for unlimited).'),
      '#min' => 0,
      '#size' => 10,
      '#default_value' => 1,
      '#required' => TRUE,
    ];

    $form['regcode_create']['regcode_create_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Code size'),
      '#min' => 1,
      '#size' => 10,
      '#default_value' => 12,
    ];

    $form['regcode_create']['regcode_create_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format of the generated codes'),
      '#options' => [
        'alpha' => $this->t('Letters'),
        'numeric' => $this->t('Numbers'),
        'alphanum' => $this->t('Numbers & Letters'),
        'hexadec' => $this->t('Hexadecimal'),
      ],
      '#default_value' => $regcode_config->get('regcode_generate_format'),
    ];

    $form['regcode_create']['regcode_create_case'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert generated codes to uppercase'),
      '#default_value' => $regcode_config->get('regcode_generate_case'),
    ];

    $form['regcode_create']['regcode_create_begins'] = [
      '#type' => 'date',
      '#title' => $this->t('Active from'),
      '#description' => $this->t('When this code should activate (leave blank to activate immediately).'),
      '#date_date_format' => 'Y-m-d',
      '#default_value' => '',
      '#size' => 10,
    ];

    $form['regcode_create']['regcode_create_expires'] = [
      '#type' => 'date',
      '#title' => $this->t('Expires on'),
      '#description' => $this->t('When this code should expire (leave blank for no expiry).'),
      '#date_date_format' => 'Y-m-d',
      '#default_value' => '',
      '#size' => 10,
    ];

    // Bulk.
    $form['regcode_create_bulk'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Bulk settings'),
      '#description' => $this->t('Multiple codes can be created at once, use these settings to configure the code generation.'),
    ];

    $form['regcode_create_bulk']['regcode_create_number'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of codes to generate'),
      '#min' => 1,
      '#size' => 10,
      '#default_value' => 1,
    ];

    $form['regcode_create_bulk_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create codes'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $code = new \stdClass();

    // Convert dates into timestamps.
    foreach (['begins', 'expires'] as $field) {
      $value = $form_state->getValue(['regcode_create_' . $field]);
      if (!empty($value)) {
        $code->$field = strtotime($value);
      }
    }

    // Grab form values.
    $code->is_active = 1;
    $code->maxuses = $form_state->getValue(['regcode_create_maxuses']);

    // Start creating codes.
    for ($i = 0; $i < intval($form_state->getValue(['regcode_create_number'])); $i++) {
      $code->code = $form_state->getValue(['regcode_create_code']);

      // Generate a code.
      if (empty($code->code) || intval($form_state->getValue(['regcode_create_number'])) > 1) {
        $gen = $this->registrationCode->generate(intval($form_state->getValue(['regcode_create_length'])), $form_state->getValue(['regcode_create_format']), (bool) $form_state->getValue(['regcode_create_case']));
        $code->code .= $gen;
      }

      // Save code.
      if ($this->registrationCode->save($code, RegistrationCodeInterface::MODE_SKIP)) {
        $this->messenger()->addStatus($this->t('Created registration code (%code)', [
          '%code' => $code->code,
        ]));
      }
      else {
        $this->messenger()->addWarning($this->t('Unable to create code (%code) as code already exists', [
          '%code' => $code->code,
        ]));
      }
    }
  }

}
