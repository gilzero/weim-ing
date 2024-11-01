<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Check that files aren't writeable by the server.
 *
 * @SecurityCheck(
 *   id = "file_permissions",
 *   title = @Translation("File permissions"),
 *   description = @Translation("Check that files aren't writeable by the server."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Drupal installation files and directories (except required) are not writable by the server."),
 *   failure_message = @Translation("Some files and directories in your install are writable by the server."),
 *   info_message = @Translation("The test cannot be run on this system."),
 *   help = {
 *     @Translation("It is dangerous to allow the web server to write to files inside the document root of your server. Doing so could allow Drupal to write files that could then be executed. An attacker might use such a vulnerability to take control of your site. An exception is the Drupal files, private files, and temporary directories which Drupal needs permission to write to in order to provide features like file attachments."),
 *     @Translation("In addition to inspecting existing directories, this test attempts to create and write to your file system. Look in your security_review module directory on the server for files named file_write_test.YYYYMMDDHHMMSS and for a file called IGNOREME.txt which gets a timestamp appended to it if it is writeable."),
 *     @Translation("In addition to inspecting existing directories, this test attempts to create and write to your file system. Look in your security_review module directory on the server for:<ul><li>A file named: file_write_test.YYYYMMDDHHMMSS<ul><li>If this file exists the web server can write files to the security_review module directory and perhaps to other directories. You should correct the file permissions on all code directories of your Drupal installation.</li></ul></li><li>Open the file IGNOREME.txt.<ul><li>If a timestamp is appended at the end of it. That means the web server has permission to write to your files. This is insecure and the permissions should be corrected.</li></ul></li></ul>"),
 *     @Translation("Read more about file system permissions in the handbooks. <a href=""https://drupal.org/node/244924"">https://drupal.org/node/244924</a>"),
 *   }
 * )
 */
class FilePermissions extends SecurityCheckBase {

  /**
   * The assets stream.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperInterface
   *
   * @see https://www.drupal.org/project/drupal/issues/3027639
   * Drupal core issue to add this. Planned to be released in Drupal core 10.1.
   */
  protected StreamWrapperInterface $assetsStream;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

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

    // Condition may be removed when we drop support for Drupal <10.2.
    if ($container->has('stream_wrapper.assets')) {
      $instance->assetsStream = $container->get('stream_wrapper.assets');
    }
    $instance->fileSystem = $container->get('file_system');
    $instance->moduleHandler = $container->get('module_handler');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $hushed_files = $this->getRealPaths($config['hushed_files'] ?? []);

    $parsed = NULL;
    $ignore = array_unique(array_merge($this->getIgnoreList(), $hushed_files));
    $file_list = $this->getFileList('.', $parsed, $ignore);
    $findings = $this->securitySettings->findWritableFiles($file_list, $cli);

    // Try creating or appending files.
    // Assume it doesn't work.
    $create_status = FALSE;
    $append_status = FALSE;

    if (!$cli) {
      $append_message = $this->t("Your web server should not be able to write to your modules directory. This is a security vulnerable. Consult the Security Review file permissions check help for mitigation steps.");
      $directory = $this->moduleHandler->getModule('security_review')->getPath();

      // Write a file with the timestamp.
      $file = './' . $directory . '/file_write_test.' . date('Ymdhis');
      if ($file_create = @fopen($file, 'w')) {
        $create_status = fwrite($file_create, date('Ymdhis') . ' - ' . $append_message . "\n");
        fclose($file_create);
        unlink($file);
      }

      // Try to append to our IGNOREME file.
      $file = './' . $directory . '/IGNOREME.txt';
      if ($file_append = @fopen($file, 'a')) {
        $append_status = fwrite($file_append, date('Ymdhis') . ' - ' . $append_message . "\n");
        fclose($file_append);
      }
    }

    if (!empty($findings) || $create_status || $append_status) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, $findings, NULL, $hushed_files);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $hushed_files = $config['hushed_files'] ?? [];
    $form = [];
    $form['hushed_files'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hush certain directories'),
      '#description' => $this->t('Files to be skipped in future runs. Enter one value per line'),
      '#default_value' => implode("\n", $hushed_files),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    $hushed['hushed_files'] = [];
    if (!empty($values['hushed_files'])) {
      $hushed['hushed_files'] = preg_split("/\r\n|\n|\r/", $values['hushed_files']);
    }
    $this->securityReview->setCheckSettings($this->pluginId, $hushed);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings) && empty($hushed)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $paragraphs = [];
    $paragraphs[] = $this->t('The following files and directories appear to be writeable by your web server.');
    $paragraphs[] = $this->t('In most cases you can fix this by simply altering the file permissions or ownership. If you have command-line access to your host try running "chmod 644 [file path]" where [file path] is one of the following paths (relative to your webroot). For more information consult the <a href="https://drupal.org/node/244924">Drupal.org handbooks on file permissions</a>.');
    $paragraphs[] = $this->t('If you have shared hosting, your options will be severely limited and you should check directly with your hosting provider. Whatever the method, the end result should be such that the web server itself cannot write to any of the Drupal core directories or individual files');

    if ($returnString) {
      $output = $this->t('Writable files:') . "\n";
      foreach ($findings as $file) {
        $output .= "\t" . $file . "\n";
      }
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $findings,
        '#hushed_items' => $hushed,
      ];
    }

    return $output;
  }

  /**
   * Scans a directory recursively and returns the files and directories inside.
   *
   * @param string $directory
   *   The directory to scan.
   * @param string[] $parsed
   *   Array of already parsed real paths.
   * @param string[] $ignore
   *   Array of file names to ignore.
   *
   * @return string[]
   *   The items found.
   */
  protected function getFileList(string $directory, array &$parsed = NULL, array &$ignore = NULL): array {
    // Initialize $parsed and $ignore arrays.
    if ($parsed === NULL) {
      $parsed = [realpath($directory)];
    }
    if ($ignore === NULL) {
      $ignore = $this->getIgnoreList();
    }

    // Start scanning.
    $items = [];
    if ($handle = opendir($directory)) {
      while (($file = readdir($handle)) !== FALSE) {
        // Don't check hidden files or ones we said to ignore.
        $path = $directory . "/" . $file;
        if ($file[0] != "." && !in_array($file, $ignore) && !in_array(realpath($path), $ignore)) {
          if (is_dir($path) && !in_array(realpath($path), $parsed)) {
            $parsed[] = realpath($path);
            $items = array_merge($items, $this->getFileList($path, $parsed, $ignore));
          }
          $items[] = preg_replace("/\/\//", "/", $path);
        }
      }
      closedir($handle);
    }

    return $items;
  }

  /**
   * Get the sites.php file.
   *
   * @return array
   *   Sites file.
   */
  private function getSites(): array {
    $sites = [];
    if (file_exists(DRUPAL_ROOT . '/sites/sites.php')) {
      include DRUPAL_ROOT . '/sites/sites.php';
    }
    return $sites;
  }

  /**
   * Returns an array of relative and canonical paths to ignore.
   *
   * @return string[]
   *   List of relative and canonical file paths to ignore.
   */
  protected function getIgnoreList(): array {
    $file_path = PublicStream::basePath();
    $ignore = ['..', 'CVS', '.git', '.svn', '.bzr', realpath($file_path)];

    foreach ($this->getSites() as $site) {
      $ignore[] = realpath(PublicStream::basePath('sites/' . $site));
    }

    $ignore = array_unique($ignore);

    // Add temporary files directory if it's set.
    $temp_path = $this->fileSystem->getTempDirectory();
    if (!empty($temp_path)) {
      $ignore[] = realpath('./' . rtrim($temp_path, '/'));
    }

    // Add private files directory if it's set.
    $private_files = PrivateStream::basePath();
    if (!empty($private_files)) {
      // Remove leading slash if set.
      if (strrpos($private_files, '/') !== FALSE) {
        $private_files = substr($private_files, strrpos($private_files, '/') + 1);
      }
      $ignore[] = $private_files;
    }

    // If the assets stream wrapper service exists, get the assets path.
    if (isset($this->assetsStream)) {
      $assetsPath = $this->assetsStream->basePath();
      $ignore[] = realpath($assetsPath);
    }

    $this->moduleHandler->alter('security_review_file_ignore', $ignore);
    return $ignore;
  }

  /**
   * Turn hushed ignore list into array with real paths.
   *
   * @param array $ignore_list
   *   Ignore list without real paths.
   *
   * @return array
   *   Array of ignored files with real path.
   */
  private function getRealPaths(array $ignore_list): array {
    $real_paths = [];
    foreach ($ignore_list as $item) {
      $real_paths[] = realpath($item);
    }
    return $real_paths;
  }

}
