<?php

namespace Drupal\site_audit_report_entity\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\site_audit\Plugin\SiteAuditCheckBase;
use Drupal\site_audit\Renderer\Console;
use Drupal\site_audit\Renderer\Html;
use Drupal\site_audit\Renderer\Json;
use Drupal\site_audit\Renderer\Markdown;
use Drupal\site_audit_report_entity\SiteAuditReportInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the site audit report entity class.
 *
 * @ContentEntityType(
 *   id = "site_audit_report",
 *   label = @Translation("Site Audit Report"),
 *   label_collection = @Translation("Site Audit Reports"),
 *   label_singular = @Translation("site audit report"),
 *   label_plural = @Translation("site audit reports"),
 *   label_count = @PluralTranslation(
 *     singular = "@count site audit reports",
 *     plural = "@count site audit reports",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\site_audit_report_entity\SiteAuditReportListBuilder",
 *     "views_data" = "Drupal\site_audit_report_entity\Entity\SiteAuditReportViewsData",
 *     "access" = "Drupal\site_audit_report_entity\SiteAuditReportAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\site_audit_report_entity\Form\SiteAuditReportForm",
 *       "edit" = "Drupal\site_audit_report_entity\Form\SiteAuditReportForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "site_audit_report",
 *   data_table = "site_audit_report_field_data",
 *   revision_table = "site_audit_report_revision",
 *   revision_data_table = "site_audit_report_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer site audit report",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "uri" = "uri",
 *     "data" = "data",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "collection" = "/admin/reports/site-audit/reports",
 *     "add-form" = "/admin/reports/site-audit/save",
 *     "canonical" = "/admin/reports/site-audit/reports/{site_audit_report}",
 *     "edit-form" = "/admin/reports/site-audit/reports/{site_audit_report}/edit",
 *     "delete-form" = "/admin/reports/site-audit/reports/{site_audit_report}/delete",
 *   },
 *   field_ui_base_route = "entity.site_audit_report.settings",
 * )
 */
class SiteAuditReport extends RevisionableContentEntityBase implements SiteAuditReportInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }

    // Normalize json data before saving.
    $data = $this->get('data')->getValue();
    $data_normalized = \Drupal::service('serializer')->normalize($data);
    $this->get('data')->setValue($data_normalized);

  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setDefaultValueCallback(static::class . '::getDefaultLabel')
      ->setRequired(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site Title'))
      ->setDescription(t('The Drupal site title for the site.'))
      ->setRequired(true)
      ->setRevisionable(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultSiteTitle')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['site_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Site UUID'))
      ->setDescription(t('The Drupal site UUID.'))
      ->setRequired(true)
      ->setReadOnly(true)
      ->setDefaultValueCallback(static::class . '::getSiteUuid')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the site audit report was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the site audit report was last edited.'));

    $fields['uri'] = BaseFieldDefinition::create('uri')
      ->setRevisionable(TRUE)
      ->setLabel(t('Site URI'))
      ->setDescription(t('The URI of the site this report was generated for.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback(static::class . '::getDefaultUri')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'uri',
      ])
      ->setDisplayConfigurable('view', TRUE);
    ;
    // Using hostname to remain consistent with other Drupal core tools like dblog.
    $fields['hostname'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reporter IP'))
      ->setDescription(t('The Client IP of the request that triggered the report.'))
      ->setDefaultValueCallback(static::class . '::getHostname')
      ->setDisplayConfigurable('view', TRUE);
    ;
    $fields['data'] = BaseFieldDefinition::create('map')
      ->setRevisionable(TRUE)
      ->setLabel(t('Report Data'))
      ->setDescription(t('The raw report data.'))
      ->setRequired(TRUE)
      ->setDefaultValueCallback(static::class . '::getNewReportData')
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'site_audit_report_html',
      ])
    ;

    return $fields;
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's hostname.
   */
  public static function getDefaultLabel() {
    return \Drupal::config('site_audit_send.settings')
      ->get('remote_label');
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's hostname.
   */
  public static function getDefaultUri() {
    return \Drupal::request()->getSchemeAndHttpHost();
  }

  /**
   * Returns the default value for site audit report entity uri base field.
   *
   * @return string
   *   The site's title.
   */
  public static function getDefaultSiteTitle() {
    return \Drupal::config('system.site')->get('name');
  }

  /**
   * Returns the site's UUID.
   *
   * @return string
   *   The site's uuid.
   */
  public static function getSiteUuid() {
    return \Drupal::config('system.site')->get('uuid');
  }

  /**
   * Returns the reporters IP.
   *
   * @return string
   *   The client IP of the request.
   */
  public static function getHostname() {
    return \Drupal::request()->getClientIp();
  }

  /**
   * Returns a new report.
   *
   * @return array
   *   An array of reports from site audit module.
   */
  public static function getNewReportData() {
    $data = [
      self::audit('json')
    ];
    return $data;
  }

  /**
   * Audit.
   *
   * @return string
   *   Rendered report output.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  static public function audit($format = 'html') {
    $checklistDefinitions = \Drupal::service('plugin.manager.site_audit_checklist')->getDefinitions();
    $saved_reports = \Drupal::service('config.factory')->getEditable('site_audit.settings')->get('reports');
    $checklists = [];
    // Check to see if there is anything checked
    // the array is empty, so the settings form hasn't been submitted.
    if (!empty($saved_reports) &&
      // They are not all unchecked.
      count(array_flip($saved_reports)) > 1) {
      foreach ($saved_reports as $saved_report) {
        if ($saved_report) {
          $checklists[$saved_report] = \Drupal::service('plugin.manager.site_audit_checklist')->createInstance($saved_report);
        }
      }
    }
    // There are no reports selected, so run them all.
    else {
      foreach ($checklistDefinitions as $checklistDefinition) {
        $checklists[$checklistDefinition['id']] = \Drupal::service('plugin.manager.site_audit_checklist')->createInstance($checklistDefinition['id']);
      }
    }

    $logger = NULL;
    $output = NULL;
    $options = ['detail' => TRUE, 'inline' => TRUE, 'uri' => \Drupal::request()->getHost()];

    switch ($format) {
      case 'html':
        $renderer = new Html($checklists, $logger, $options);
        $render = $renderer->render(TRUE);
        $out = \Drupal::service('renderer')->renderRoot($render);
        break;

      case 'json';
        foreach ($checklists as $plugin_id => $checklist) {
          $renderer = new Json($checklist, $logger, $options, $output);
          # @TODO: Figure out how to use Drupal denormalizer
          $out[$plugin_id] = json_decode($renderer->render(TRUE), TRUE);
          $out[$plugin_id]['plugin_id'] = $plugin_id;
        }
        break;

      case 'markdown':
        foreach ($checklists as $checklist) {
          $renderer = new Markdown($checklist, $logger, $options, $output);
          $out .= $renderer->render(TRUE);
        }
        break;

      case 'text':
      default:
        foreach ($checklists as $checklist) {
          $renderer = new Console($checklist, $logger, $options, $output);
          // The Console::renderer() doesn't return anything, it print directly to the console.
          $renderer->render(TRUE);
        }
        break;
    }

    return $out;

  }

  /**
   *
   */
  static public function getPercentClasses($percent) {
    if ($percent > 80) {
      return self::getScoreClasses(SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS);
    }
    if ($percent > 65) {
      return self::getScoreClasses(SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN);
    }
    if ($percent >= 0) {
      return self::getScoreClasses(SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN);
    }
  }

  /**
   *
   */
  static public function getScoreClasses($score) {
    $classes = [
      'site_audit_report_state'
    ];

    if ($score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS) {
      $state = 'checked';
    }
    elseif ($score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN) {
      $state = 'warning';
    }
    elseif ($score == SiteAuditCheckBase::AUDIT_CHECK_SCORE_FAIL) {
      $state = 'error';
    }
    else {
      $state = 'info';
    }

    $classes[] = "site_audit_report_state_{$state}";

    return $classes;
  }

  /**
   * Get the CSS class associated with a percentage.
   *
   * @return string
   *   A CSS class. Use Drupal core stuff whenever possible.
   */
  static public function getPercentCssClass($percent) {
    if ($percent > 80) {
      return 'success';
    }
    if ($percent > 65) {
      return 'error';
    }
    if ($percent >= 0) {
      return 'caution';
    }
    return 'info';
  }

  /**
   * Get the CSS class associated with a percentage.
   *
   * @return string
   *   A CSS class. Use Drupal core stuff whenever possible.
   */
  static public function getScoreCssClass($score = NULL) {
    switch ($score) {
      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
        return 'success';

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
        return 'caution';

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
        return 'info';

      default:
        return 'error';

    }
  }
  /**
   * Get the CSS class associated with a score.
   *
   * @return string
   *   Name of the Twitter bootstrap class.
   *
   * @TODO: Make this an example of extending to use bootstrap classes instead.
  static public function getScoreCssClass($score = NULL) {
    switch ($score) {
      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_PASS:
        return 'success';

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_WARN:
        return 'warning';

      case SiteAuditCheckBase::AUDIT_CHECK_SCORE_INFO:
        return 'info';

      default:
        return 'danger';

    }
  }
   */

  /**
   * @param $message String A string to save in the revision log.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function newReportEntity(string $label = '', string $revision_log = '') {

    $user = \Drupal::currentUser();

    // Do not include data here so entity api uses default callback.
    $entity_data = [
      'uid' => $user->id(),
      'label' => $label,
      'revision_log' => t('Report created from defaults: :message', [
        ':message' => $revision_log ?: \Drupal::service('date.formatter')
          ->format(time()),
      ]),
    ];

    $report_entity = \Drupal::entityTypeManager()
      ->getStorage('site_audit_report')
      ->create($entity_data);
    return $report_entity;
  }

  /**
   * @param $revision_log String A string to save in the revision log.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function saveReport(string $label = '', string $revision_log = '') {
    $report_entity = self::newReportEntity($label, $revision_log);
    $report_entity->save();
    // saving standard property so sendReport and saveReport both have uris.
    $report_entity->link = $report_entity->toUrl('canonical', ['absolute' => true])->toString();
    return $report_entity;
  }

  /**
   * @param $revision_log String A string to save in the revision log.
   * @param $send_method String The send method to invoke.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function sendReport(string $label = '', string $revision_log = '', $send_method = 'report_api') {
    $report_entity = self::newReportEntity($label, $revision_log);
    \Drupal::moduleHandler()->invokeAllWith('site_audit_send_send_methods', function (callable $hook, string $module) use ($report_entity, $send_method) {
      $hook = "{$module}_site_audit_send_send_{$send_method}";

      // @TODO: invoke a pre_report_send hook.
      $hook($report_entity);
    });
    return $report_entity;
  }
}
