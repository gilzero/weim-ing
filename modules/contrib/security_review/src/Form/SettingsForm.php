<?php

namespace Drupal\security_review\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Drupal\security_review\SecurityReviewData;
use Drupal\security_review\SecurityReviewHelperTrait;
use Drupal\user\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings page for Security Review.
 */
class SettingsForm extends ConfigFormBase {

  use SecurityReviewHelperTrait;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * The security_review.data service.
   *
   * @var \Drupal\security_review\SecurityReviewData
   */
  protected SecurityReviewData $securityData;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReview;

  /**
   * The security checks plugin manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * Constructs a SettingsForm.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\security_review\SecurityReviewData $security_data
   *   The security_review.data service.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, DateFormatterInterface $dateFormatter, SecurityReviewData $security_data, SecurityReview $security_review, SecurityCheckPluginManager $checkPluginManager) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->dateFormatter = $dateFormatter;
    $this->securityData = $security_data;
    $this->securityReview = $security_review;
    $this->checkPluginManager = $checkPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('date.formatter'),
      $container->get('security_review.data'),
      $container->get('security_review'),
      $container->get('plugin.manager.security_review.security_check')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'security-review-settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get the list of checks.
    $checks = $this->checkPluginManager->getChecks();

    // Get the user roles.
    $roles = Role::loadMultiple();
    $options = [];
    foreach ($roles as $rid => $role) {
      $options[$rid] = $role->label();
    }

    // Notify the user if anonymous users can create accounts.
    $message = '';
    if (in_array(AccountInterface::AUTHENTICATED_ROLE, $this->securityData->untrustedRoles())) {
      $message = $this->t('You have allowed anonymous users to create accounts without approval so the authenticated role defaults to untrusted.');
    }

    // Show the untrusted roles form element.
    $form['untrusted_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Untrusted roles'),
      '#description' => $this->t(
        'Define which roles are for less trusted users. The anonymous role defaults to untrusted. @message Most Security Review checks look for resources usable by untrusted roles.',
        ['@message' => $message]
      ),
      '#options' => $options,
      '#default_value' => $this->securityData->untrustedRoles(),
    ];

    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => TRUE,
    ];

    // Show the logging setting.
    $form['advanced']['logging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log checklist results and skips'),
      '#description' => $this->t('The result of each check and skip can be logged to watchdog for tracking.'),
      '#default_value' => $this->securityReview->isLogging(),
    ];

    // Skipped checks.
    $values = [];
    $options = [];
    $skipped_values = $this->securityReview->getSkipped();
    foreach ($checks as $check) {
      $id = $check->getPluginId();
      // Determine if check is being skipped.
      if (array_key_exists($id, $skipped_values)) {
        $values[] = $id;
        $label = $this->t(
          '@name <em>skipped by UID @uid on @date</em>',
          [
            '@name' => $check->getTitle(),
            '@uid' => $skipped_values[$id]['skipped_by'],
            '@date' => $this->dateFormatter->format($skipped_values[$id]['skipped_on']),
          ]
        );
      }
      else {
        $label = $check->getTitle();
      }
      $options[$check->getPluginId()] = $label;
    }
    $form['advanced']['skip'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Checks to skip'),
      '#description' => $this->t('Skip running certain checks. This can also be set on the <em>Run & review</em> page. It is recommended that you do not skip any checks unless you know the result is wrong or the process times out while running.'),
      '#options' => $options,
      '#default_value' => $values,
    ];

    // Iterate through checklist and get check-specific setting pages.
    foreach ($checks as $check) {
      // Get the check's setting form.
      $check_form = $check->buildConfigurationForm($form, $form_state);
      // If not empty, add it to the form.
      if (!empty($check_form)) {
        // If this is the first non-empty setting page initialize the 'details'.
        if (!isset($form['advanced']['check_specific'])) {
          $form['advanced']['check_specific'] = [
            '#type' => 'details',
            '#title' => $this->t('Check-specific settings'),
            '#open' => FALSE,
            '#tree' => TRUE,
          ];
        }

        // Add the form.
        $sub_form = &$form['advanced']['check_specific'][$check->getPluginId()];

        $title = $check->getTitle();
        // If it's an external check, show its namespace.
        if ($this->getMachineName($check->getNamespace()) !== 'security_review') {
          $title .= $this->t('%namespace', [
            '%namespace' => $check->getNamespace(),
          ]);
        }
        $sub_form = [
          '#type' => 'details',
          '#title' => $title,
          '#open' => FALSE,
          '#tree' => TRUE,
          'form' => $check_form,
        ];
      }
    }

    // Return the finished form.
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Run validation for check-specific settings.
    if (isset($form['advanced']['check_specific'])) {
      foreach ($this->checkPluginManager->getChecks() as $check) {
        $check_form = &$form['advanced']['check_specific'][$check->getPluginId()];
        if (isset($check_form)) {
          $check->validateConfigurationForm($form, $form_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Save the new untrusted roles.
    $untrusted_roles = array_keys(array_filter($form_state->getValue('untrusted_roles')));
    $this->securityReview->setUntrustedRoles($untrusted_roles);

    // Save the new logging setting.
    $logging = $form_state->getValue('logging') == 1;
    $this->securityReview->setLogging($logging);

    // Skip selected checks.
    $new_skipped = [];
    $skipped = array_keys(array_filter($form_state->getValue('skip')));
    $check_specific_values = $form_state->getValue('check_specific');
    foreach ($this->checkPluginManager->getChecks() as $check) {
      if (in_array($check->getPluginId(), $skipped)) {
        $new_skipped[$check->getPluginId()] = [
          'skipped' => TRUE,
          'skipped_by' => $this->currentUser()->id(),
          'skipped_on' => time(),
        ];
      }

      if (isset($form['advanced']['check_specific']) && isset($check_specific_values[$check->getPluginId()])) {
        $check_form_values = $check_specific_values[$check->getPluginId()]['form'];
        // Submit.
        $check->submitConfigurationForm($check_form_values);
      }

    }
    $this->securityReview->setSkipped($new_skipped);

    // Finish submitting the form.
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['security_review.checks'];
  }

}
