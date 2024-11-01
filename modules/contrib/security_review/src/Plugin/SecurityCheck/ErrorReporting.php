<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a security check that checks the error reporting setting.
 *
 * @SecurityCheck(
 *   id = "error_reporting",
 *   title = @Translation("Error reporting"),
 *   description = @Translation("Defines a security check that checks the error reporting setting."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Error reporting set to log only."),
 *   failure_message = @Translation("Errors are written to the screen."),
 *   info_message = @Translation("Errors are managed in the ""verbose"" way from local settings overrides."),
 *   help = {
 *     @Translation("As a form of hardening your site you should avoid information disclosure. Drupal by default prints errors to the screen and writes them to the log. Error messages disclose the full path to the file where the error occurred."),
 *   }
 * )
 */
class ErrorReporting extends SecurityCheckBase {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Get the error level.
    $error_level = $this->configFactory->get('system.logging')->get('error_level');

    // Determine the result.
    if ($error_level === 'verbose') {
      $result = CheckResult::INFO;
    }
    elseif ($error_level !== 'hide') {
      $result = CheckResult::FAIL;
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, ['level' => $error_level]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];
    if (isset($findings['level'])) {
      if ($findings['level'] === 'verbose') {
        $paragraphs[] = $this->t('You are probably using error report settings overridden from settings.local.php file');
      }
      else {
        $paragraphs[] = $this->t('You have error reporting set to both the screen and the log.');
        $paragraphs[] = Link::createFromRoute(
          $this->t('Alter error reporting settings.'),
          'system.logging_settings'
        );
      }
    }

    if ($returnString) {
      $output .= implode("", $paragraphs);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
      ];
    }

    return $output;
  }

}
