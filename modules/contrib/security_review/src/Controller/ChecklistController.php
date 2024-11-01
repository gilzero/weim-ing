<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Url;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Drupal\security_review\SecurityReviewHelperTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The class of the 'Run & Review' page's controller.
 */
class ChecklistController extends ControllerBase {

  use MessengerTrait;
  use SecurityReviewHelperTrait;

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * Security Review plugin Manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReview;

  /**
   * Constructs a ChecklistController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, SecurityReview $security_review, SecurityCheckPluginManager $checkPluginManager) {
    $this->csrfToken = $csrf_token_generator;
    $this->securityReview = $security_review;
    $this->checkPluginManager = $checkPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ChecklistController|static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('csrf_token'),
      $container->get('security_review'),
      $container->get('plugin.manager.security_review.security_check')
    );
  }

  /**
   * Creates the Run & Review page.
   *
   * @return array
   *   The 'Run & Review' page's render array.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function index(): array {
    $run_form = [];

    // If the user has the required permissions, show the RunForm.
    if ($this->currentUser()->hasPermission('run security checks')) {
      // Get the Run form.
      $run_form = $this->formBuilder()
        ->getForm('Drupal\security_review\Form\RunForm');

      // Close the Run form if there are results.
      if ($this->securityReview->getLastRun() > 0) {
        $run_form['run_form']['#open'] = FALSE;
      }
    }

    // Print the results if any.
    if ($this->securityReview->getLastRun() <= 0) {
      $this->messenger()
        ->addWarning($this->t('If this is your first time using the Security Review checklist. Before running the checklist please review the settings page at <a href=":url">admin/reports/security-review/settings</a> to set which roles are untrusted.',
          [':url' => Url::fromRoute('security_review.settings')->toString()]
        ), 'warning');
    }

    return [$run_form, $this->results()];
  }

  /**
   * Creates the results' table.
   *
   * @return array
   *   The render array for the result table.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function results(): array {
    // If there are no results return.
    if ($this->securityReview->getLastRun() <= 0) {
      return [];
    }

    $checks = [];
    foreach ($this->checkPluginManager->getChecks() as $check) {
      // Initialize with defaults.
      $check_info = [
        'message' => $this->t(
          'The check "@name" hasn\'t been run yet.',
          ['@name' => $check->getTitle()]
        ),
        'skipped' => $this->securityReview->isCheckSkipped($check->getPluginId()),
      ];

      // Get last result.
      $last_result = $check->lastResult();
      if ($last_result != NULL) {
        $result_number = $last_result['result'];
        $check_info['result'] = $result_number;
        $check_info['message'] = $check->getStatusMessage($result_number);
      }

      // Determine help link.
      $check_info['help_link'] = Link::createFromRoute(
        'Details',
        'security_review.help',
        [
          'namespace' => $this->getMachineName($check->getNamespace()),
          'title' => $this->getMachineName($check->getTitle()),
        ]
      );

      // Add toggle button.
      $toggle_text = $this->securityReview->isCheckSkipped($check->getPluginId()) ? 'Enable' : 'Skip';
      $check_info['toggle_link'] = Link::createFromRoute($toggle_text,
        'security_review.toggle',
        ['check_id' => $check->getPluginId()],
        ['query' => ['token' => $this->csrfToken->get($check->getPluginId())]]
      );

      // Add to array of completed checks.
      $checks[] = $check_info;
    }

    return [
      '#theme' => 'run_and_review',
      '#date' => $this->securityReview->getLastRun(),
      '#checks' => $checks,
      '#attached' => [
        'library' => ['security_review/run_and_review'],
      ],
    ];
  }

}
