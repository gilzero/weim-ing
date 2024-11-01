<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks if PHP files written to the files directory can be executed.
 *
 * @SecurityCheck(
 *   id = "executable_php",
 *   title = @Translation("Executable PHP"),
 *   description = @Translation("Checks if PHP files written to the files directory can be executed."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("PHP files in the Drupal files directory cannot be executed."),
 *   failure_message = @Translation("PHP files in the Drupal files directory can be executed."),
 *   warning_message = @Translation("The .htaccess file in the files directory is writable."),
 *   help = {
 *     @Translation("The Drupal files directory is for user-uploaded files and by default provides some protection against a malicious user executing arbitrary PHP code against your site. Read more about the <a href=""https://drupal.org/node/615888"">risk of PHP code execution on Drupal.org</a>."),
 *   }
 * )
 */
class ExecutablePhp extends SecurityCheckBase {

  use LoggerChannelTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    global $base_url;
    $result = CheckResult::SUCCESS;
    $findings = [];

    // Set up test file data.
    $message = 'Security review test ' . date('Ymdhis');
    $content = "<?php\necho '" . $message . "';";
    $file_path = PublicStream::basePath() . '/security_review_test.php';

    // Create the test file.
    if ($test_file = @fopen('./' . $file_path, 'w')) {
      fwrite($test_file, $content);
      fclose($test_file);
    }

    // Try to access the test file.
    try {
      $response = $this->httpClient->get($base_url . '/' . $file_path);
      if ($response->getStatusCode() == 200 && $response->getBody()->getContents() === $message) {
        $result = CheckResult::FAIL;
        $findings[] = 'executable_php';
      }
    }
    catch (RequestException | GuzzleException) {
      // Access was correctly denied to the file.
      $this->getLogger('security_review')
        ->info('Error executable_php, access was denied to the file.');
    }

    // Remove the test file.
    if (file_exists('./' . $file_path)) {
      @unlink('./' . $file_path);
    }

    // Only perform .htaccess checks if the webserver is Apache.
    $str = isset($_SERVER['SERVER_SOFTWARE']) ? substr($_SERVER['SERVER_SOFTWARE'], 0, 6) : '';
    if ($str == 'Apache') {
      // Check for presence of the .htaccess file and if the contents are
      // correct.
      $htaccess_path = PublicStream::basePath() . '/.htaccess';
      if (!file_exists($htaccess_path)) {
        $result = CheckResult::FAIL;
        $findings[] = 'missing_htaccess';
      }
      else {
        // Check whether the contents of .htaccess are correct.
        $contents = file_get_contents($htaccess_path);
        $expected = FileSecurity::htaccessLines(FALSE);

        // Trim each line separately then put them back together.
        $contents = implode("\n", array_map('trim', explode("\n", trim($contents))));
        $expected = implode("\n", array_map('trim', explode("\n", trim($expected))));

        if ($contents !== $expected) {
          $result = CheckResult::FAIL;
          $findings[] = 'incorrect_htaccess';
        }

        // Check whether .htaccess is writable.
        if (!$cli) {
          $writable_htaccess = is_writable($htaccess_path);
        }
        else {
          $writable = $this->securitySettings->findWritableFiles([$htaccess_path], TRUE);
          $writable_htaccess = !empty($writable);
        }

        if ($writable_htaccess) {
          $findings[] = 'writable_htaccess';
          if ($result !== CheckResult::FAIL) {
            $result = CheckResult::WARN;
          }
        }
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
    foreach ($findings as $label) {
      switch ($label) {
        case 'executable_php':
          $paragraphs[] = $this->t('Security Review was able to execute a PHP file written to your files directory.');
          break;

        case 'missing_htaccess':
          $directory = PublicStream::basePath();
          $paragraphs[] = $this->t("The .htaccess file is missing from the files directory at @path", ['@path' => $directory]);
          $paragraphs[] = $this->t("Note, if you are using a webserver other than Apache you should consult your server's documentation on how to limit the execution of PHP scripts in this directory.");
          break;

        case 'incorrect_htaccess':
          $paragraphs[] = $this->t("The .htaccess file exists but does not contain the correct content. It is possible it's been maliciously altered.");
          break;

        case 'writable_htaccess':
          $paragraphs[] = $this->t("The .htaccess file is writable which poses a risk should a malicious user find a way to execute PHP code they could alter the .htaccess file to allow further PHP code execution.");
          break;
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
