<?php

namespace Drupal\security_review\Form;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides implementation for the Run form.
 */
class RunForm extends FormBase {

  use LoggerChannelTrait;

  /**
   * The security checks plugin manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * Security review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReview;

  /**
   * Constructs a RunForm.
   *
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security review service.
   */
  public function __construct(SecurityCheckPluginManager $checkPluginManager, SecurityReview $security_review) {
    $this->checkPluginManager = $checkPluginManager;
    $this->securityReview = $security_review;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): RunForm|static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('plugin.manager.security_review.security_check'),
      $container->get('security_review'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'security-review-run';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (!$this->currentUser()->hasPermission('run security checks')) {
      return [];
    }

    $form['run_form'] = [
      '#type' => 'details',
      '#title' => $this->t('Run'),
      '#description' => $this->t('Click the button below to run the security checklist and review the results.') . '<br />',
      '#open' => TRUE,
    ];

    $form['run_form']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run checklist'),
    ];

    // Return the finished form.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $batch = [
      'operations' => [],
      'finished' => '_security_review_batch_run_finished',
      'title' => $this->t('Performing Security Review'),
      'init_message' => $this->t('Security Review is starting.'),
      'progress_message' => $this->t('Progress @current out of @total.'),
      'error_message' => $this->t('An error occurred. Rerun the process or consult the logs.'),
    ];

    foreach ($this->checkPluginManager->getDefinitions() as $check) {
      try {
        if (!$this->securityReview->isCheckSkipped($check['id'])) {
          $plugin = $this->checkPluginManager->createInstance($check['id']);
          $batch['operations'][] = [
            '_security_review_batch_run_op',
            [$plugin],
          ];
        }
      }
      catch (PluginException) {
        $this->getLogger('security_review')->log(RfcLogLevel::ERROR, $this->t('Error creating instance for plugin with ID: @id', ['@id' => $check['id']]));
      }
    }

    batch_set($batch);
  }

}
