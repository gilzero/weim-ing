<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Responsible for handling the toggle links on the Run & Review page.
 */
class ToggleController extends ControllerBase {

  /**
   * The CSRF Token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected CsrfTokenGenerator $csrfToken;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The security checks plugin manager.
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
   * Constructs a ToggleController.
   *
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrf_token_generator
   *   The CSRF Token generator.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   */
  public function __construct(CsrfTokenGenerator $csrf_token_generator, RequestStack $request_stack, MessengerInterface $messenger, SecurityCheckPluginManager $checkPluginManager, SecurityReview $security_review) {
    $this->csrfToken = $csrf_token_generator;
    $this->request = $request_stack->getCurrentRequest();
    $this->messenger = $messenger;
    $this->checkPluginManager = $checkPluginManager;
    $this->securityReview = $security_review;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ToggleController|static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('csrf_token'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('plugin.manager.security_review.security_check'),
      $container->get('security_review')
    );
  }

  /**
   * Handles check toggling.
   *
   * @param string $check_id
   *   The ID of the check.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The response.
   */
  public function index(string $check_id): RedirectResponse {
    // Validate token.
    $token = $this->request->query->get('token');
    if ($this->csrfToken->validate($token, $check_id)) {
      // Toggle.
      $check = $this->checkPluginManager->getCheckById($check_id);
      $skipped = $this->securityReview->isCheckSkipped($check->getPluginId());

      $plugin_id = $check->getPluginId();
      if (!empty($skipped)) {
        $this->securityReview->enable($plugin_id);
        $skipped = FALSE;
      }
      else {
        $this->securityReview->skip($plugin_id);
        $skipped = TRUE;
      }

      // Set message.
      if ($skipped) {
        $this->messenger()
          ->addMessage($this->t('@name check skipped.', ['@name' => $check->getTitle()]));
      }
      else {
        $this->messenger()
          ->addMessage($this->t('@name check no longer skipped.', ['@name' => $check->getTitle()]));
      }
    }

    // Redirect back to Run & Review.
    return $this->redirect('security_review');
  }

}
