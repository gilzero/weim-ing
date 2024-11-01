<?php

declare(strict_types=1);

namespace Drupal\regcode\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\regcode\RegistrationCodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for managing registration codes.
 */
class RegcodeAdminManageForm extends FormBase {

  /**
   * The registration_code service.
   *
   * @var \Drupal\regcode\RegistrationCodeInterface
   */
  protected $registrationCode;

  /**
   * Constructs a RegcodeAdminManageForm.
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
    return 'regcode_admin_manage';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $operations = [
      RegistrationCodeInterface::CLEAN_TRUNCATE => $this->t('Delete all registration codes'),
      RegistrationCodeInterface::CLEAN_EXPIRED => $this->t('Delete all expired codes'),
      RegistrationCodeInterface::CLEAN_INACTIVE => $this->t('Delete all inactive codes'),
    ];
    $form['regcode_operations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Operations'),
      '#description' => $this->t('This operation cannot be undone.'),
      '#options' => $operations,
    ];

    $form['regcode_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Perform operations'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = $form_state->getValue(['regcode_operations']);
    foreach ($operations as $operation) {
      switch ($operation) {
        case RegistrationCodeInterface::CLEAN_TRUNCATE:
          $this->registrationCode->clean(RegistrationCodeInterface::CLEAN_TRUNCATE);
          $this->messenger()->addStatus($this->t('All registration codes were deleted.'));
          break;

        case RegistrationCodeInterface::CLEAN_EXPIRED:
          $this->registrationCode->clean(RegistrationCodeInterface::CLEAN_EXPIRED);
          $this->messenger()->addStatus($this->t('All expired registration codes were deleted.'));
          break;

        case RegistrationCodeInterface::CLEAN_INACTIVE:
          $this->registrationCode->clean(RegistrationCodeInterface::CLEAN_INACTIVE);
          $this->messenger()->addStatus($this->t('All inactive registration codes were deleted.'));
          break;
      }
    }
  }

}
