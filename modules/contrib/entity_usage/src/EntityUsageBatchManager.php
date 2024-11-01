<?php

namespace Drupal\entity_usage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Utility\Error;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Manages Entity Usage integration with Batch API.
 */
class EntityUsageBatchManager implements LoggerAwareInterface {

  use LoggerAwareTrait;
  use StringTranslationTrait;

  /**
   * The size of the batch for the revision queries.
   */
  const REVISION_BATCH_SIZE = 15;

  /**
   * Creates a EntityUsageBatchManager object.
   */
  final public function __construct(
    private EntityTypeManagerInterface $entityTypeManager,
    TranslationInterface $stringTranslation,
    private ConfigFactoryInterface $configFactory,
  ) {
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * Recreate the entity usage statistics.
   *
   * @param bool $keep_existing_records
   *   (optional) If TRUE existing usage records won't be deleted. Defaults to
   *   FALSE.
   *
   * Generate a batch to recreate the statistics for all entities.
   * Note that if we force all statistics to be created, there is no need to
   * separate them between source / target cases. If all entities are
   * going to be re-tracked, tracking all of them as source is enough, because
   * there could never be a target without a source.
   */
  public function recreate($keep_existing_records = FALSE) {
    $batch = $this->generateBatch($keep_existing_records);
    batch_set($batch);
  }

  /**
   * Create a batch to process the entity types in bulk.
   *
   * @param bool $keep_existing_records
   *   (optional) If TRUE existing usage records won't be deleted. Defaults to
   *   FALSE.
   *
   * @return array{operations: array<array{callable-string, array}>, finished: callable-string, title: \Drupal\Core\StringTranslation\TranslatableMarkup, progress_message: \Drupal\Core\StringTranslation\TranslatableMarkup, error_message: \Drupal\Core\StringTranslation\TranslatableMarkup}
   *   The batch array.
   */
  public function generateBatch($keep_existing_records = FALSE) {
    $operations = [];
    $to_track = $this->configFactory
      ->get('entity_usage.settings')
      ->get('track_enabled_source_entity_types');
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      // Only look for entities enabled for tracking on the settings form.
      $track_this_entity_type = FALSE;
      if (!is_array($to_track) && ($entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface'))) {
        // When no settings are defined, track all content entities by default,
        // except for Files and Users.
        if (!in_array($entity_type_id, ['file', 'user'])) {
          $track_this_entity_type = TRUE;
        }
      }
      elseif (is_array($to_track) && in_array($entity_type_id, $to_track, TRUE)) {
        $track_this_entity_type = TRUE;
      }
      if ($track_this_entity_type) {
        $operations[] = ['\Drupal\entity_usage\EntityUsageBatchManager::updateSourcesBatchWorker', [$entity_type_id, $keep_existing_records]];
      }
    }

    $batch = [
      'operations' => $operations,
      'finished' => '\Drupal\entity_usage\EntityUsageBatchManager::batchFinished',
      'title' => $this->t('Updating entity usage statistics.'),
      'progress_message' => $this->t('Processed @current of @total entity types.'),
      'error_message' => $this->t('This batch encountered an error.'),
    ];

    return $batch;
  }

  /**
   * Batch operation worker for recreating statistics for source entities.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'node'.
   * @param bool $keep_existing_records
   *   If TRUE existing usage records won't be deleted.
   * @param array{sandbox: array{progress?: int, total?: int, current_item?: int}, results: string[], finished: int, message: string} $context
   *   Batch context.
   */
  public static function updateSourcesBatchWorker($entity_type_id, $keep_existing_records, &$context) {
    $entity_storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
    $entity_type_key = $entity_type->getKey('id');

    if (empty($context['sandbox']['total'])) {
      $id_definition = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id)[$entity_type_key];

      // Delete current usage statistics for these entities.
      if (!$keep_existing_records) {
        \Drupal::service('entity_usage.usage')->bulkDeleteSources($entity_type_id);
      }

      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_id'] = '';
      if (($id_definition instanceof FieldStorageDefinitionInterface) && $id_definition->getType()  === 'integer') {
        $context['sandbox']['current_id'] = -1;
      }
      $context['sandbox']['total'] = (int) $entity_storage->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
      $context['sandbox']['batch_entity_revision'] = [
        'status' => 0,
        'current_vid' => 0,
        'start' => 0,
      ];
    }
    if ($context['sandbox']['batch_entity_revision']['status']) {
      $op = '=';
    }
    else {
      $op = '>';
    }

    $entity_ids = $entity_storage->getQuery()
      ->condition($entity_type_key, $context['sandbox']['current_id'], $op)
      ->range(0, 1)
      ->accessCheck(FALSE)
      ->sort($entity_type_key)
      ->execute();
    $entity_id = reset($entity_ids);

    if ($entity_id !== FALSE && $entity = $entity_storage->load($entity_id)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      try {
        if ($entity->getEntityType()->isRevisionable()) {
          assert($entity_storage instanceof RevisionableStorageInterface);

          // We cannot query the revisions due to this bug
          // https://www.drupal.org/project/drupal/issues/2766135
          // so we will use offsets.
          $start = $context['sandbox']['batch_entity_revision']['start'];
          // Track all revisions and translations of the source entity. Sources
          // are tracked as if they were new entities.
          $result = $entity_storage->getQuery()->allRevisions()
            ->condition($entity->getEntityType()->getKey('id'), $entity->id())
            ->accessCheck(FALSE)
            ->sort($entity->getEntityType()->getKey('revision'), 'DESC')
            ->range($start, static::REVISION_BATCH_SIZE)
            ->execute();
          $revision_ids = array_keys($result);
          if (count($revision_ids) === static::REVISION_BATCH_SIZE) {
            $context['sandbox']['batch_entity_revision'] = [
              'status' => 1,
              'current_vid' => min($revision_ids),
              'start' => $start + static::REVISION_BATCH_SIZE,
            ];
          }
          else {
            $context['sandbox']['batch_entity_revision'] = [
              'status' => 0,
              'current_vid' => 0,
              'start' => 0,
            ];
          }

          foreach ($revision_ids as $revision_id) {
            /** @var \Drupal\Core\Entity\EntityInterface $entity_revision */
            if (!$entity_revision = $entity_storage->loadRevision($revision_id)) {
              continue;
            }

            \Drupal::service('entity_usage.entity_update_manager')->trackUpdateOnCreation($entity_revision);
          }
        }
        else {
          // Sources are tracked as if they were new entities.
          \Drupal::service('entity_usage.entity_update_manager')->trackUpdateOnCreation($entity);
        }
      }
      catch (\Exception $e) {
        Error::logException(\Drupal::service('logger.channel.entity_usage'), $e);
      }

      if (
        $context['sandbox']['batch_entity_revision']['status'] === 0 ||
        intval($context['sandbox']['progress']) === 0
      ) {
        $context['sandbox']['progress']++;
      }
      $context['sandbox']['current_id'] = $entity->id();
      $context['results'][] = $entity_type_id . ':' . $entity->id();
    }

    if ($context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
    }

    $context['message'] = t('Updating entity usage for @entity_type: @current of @total', [
      '@entity_type' => $entity_type_id,
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
    ]);
  }

  /**
   * Finish callback for our batch processing.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public static function batchFinished($success, array $results, array $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage(t('Recreated entity usage for @count entities.', ['@count' => count($results)]));
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      \Drupal::messenger()->addMessage(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

}
