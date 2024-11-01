<?php

declare(strict_types=1);

namespace Drupal\security_review\Plugin\SecurityCheck;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityMalformedException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Exception\UndefinedLinkTemplateException;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\security_review\CheckResult;
use Drupal\security_review\SecurityCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks for Javascript and PHP in submitted content.
 *
 * @SecurityCheck(
 *   id = "fields",
 *   title = @Translation("Dangerous tags in content exclude list"),
 *   description = @Translation("Checks for Javascript and PHP in submitted content."),
 *   namespace = @Translation("Security Review"),
 *   success_message = @Translation("Dangerous tags were not found in any submitted content (fields)."),
 *   failure_message = @Translation("Dangerous tags were found in submitted content (fields)."),
 *   help = {
 *     @Translation("Script and PHP code in content does not align with Drupal best practices and may be a vulnerability if an untrusted user is allowed to edit such content. It is recommended you remove such contents or add to exclude list in security review settings page."),
 *   }
 * )
 */
class Fields extends SecurityCheckBase {

  use MessengerTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The sandbox from the batch process.
   *
   * We keep it centrally stored to ease access to it.
   *
   * @var array
   */
  protected array $sandbox;

  /**
   * The field types we care about.
   */
  private const FIELD_TYPES = [
    'text_with_summary',
    'text_long',
  ];

  /**
   * The tags we care about.
   */
  private const TAGS = [
    'Javascript' => 'script',
    'PHP' => '?php',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SecurityCheckBase|ContainerFactoryPluginInterface|static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function run(bool $cli = FALSE, &$sandbox = []): float {
    if (empty($sandbox)) {
      // We are going to do some bookkeeping to know where we left the last
      // iteration.
      $sandbox['done_entity_type_ids'] = [];
      $sandbox['done_fields'] = [];
      $sandbox['field_offset'] = 0;
      $sandbox['findings'] = [];
      $sandbox['hushed_findings'] = [];
    }

    // Prepare the data we want to pass between functions.
    $this->sandbox = $sandbox;
    $doneCount = 0;
    $finished = 1;

    $result = CheckResult::SUCCESS;

    $fieldMap = $this->entityFieldManager->getFieldMap();
    $totalEntityTypes = count($fieldMap);
    foreach ($fieldMap as $entity_type_id => $fields) {
      // Skip through the types we already finished.
      if (in_array($entity_type_id, $this->sandbox['done_entity_type_ids'])) {
        $doneCount++;
        continue;
      }

      $entityTypeFinished = $this->processEntityType($entity_type_id, $fields);

      $finished = ($doneCount + $entityTypeFinished) / $totalEntityTypes;
      if ($entityTypeFinished === 1.0) {
        $doneCount++;
        $this->sandbox['done_entity_type_ids'][] = $entity_type_id;
        $this->sandbox['done_fields'] = [];
      }

      // When we finish the loop once, we're happy for now.
      break;
    }

    // As a precaution, check for doneCount to be equal to the total. I don't
    // trust those floats.
    if ($doneCount == $totalEntityTypes) {
      $finished = 1.0;
    }

    // Set the sandbox parameter back to our private copy.
    $sandbox = $this->sandbox;

    if (!empty($this->sandbox['findings'])) {
      $result = CheckResult::FAIL;
    }

    if ($finished === 1.0) {
      $this->createResult($result, $this->sandbox['findings'], NULL, $this->sandbox['hushed_findings']);
    }

    return $finished;
  }

  /**
   * Process a single entity type.
   *
   * @param string $entityTypeId
   *   The entity type ID to process.
   * @param mixed $fields
   *   The fields for this entity type.
   *
   * @return float
   *   A fraction between 0 and 1 that expresses our level of completion for
   *   this one entity type.
   */
  protected function processEntityType(string $entityTypeId, mixed $fields): float {
    $doneCount = 0;
    $totalFields = count($fields);
    $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
    $finished = 1.0;
    foreach (array_keys($fields) as $fieldName) {
      if (!isset($fieldStorageDefinitions[$fieldName]) || in_array($fieldName, $this->sandbox['done_fields'])) {
        $doneCount++;
        continue;
      }
      $fieldFinished = $this->processField($entityTypeId, $fieldName);

      $finished = ($doneCount + $fieldFinished) / $totalFields;
      if ($fieldFinished === 1.0) {
        $doneCount++;
        $this->sandbox['done_fields'][] = $fieldName;
      }

      // When we finish the loop once, we're happy for now.
      break;
    }
    return $finished;
  }

  /**
   * Process a single field.
   *
   * This needs to be called again when the return value is < 1.
   *
   * @param string $entityTypeId
   *   THe current entity type ID.
   * @param string $fieldName
   *   The current field.
   *
   * @return float
   *   A fraction between 0 and 1 that expresses our level of completion for
   *   this one field.
   */
  public function processField(string $entityTypeId, string $fieldName): float {
    // We process 1000 rows per iteration.
    $batchSize = 1000;
    $knownRiskyFields = $this->getHushedFields($config['known_risky_fields'] ?? []);
    $fieldStorageDefinitions = $this->entityFieldManager->getFieldStorageDefinitions($entityTypeId);
    $fieldStorageDefinition = $fieldStorageDefinitions[$fieldName];
    if (!in_array($fieldStorageDefinition->getType(), self::FIELD_TYPES)) {
      return 1;
    }

    $finished = 1;
    try {
      $entity = $this->entityTypeManager->getStorage($entityTypeId)
        ->getEntityType();

      $separator = '_';
      $table = '';
      $id = 'entity_id';
      // We only check entities that are stored in database.
      if (is_a($entity->getStorageClass(), SqlContentEntityStorage::class, TRUE)) {
        if ($fieldStorageDefinition instanceof FieldStorageConfig) {
          $table_mapping = $this->entityTypeManager->getStorage($entityTypeId)
            ->getTableMapping();
          $table = $table_mapping->getDedicatedDataTableName($fieldStorageDefinition);
        }
        else {
          $translatable = $entity->isTranslatable();
          if ($translatable) {
            $table = $entity->getDataTable() ?: $entityTypeId . '_field_data';
          }
          else {
            $table = $entity->getBaseTable() ?: $entityTypeId;
          }
          $separator = '__';
          $id = $entity->getKey('id');
        }
      }

      $totalRows = $this->database->select($table, 't')->countQuery()->execute()->fetchField();
      $fields = [$id];
      foreach (array_keys($fieldStorageDefinition->getSchema()['columns']) as $column) {
        $columnName = $fieldName . $separator . $column;
        $fields[] = $columnName;
      }

      $query = $this->database->select($table, 't')
        ->fields('t', $fields)
        ->range($this->sandbox['field_offset'], $batchSize)
        ->execute();

      while ($record = $query->fetchAssoc()) {
        foreach ($fields as $columnName) {
          foreach (self::TAGS as $vulnerability => $tag) {
            $column_value = $record[$columnName];
            $id_value = $record[$id];
            if (str_contains((string) $column_value, '<' . $tag)) {
              // Only alert on values that are not known to be safe.
              $hash = hash('sha256', implode(
                [
                  $entityTypeId,
                  $id_value,
                  $fieldName,
                  $column_value,
                ]
              ));
              if (!array_key_exists($hash, $knownRiskyFields)) {
                // Vulnerability found.
                $this->sandbox['findings'][$entityTypeId][$id_value][$fieldName][] = $vulnerability;
                $this->sandbox['findings'][$entityTypeId][$id_value][$fieldName]['hash'] = $hash;
              }
              else {
                $this->sandbox['hushed_findings'][$entityTypeId][$id_value][$fieldName][] = $vulnerability;
                $this->sandbox['hushed_findings'][$entityTypeId][$id_value][$fieldName]['hash'] = $hash;
                $this->sandbox['hushed_findings'][$entityTypeId][$id_value][$fieldName]['reason'] = $knownRiskyFields[$hash];
              }
            }
          }
        }
      }

      $this->sandbox['field_offset'] += $batchSize;
      $finished = ($totalRows > 0) ? $this->sandbox['field_offset'] / $totalRows : 1;

      if ($this->sandbox['field_offset'] >= $totalRows) {
        $finished = 1;
        // Reset the offset for the next field.
        $this->sandbox['field_offset'] = 0;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException) {
      $this->messenger()
        ->addError('Error in fields check, could not load storage for ' . $entityTypeId);
    }

    return $finished;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails(array $findings, array $hushed = [], bool $returnString = FALSE): array|string {
    if (empty($findings) && empty($hushed)) {
      return [];
    }

    $output = $returnString ? '' : [];
    if ($returnString) {
      $output = $this->t('There were some dangerous tags found, see UI for more details.');
    }
    else {
      $paragraphs = [];
      $paragraphs[] = $this->t('The following items potentially have dangerous tags.');

      $items = $this->loopThroughItems($findings);
      $hushed_items = $this->loopThroughItems($hushed, TRUE);
      $output[] = [
        '#theme' => 'check_evaluation',
        '#paragraphs' => $paragraphs,
        '#items' => $items,
        '#hushed_items' => $hushed_items,
      ];
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $config = $this->securityReview->getCheckSettings($this->pluginId);
    $known_risky_fields = $config['known_risky_fields'] ?? [];
    $form = [];
    $form['known_risky_fields'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hashes'),
      '#description' => $this->t('SHA-256 hashes of entity_type, entity_id, field_name and field content to be skipped in future runs. Enter one value per line, in the format hash|reason.'),
      '#default_value' => implode("\n", $known_risky_fields),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array $values): void {
    $hushed['known_risky_fields'] = [];
    if (!empty($values['known_risky_fields'])) {
      $hushed['known_risky_fields'] = preg_split("/\r\n|\n|\r/", $values['known_risky_fields']);
    }
    $this->securityReview->setCheckSettings($this->pluginId, $hushed);
  }

  /**
   * Generates an array of hushed fields.
   *
   * @param array $values
   *   Array of values from config where key is numerical. Turn this into
   *   something more useable.
   *
   * @return array
   *   A key|value array of the hushed values.
   */
  protected function getHushedFields(array $values): array {
    $lines = [];
    foreach ($values as $value) {
      $parts = explode('|', $value);
      $lines[$parts[0]] = $parts[1];
    }
    return $lines;
  }

  /**
   * Attempt to get a good link for the given entity.
   *
   * Falls back on a string with entity type id and id if no good link can
   * be found.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   Entity link.
   */
  protected function getEntityLink(EntityInterface $entity): string {
    try {
      if ($entity->hasLinkTemplate('edit-form')) {
        $url = $entity->toUrl('edit-form');
      }
      else {
        $url = $entity->toUrl();
      }
    }
    catch (UndefinedLinkTemplateException | EntityMalformedException) {
      $url = NULL;
    }

    return $url !== NULL ? $url->toString() : ($entity->getEntityTypeId() . ':' . $entity->id());
  }

  /**
   * Loop through the next array of the field findings/hushed_findings.
   *
   * @param array $list
   *   Findings list to loop through.
   * @param bool $additional_info
   *   If there is additional information that should be added to output.
   *
   * @return array
   *   Formatted findings.
   */
  protected function loopThroughItems(array $list, bool $additional_info = FALSE): array {
    $items = [];
    if (!empty($list)) {
      foreach ($list as $entity_type_id => $entities) {
        foreach ($entities as $entity_id => $fields) {
          try {
            $entity = $this->entityTypeManager
              ->getStorage($entity_type_id)
              ->load($entity_id);

            foreach ($fields as $field => $finding) {
              $hash = $finding['hash'];
              unset($finding['hash']);
              if ($additional_info) {
                $items[] = $this->t(
                  '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash | <strong>Reason is @reason</strong>',
                  [
                    '@vulnerabilities' => $finding[0],
                    '@field' => $field,
                    '@label' => $entity->label(),
                    ':url' => $this->getEntityLink($entity),
                    '@hash' => $hash,
                    '@reason' => $finding['reason'],
                  ]
                );
              }
              else {
                $items[] = $this->t(
                  '@vulnerabilities found in <em>@field</em> field of <a href=":url">@label</a> Hash ID: @hash',
                  [
                    '@vulnerabilities' => implode(' and ', $finding),
                    '@field' => $field,
                    '@label' => $entity->label(),
                    ':url' => $this->getEntityLink($entity),
                    '@hash' => $hash,
                  ]
                );
              }
            }
          }
          catch (InvalidPluginDefinitionException | PluginNotFoundException) {
            $this->messenger()->addError('Error in fields check, could not load storage for ' . $entity_type_id);
          }
        }
      }
    }
    return $items;
  }

}
