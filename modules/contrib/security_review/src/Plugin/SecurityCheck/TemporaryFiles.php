<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check for sensitive temporary files like settings.php.
 *
 * @SecurityCheck(
 *   id = "temporary_files",
 *   title = @Translation("Temporary setting files"),
 *   description = @Translation("Check for sensitive temporary files like settings.php."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("No sensitive temporary files were found."),
 *   failure_message = @Translation("Sensitive temporary files were found on your files system."),
 *   help = {
 *     @Translation("Some file editors create temporary copies of a file that can be left on the file system. A copy of a sensitive file like Drupal's settings.php may be readable by a malicious user who could use that information to further attack a site."),
 *   }
 * )
 */
class TemporaryFiles extends SecurityCheckBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Get list of files from the site directory.
    $files = [];
    $site_path = $this->securitySettings->sitePath() . '/';
    $dir = scandir($site_path);
    foreach ($dir as $file) {
      // Set full path to only files.
      if (!in_array($file, ['.', '..'], TRUE) && !is_dir($file)) {
        $files[] = $site_path . $file;
      }
    }
    $this->moduleHandler->alter('security_review_temporary_files', $files);

    // Analyze the files' names.
    foreach ($files as $path) {
      $matches = [];
      if (file_exists($path) && preg_match('/.*(~|\.sw[op]|\.bak|\.orig|\.save)$/', $path, $matches) !== FALSE && !empty($matches)) {
        // Found a temporary file.
        $findings[] = $path;
      }
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, $findings);
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
    $paragraphs[] = $this->t("The following are extraneous files in your Drupal installation that can probably be removed. You should confirm you have saved any of your work in the original files prior to removing these.");

    if ($returnString) {
      $output .= implode("", $paragraphs);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $findings,
      ];
    }

    return $output;
  }

}
