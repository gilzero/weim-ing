<?php

namespace Drupal\security_review\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Link;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\security_review\SecurityCheckPluginManager;
use Drupal\security_review\SecurityReview;
use Drupal\security_review\SecurityReviewHelperTrait;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The class of the Help pages' controller.
 */
class HelpController extends ControllerBase {

  use SecurityReviewHelperTrait;
  use LoggerChannelTrait;

  /**
   * The security_review service.
   *
   * @var \Drupal\security_review\SecurityReview
   */
  protected SecurityReview $securityReview;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  private DateFormatterInterface $dateFormatter;

  /**
   * The security checks plugin manager.
   *
   * @var \Drupal\security_review\SecurityCheckPluginManager
   */
  protected SecurityCheckPluginManager $checkPluginManager;

  /**
   * Constructs a HelpController.
   *
   * @param \Drupal\security_review\SecurityReview $security_review
   *   The security_review service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\security_review\SecurityCheckPluginManager $checkPluginManager
   *   Plugin manager for Security Checks.
   */
  public function __construct(SecurityReview $security_review, DateFormatterInterface $dateFormatter, SecurityCheckPluginManager $checkPluginManager) {
    // Store the dependencies.
    $this->securityReview = $security_review;
    $this->dateFormatter = $dateFormatter;
    $this->checkPluginManager = $checkPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): HelpController|static {
    // @phpstan-ignore-next-line
    return new static(
      $container->get('security_review'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.security_review.security_check')
    );
  }

  /**
   * Serves as an entry point for the help pages.
   *
   * @param string|null $namespace
   *   The namespace of the check (null if general page).
   * @param string|null $title
   *   The name of the check.
   *
   * @return array
   *   The requested help page.
   */
  public function index(?string $namespace, ?string $title): array {
    // If no namespace is set, print the general help page.
    if ($namespace === NULL || $title === NULL) {
      return $this->generalHelp();
    }

    // Print check-specific help.
    return $this->checkHelp($namespace, $title);
  }

  /**
   * Returns the general help page.
   *
   * @return array
   *   The general help page.
   */
  private function generalHelp(): array {
    $paragraphs = [];

    // Print the general help.
    $paragraphs[] = $this->t('You should take the security of your site very seriously. Fortunately, Drupal is fairly secure by default. The Security Review module automates many of the easy-to-make mistakes that render your site insecure, however it does not automatically make your site impenetrable. You should give care to what modules you install and how you configure your site and server. Be mindful of who visits your site and what features you expose for their use.');
    $paragraphs[] = $this->t('You can read more about securing your site in the <a href="https://drupal.org/security/secure-configuration">drupal.org handbooks</a> and on <a href="https://crackingdrupal.com">CrackingDrupal.com</a>. There are also additional modules you can install to secure or protect your site. Be aware though that the more modules you have running on your site the greater (usually) attack area you expose.');
    $paragraphs[] = $this->t('<a href="https://drupal.org/node/382752">Drupal.org Handbook: Introduction to security-related contrib modules</a>');

    // Print the list of security checks with links to their help pages.
    $checks = [];
    foreach ($this->checkPluginManager->getChecks() as $check) {
      // Get the namespace array's reference.
      $check_namespace = &$checks[$check->getNamespace()];

      // Set up the namespace array if not set.
      if (!isset($check_namespace)) {
        $check_namespace['namespace'] = $check->getNamespace();
        $check_namespace['check_links'] = [];
      }

      // Add the link pointing to the check-specific help.
      $check_namespace['check_links'][] = Link::createFromRoute(
        $this->t('@title', ['@title' => $check->getTitle()]),
        'security_review.help',
        [
          'namespace' => $this->getMachineName($check->getNamespace()),
          'title' => $this->getMachineName($check->getTitle()),
        ]
      );
    }

    return [
      '#theme' => 'general_help',
      '#paragraphs' => $paragraphs,
      '#checks' => $checks,
    ];
  }

  /**
   * Returns a check-specific help page.
   *
   * @param string $namespace
   *   The namespace of the check.
   * @param string $title
   *   The name of the check.
   *
   * @return array
   *   The check's help page.
   */
  private function checkHelp(string $namespace, string $title): array {
    // Get the requested check.
    $check = $this->checkPluginManager->getCheck($namespace, $title);

    // If the check doesn't exist, throw 404.
    if ($check == NULL) {
      throw new NotFoundHttpException();
    }

    // Print the help page.
    $output = [];
    $output[] = $check->getHelp();

    // If the check is skipped print the skip message, else print the
    // evaluation.
    $skipped_info = $this->securityReview->isCheckSkipped($check->getPluginId());
    if ($this->securityReview->isCheckSkipped($check->getPluginId())) {

      if ($skipped_info['skipped_by'] !== NULL) {
        $user_object = User::load($skipped_info['skipped_by']);
        try {
          $user = $user_object->toLink()->toString();
        }
        catch (EntityMalformedException) {
          $this->getLogger('security_review')->log(RfcLogLevel::ERROR, $this->t('Error getting link to user: @user', ['@user' => $user_object->getAccountName()]));
          $user = 'Error';
        }
      }
      else {
        $user = 'Anonymous';
      }

      $skip_message = $this->t(
        'Check marked for skipping on @date by @user',
        [
          '@date' => $this->dateFormatter->format($skipped_info['skipped_on']),
          '@user' => $user,
        ]
      );

      $output[] = [
        '#type' => 'markup',
        '#markup' => "<p>$skip_message</p>",
      ];
    }
    else {
      // Evaluate last result, if any.
      $last_result = $check->lastResult();
      // Separator.
      $output[] = [
        '#type' => 'markup',
        '#markup' => '<div />',
      ];

      // Evaluation page.
      $output[] = $check->getDetails($last_result['findings'] ?: [], $last_result['hushed']);
    }

    // Return the completed page.
    return $output;
  }

}
