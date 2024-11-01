<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Checks for specific headers in request.
 *
 * @SecurityCheck(
 *   id = "headers",
 *   title = @Translation("Headers"),
 *   description = @Translation("Checks for specific headers in request."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("All specified headers present."),
 *   failure_message = @Translation("Some specified headers are missing."),
 *   help = {
 *     @Translation("There are some headers a site should set. One such header is X-Frame-Options with a value that will protect the site against clickjacking."),
 *   }
 * )
 */
class Headers extends SecurityCheckBase {

  use LoggerChannelTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->httpClient = $container->get('http_client');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(bool $cli = FALSE): void {
    $result = CheckResult::SUCCESS;
    $findings = [];

    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $additional_headers = $config['headers_to_check'] ?? [];
    $headers = array_merge(['X-Frame-Options'], $additional_headers);
    $host = $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost();
    try {
      $requestHeaders = $this->httpClient->request('GET', $host)->getHeaders();

      foreach ($headers as $header) {
        if (!array_key_exists($header, $requestHeaders)) {
          $findings[] = $header;
        }
      }

    }
    catch (GuzzleException $e) {
      $this->getLogger('security_review')->log(RfcLogLevel::ERROR, $e->getMessage());
      $result = CheckResult::FAIL;
    }

    if (!empty($findings)) {
      $result = CheckResult::FAIL;
    }

    $this->createResult($result, $findings);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $headers_check = $config['headers_to_check'] ?? [];
    $form = [];
    $form['headers_to_check'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Additional headers to check'),
      '#description' => $this->t('Already checking for X-Frame-Options. One per line'),
      '#default_value' => implode("\n", $headers_check),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    $headers_check['headers_to_check'] = [];
    if (!empty($values['headers_to_check'])) {
      $headers_check['headers_to_check'] = preg_split("/\r\n|\n|\r/", $values['headers_to_check']);
    }
    $this->securityReview->setCheckSettings($this->pluginId, $headers_check);
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
    $paragraphs[] = $this->t('The following headers were missing.');

    if ($returnString) {
      foreach ($findings as $header) {
        $output .= "\t" . $header . "\n";
      }
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
