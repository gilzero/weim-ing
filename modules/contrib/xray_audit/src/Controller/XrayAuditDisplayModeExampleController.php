<?php

namespace Drupal\xray_audit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Displays a given  entity using a given display mode.
 *
 * Used for example purposes, to display an entity on different incarnations.
 *
 * @package Drupal\xray_audit\Controller
 */
final class XrayAuditDisplayModeExampleController extends ControllerBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * XrayAuditDisplayModeExampleController constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new self($container->get('database'));
  }

  /**
   * Display a entity in a given view mode.
   *
   * @param string $entity_type
   *   The view mode.
   * @param string $entity_id
   *   The entity type.
   * @param string $view_mode
   *   The entity id.
   */
  public function displayEntity(string $entity_type, string $entity_id, string $view_mode) {
    $response = [];
    if ($entity = $this->entityTypeManager()->getStorage($entity_type)->load($entity_id)) {
      $response = $this->entityTypeManager()
        ->getViewBuilder($entity_type)
        ->view($entity, $view_mode);
    }
    return $response;
  }

  /**
   * Display an entity in a given view mode.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $entity_bundle
   *   The entity bundle type.
   * @param string $view_mode
   *   The view mode.
   */
  public function displayEntityPopupContent(string $entity_type, string $entity_bundle, string $view_mode) {
    $id = $this->getExampleId($entity_type, $entity_bundle);

    $build = [
      '#theme' => 'xray_audit_popup',
    ];

    if (!$id) {
      $build['#content'] = [
        '#type' => 'markup',
        '#markup' => $this->t('No examples available.'),
      ];
      $build['#attributes']['class'] = ['xray-audit--message'];
    }
    elseif ($entity_type === 'node' && ($view_mode === 'default' || $view_mode === 'full')) {
      $url = Url::fromRoute('entity.node.canonical', ['node' => $id]);

      return new RedirectResponse($url->toString());
    }
    else {
      $build['#attributes']['class'] = ['xray-audit--iframe'];
      $build['#iframe_url'] = Url::fromRoute('xray_audit.display_mode_example', [
        'entity_type' => $entity_type,
        'entity_id' => $id,
        'view_mode' => $view_mode,
      ]);
    }
    return $build;
  }

  /**
   * Provide random entity id.
   *
   * @param string $entity_type
   *   Entity name.
   * @param string $entity_bundle
   *   Entity type name.
   */
  protected function getExampleId(string $entity_type, string $entity_bundle) {
    $query = $this->entityTypeManager()->getStorage($entity_type)->getQuery();
    $bundle_column = 'type';
    $sort_column = 'changed';

    if ($entity_type === 'media') {
      $bundle_column = 'bundle';
    }
    elseif ($entity_type === 'taxonomy_term') {
      $bundle_column = 'vid';
    }

    if ($entity_type === 'paragraph') {
      $sort_column = 'created';
    }

    $query->accessCheck(TRUE)
      ->condition($bundle_column, $entity_bundle);

    if ($entity_type === 'paragraph') {
      $query->condition('id', $this->getParagraphsUsedOnCurrentRevisions(), 'IN');
    }

    $ids = $query->range(0, 100)
      ->accessCheck(TRUE)
      ->sort($sort_column, 'DESC')
      ->execute();

    if (count($ids) > 0) {
      return $ids[array_rand($ids)];
    }
    return FALSE;
  }

  /**
   * Get the list of paragraphs that are being used on current revisions.
   *
   * This is used to not show unused paragraphs in paragraphs hierarchy.
   *
   * @return array
   *   IDs of unused paragraphs.
   */
  protected function getParagraphsUsedOnCurrentRevisions() {
    $paragraph_field_tables_query = $this->database->select('paragraphs_item_field_data', 'pd');
    $paragraph_field_tables_query->addExpression('CONCAT(pd.parent_type, :underscores , pd.parent_field_name)', 'table_name', [':underscores' => '__']);
    $paragraph_field_tables_query->groupBy('pd.parent_type');
    $paragraph_field_tables_query->groupBy('pd.parent_field_name');
    $executed_paragraph_field_tables_query = $paragraph_field_tables_query->execute();
    if (!$executed_paragraph_field_tables_query instanceof StatementInterface) {
      return [];
    }
    $paragraph_field_tables = $executed_paragraph_field_tables_query->fetchAllKeyed(0, 0);
    $paragraphs_ids = [];
    foreach ($paragraph_field_tables as $paragraph_field_table) {
      [, $paragraph_field_table_field_name] = explode('__', $paragraph_field_table);
      if ($this->database->schema()->tableExists($paragraph_field_table)) {
        $executed_paragraphs_ids = $this->database->select($paragraph_field_table)
          ->fields($paragraph_field_table, [$paragraph_field_table_field_name . '_target_id'])
          ->execute();
        if ($executed_paragraphs_ids instanceof StatementInterface) {
          $paragraphs_ids = array_merge($paragraphs_ids, $executed_paragraphs_ids->fetchAllKeyed(0, 0));
        }
      }
    }
    return $paragraphs_ids;
  }

}
