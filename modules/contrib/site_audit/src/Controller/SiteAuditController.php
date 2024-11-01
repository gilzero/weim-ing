<?php

namespace Drupal\site_audit\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\site_audit\Plugin\SiteAuditChecklistManager;
use Drupal\site_audit\Renderer\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SiteAuditController.
 *
 * @package Drupal\site_audit\Controller
 */
class SiteAuditController extends ControllerBase {
  /**
  * The Site Audit Report manager.
  *
  * @var \Drupal\site_audit\Plugin\SiteAuditChecklistManager
  */
  private $auditChecklistManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $requestStack;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack object.
   * @param \Drupal\site_audit\Plugin\SiteAuditChecklistManager $auditChecklistManager
   *   The Site Audit Report manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $requestStack, SiteAuditChecklistManager $auditChecklistManager) {
    $this->configFactory = $config_factory;
     $this->auditChecklistManager = $auditChecklistManager;
     $this->requestStack = $requestStack;
   }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('request_stack'),
      $container->get('plugin.manager.site_audit_checklist')
    );
  }

  /**
   * Audit.
   *
   * @return string
   *   Rendered report output.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function audit() {
    $checklistDefinitions = $this->auditChecklistManager->getDefinitions();
    $saved_reports = $this->configFactory->getEditable('site_audit.settings')->get('reports');
    $reports = [];
    // Check to see if there is anything checked
    // the array is empty, so the settings form hasn't been submitted.
    if (!empty($saved_reports) &&
    // They are not all unchecked.
      count(array_flip($saved_reports)) > 1) {
      foreach ($saved_reports as $saved_report) {
        if ($saved_report) {
          $reports[] = $this->auditChecklistManager->createInstance($saved_report);
        }
      }
    }
    // There are no reports selected, so run them all.
    else {
      foreach ($checklistDefinitions as $checklistDefinition) {
        $reports[] = $this->auditChecklistManager->createInstance($checklistDefinition['id']);
      }
    }

    $out = '';

    $renderer = new Html($reports, NULL, ['detail' => TRUE, 'inline' => TRUE, 'uri' => \Drupal::request()->getHost()]);
    return $renderer->render(TRUE);
  }

}
