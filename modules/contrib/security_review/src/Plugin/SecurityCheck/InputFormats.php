<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks for vulnerabilities related to input formats.
 *
 * @SecurityCheck(
 *   id = "input_formats",
 *   title = @Translation("Text formats"),
 *   description = @Translation("Checks for formats that either do not have HTML filter that can be used by untrusted users, or if they do check if unsafe tags are allowed."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Untrusted users are not allowed to input dangerous HTML tags."),
 *   failure_message = @Translation("Untrusted users are allowed to input dangerous HTML tags."),
 *   info_message = @Translation("Module filter is not enabled."),
 *   help = {
 *     @Translation("Certain HTML tags can allow an attacker to take control of your site. Drupal's input format system makes use of a set filters to run on incoming text. The 'HTML Filter' strips out harmful tags and Javascript events and should be used on all formats accessible by untrusted users."),
 *   }
 * )
 */
class InputFormats extends SecurityCheckBase {

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
    // If filter is not enabled return with INFO.
    if (!$this->moduleHandler->moduleExists('filter')) {
      $this->createResult(CheckResult::INFO);
    }
    else {
      $result = CheckResult::SUCCESS;
      $findings = [];

      $formats = filter_formats();
      $untrusted_roles = $this->securitySettings->untrustedRoles();
      $unsafe_tags = $this->securitySettings->unsafeTags();

      foreach ($formats as $format) {
        $format_roles = array_keys(filter_get_roles_by_format($format));
        $intersect = array_intersect($format_roles, $untrusted_roles);

        if (!empty($intersect)) {
          // Untrusted users can use this format.
          // Check format for enabled HTML filter.
          $filter_html_enabled = FALSE;
          if ($format->filters()->has('filter_html')) {
            $filter_html_enabled = $format->filters('filter_html')
              ->getConfiguration()['status'];
          }
          $filter_html_escape_enabled = FALSE;
          if ($format->filters()->has('filter_html_escape')) {
            $filter_html_escape_enabled = $format->filters('filter_html_escape')
              ->getConfiguration()['status'];
          }

          if ($filter_html_enabled) {
            $filter = $format->filters('filter_html');

            // Check for unsafe tags in allowed tags.
            $allowed_tags = array_keys($filter->getHTMLRestrictions()['allowed']);
            foreach (array_intersect($allowed_tags, $unsafe_tags) as $tag) {
              // Found an unsafe tag.
              $findings['tags'][$format->id()] = $tag;
            }
          }
          elseif (!$filter_html_escape_enabled) {
            // Format is usable by untrusted users but does not contain the HTML
            // Filter or the HTML escape.
            $findings['formats'][$format->id()] = $format->label();
          }
        }
      }

      if (!empty($findings)) {
        $result = CheckResult::FAIL;
      }
      $this->createResult($result, $findings);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = [];
    $paragraphs = [];
    if (!empty($findings['tags'])) {
      $paragraphs[] = Link::createFromRoute(
        $this->t('Review your text formats.'),
        'filter.admin_overview'
      );
      $paragraphs[] = $this->t('It is recommended you remove the following tags from roles accessible by untrusted users.');
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $findings['tags'],
      ];
    }

    if (!empty($findings['formats'])) {
      $paragraphs[] = $this->t('The following formats are usable by untrusted roles and do not filter or escape allowed HTML tags.');
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $findings['formats'],
      ];
    }

    if ($returnString) {
      $output = '';
      if (!empty($findings['tags'])) {
        $output .= $this->t('Tags') . "\n";
        foreach ($findings['tags'] as $tag) {
          $output .= "\t$tag\n";
        }
      }

      if (!empty($findings['formats'])) {
        $output .= $this->t('Formats') . "\n";
        foreach ($findings['formats'] as $format) {
          $output .= "\t$format\n";
        }
      }
    }

    return $output;
  }

}
