<?php

namespace Drupal\site_audit_report_entity;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the site audit report entity type.
 */
class SiteAuditReportListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new SiteAuditReportListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter')
    );
  }
  public function load() {
    // Sorting my entities.
    $entity_query = \Drupal::entityQuery('site_audit_report')
      ->sort('created' , 'DESC');

    // Make the table sortable.
    $header = $this->buildHeader();

    $entity_query->pager($this->limit);
    $entity_query->tableSort($header);

    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);

  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = parent::render();

    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    $build['summary']['#markup'] = $this->t('Total site audit reports: @total', ['@total' => $total]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Report ID');
    $header['label'] = $this->t('Label');
    $header['site_title'] = $this->t('Site Title');

    // @TODO: Use audit server module to do this. Only useful when storing remote reports.
    // $header['site_uuid'] = $this->t('Site UUID');

    $header['uri'] = $this->t('URI');
    $header['uid'] = $this->t('Reporter');
    $header['created'] = $this->t('Created');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $created = (int) $entity->get('created')->value;
    $created_string = $this->dateFormatter->format($created);

    /** @var \Drupal\site_audit_report_entity\SiteAuditReportInterface $entity */

    /** @var \Drupal\Core\Link */
    $report_link = $entity->toLink()
      ->setText($this->t('Report #:id', [':id' => $entity->id()]))
      ->toRenderable()
    ;

    $report_link['#attributes'] = [
      'class' => [
        'button',
        'nowrap'
      ]
    ];

    $row['id'] = ['data' => $report_link];

    if (empty($entity->label())) {
      $row['label'] = '';
    }
    else {
      $row['label'] = $entity->toLink();
    }

    $row['site_title'] = $entity->site_title->value;

    // @TODO: Use audit server module to do this. Only useful when storing remote reports.
    //    $row['site_uuid'] = $entity->site_uuid->value;

    if ($entity->uri) {
      $row['uri']['data'] = $entity->uri->view();
      $row['uri']['data']['#label_display'] = 'hidden';
      $row['uri']['data'][0]['#attributes']['target'] = '_blank';
    }


    $row['uid']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];

    $row['created'] = $created_string;
    return $row + parent::buildRow($entity);
  }

}
