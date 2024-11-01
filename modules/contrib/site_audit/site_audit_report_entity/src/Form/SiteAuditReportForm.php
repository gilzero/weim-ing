<?php

namespace Drupal\site_audit_report_entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\site_audit\Plugin\SiteAuditChecklistManager;
use Drupal\site_audit\Renderer\Console;
use Drupal\site_audit\Renderer\Html;
use Drupal\site_audit\Renderer\Json;
use Drupal\site_audit\Renderer\Markdown;
use Drupal\site_audit_report_entity\Entity\SiteAuditReport;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the site audit report entity edit forms.
 */
class SiteAuditReportForm extends ContentEntityForm {

  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $build_info = $form_state->getBuildInfo();
    if ($this->getFormId() == 'site_audit_report_add_form') {
      $form['report'] = [
        '#type' => 'details',
        '#title' => $this->t('Site Audit'),
        '#open' => FALSE,
      ];
      $form['report']['audit'] = [
        '#markup' => SiteAuditReport::audit(),
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = parent::buildEntity($form, $form_state);
    $entity->submit_remote = $form_state->getValue('submit_remote');

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New site audit report %label has been created.', $message_arguments));
        $this->logger('site_audit_report_entity')->notice('Created new site audit report %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The site audit report %label has been updated.', $message_arguments));
        $this->logger('site_audit_report_entity')->notice('Updated site audit report %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.site_audit_report.canonical', ['site_audit_report' => $entity->id()]);

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function send(array $form, FormStateInterface $form_state) {

    // Find the module that defines the chosen send method and invoke HOOK_site_audit_send_send_METHOD()
    $send_method = $form_state->getValue('send_method');
    \Drupal::moduleHandler()->invokeAllWith('site_audit_send_send_methods', function (callable $hook, string $module) use ($form_state, $send_method) {
      $hook = "{$module}_site_audit_send_send_{$send_method}";
      $hook($this->entity, $this->operation, $form_state);
    });
  }
}
