<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Drupal\views\Entity\View;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks for Views that do not check access.
 *
 * @SecurityCheck(
 *   id = "views_access",
 *   title = @Translation("Views Access"),
 *   description = @Translation("Checks for Views that do not check access."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Views are access controlled."),
 *   failure_message = @Translation("There are Views that do not provide any
 *   access checks."), info_message = @Translation("Module views is not
 *   enabled."), help = {
 *     @Translation("Views can check if the user is allowed access to the
 *   content. It is recommended that all Views implement some amount of access
 *   control, at a minimum checking for the permission 'access content'."),
 *   }
 * )
 */
class ViewsAccess extends SecurityCheckBase {

  use MessengerTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected ExtensionPathResolver $extensionPathResolver;

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
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->extensionPathResolver = $container->get('extension.path.resolver');
    $instance->moduleHandler = $container->get('module_handler');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float {
    // If views is not enabled return with INFO.
    if (!$this->moduleHandler->moduleExists('views')) {
      $this->createResult(CheckResult::INFO);
      return 1;
    }

    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $ignore_default = $config['ignore_default'] ?? FALSE;
    if (!isset($sandbox['vids'])) {
      try {
        $vids = $this->entityTypeManager->getStorage('view')
          ->getQuery()
          ->accessCheck()
          ->execute();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException) {
        $this->messenger()->addError('Error running views_access check.');
        return 1;
      }
      $sandbox = [];
      $sandbox['vids'] = $vids;
      $sandbox['progress'] = 0;
      $sandbox['max'] = count($vids);
      $sandbox['findings'] = [];
    }

    // 5 at a time.
    $ids = array_slice($sandbox['vids'], $sandbox['progress'], 5);
    $views = View::loadMultiple($ids);
    $findings = [];
    $default = NULL;

    foreach ($views as $view) {
      if ($view->status()) {
        foreach ($view->get('display') as $display_name => $display) {
          $access = $display['display_options']['access'] ?? $default;
          if ($display_name == 'default' && $ignore_default) {
            $default = $access;
          }
          elseif (isset($access) && $access['type'] == 'none') {
            // Access is not controlled for this display.
            $sandbox['findings'][$view->id()][] = $display_name;
          }
        }
      }
      $sandbox['progress']++;
    }

    // Have we finished?
    if ($sandbox['progress'] == $sandbox['max']) {
      $result = CheckResult::SUCCESS;
      if (!empty($sandbox['findings'])) {
        $result = CheckResult::FAIL;
      }
      $this->createResult($result, $sandbox['findings']);

      return 1;
    }

    // Report we are not finished, and provide an estimation of the
    // completion level we reached.
    return $sandbox['progress'] / $sandbox['max'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $ignore_default = $config['ignore_default'] ?? FALSE;
    $form = [];
    $form['ignore_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore default view'),
      '#description' => $this->t('Check to ignore default views.'),
      '#default_value' => $ignore_default,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    if (isset($values['ignore_default'])) {
      $values['ignore_default'] = (bool) $values['ignore_default'];
    }
    $this->securityReview->setCheckSettings($this->pluginId, $values);
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings)) {
      return [];
    }

    $output = $returnString ? '' : [];
    $views_ui_enabled = $this->moduleHandler->moduleExists('views_ui');
    $paragraphs = [];
    $paragraphs[] = $this->t('The following View displays do not check access.');
    $items = [];
    foreach ($findings as $view_id => $displays) {
      $view = View::load($view_id);
      /** @var \Drupal\views\Entity\View $view */

      foreach ($displays as $display) {
        $label = $view->label() . ': ' . $display;
        $items[] = $views_ui_enabled ?
          Link::createFromRoute(
            $label,
            'entity.view.edit_display_form',
            [
              'view' => $view_id,
              'display_id' => $display,
            ]
          ) :
          $label;
      }
    }

    if ($returnString) {
      $output .= implode("", $paragraphs) . implode("", $items);
    }
    else {
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $items,
      ];
    }

    return $output;
  }

}
