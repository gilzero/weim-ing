<?php

namespace Drupal\ebt_core_remove_helper\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * EBT Core remove helper operations.
 */
class EbtRemoveHelperForm extends FormBase {

  /**
   * The custom block type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockContentTypeStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $entity_type_manager->getStorage('block_content_type'),
    );
  }

  /**
   * Constructs a Form object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $block_content_type_storage
   *   The custom block type storage.
   */
  public function __construct(EntityStorageInterface $block_content_type_storage) {
    $this->blockContentTypeStorage = $block_content_type_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'ebt_remove_helper_batch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['operation'] = [
      '#title' => $this->t('Operation'),
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => [
        '' => $this->t('Select operation'),
        'remove_all_ebt_blocks' => $this->t('Remove All EBT Blocks'),
        'remove_ebt_settings_field_storage' => $this->t('Remove EBT Settings Field Storage'),
      ],
      '#default_value' => '',
    ];

    $form['description'] = [
      '#title' => $this->t('Attention!'),
      '#markup' => $this->t('<p>Removing inline blocks for existing layout builder pages can cause error:
      Error: Call to a member function getEntityTypeId() on null (Layout Builder).</p>
      <p><a href="https://www.drupal.org/project/drupal/issues/3049332" target="_blank">
        https://www.drupal.org/project/drupal/issues/3049332
        </a>
      </p>
      <p>Create a backup before removing Inline Blocks programmatically.</p>
      <p>Avoid to remove blocks programmatically on Live site.</p>'),
    ];

    $form['run_operation'] = [
      '#type' => 'submit',
      '#value' => $this->t('Run operation'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operation = $form_state->getValue('operation');
    switch ($operation) {
      case 'remove_all_ebt_blocks':
        $operations = [];
        $bids = $this->getAllEbtBlockIds();
        if (!empty($bids)) {
          $operations[] = ['_ebt_core_remove_helper_remove_blocks', [$bids]];
        }

        if (empty($operations)) {
          \Drupal::messenger()->addMessage('You don\'t have EBT blocks on your site.');
          return;
        }

        $batch = [
          'title' => $this->t('Deleting All EBT blocks ...'),
          'operations' => $operations,
          'finished' => '_ebt_core_remove_helper_remove_blocks_finished',
        ];
        batch_set($batch);
        break;

      case 'remove_ebt_settings_field_storage':
        $ebt_types = $this->getAllEbtBlockTypes();
        if (!empty($ebt_types)) {
          \Drupal::messenger()->addMessage('You have created EBT block types yet.');
          return;
        }

        $field_storage_name = 'field_ebt_settings';
        $field_storage = \Drupal::entityTypeManager()
          ->getStorage('field_storage_config')
          ->load($field_storage_name);
        if ($field_storage) {
          $field_storage->delete();
        }
        break;

      case 'remove_all_ebt_block_types':
        $bids = $this->getAllEbtBlockIds();
        if (!empty($bids)) {
          \Drupal::messenger()->addMessage('Please, remove all EBT blocks before removing all EBT Block types.');
          return;
        }

        $ebt_types = $this->getAllEbtBlockTypes();
        if (empty($ebt_types)) {
          \Drupal::messenger()->addMessage('You have not created any EBT block types yet');
          return FALSE;
        }
        break;

      default:
        \Drupal::messenger()->addMessage('Unknown operation type.');
    }

  }

  /**
   * Helper method to get all EBT block IDs.
   */
  public function getAllEbtBlockIds() {
    $ebt_types = $this->getAllEbtBlockTypes();
    if (empty($ebt_types)) {
      \Drupal::messenger()->addMessage('You have not created any EBT block types yet');
      return FALSE;
    }

    return \Drupal::entityQuery('block_content')
      ->accessCheck(FALSE)
      ->condition('type', $ebt_types, 'IN')
      ->execute();
  }

  /**
   * Helper function.
   */
  public function getAllEbtBlockTypes() {
    $types = $this->blockContentTypeStorage->loadMultiple();
    uasort($types,
      [$this->blockContentTypeStorage->getEntityType()->getClass(), 'sort']
    );
    if (count($types) === 0) {
      \Drupal::messenger()->addMessage('You have not created any block types yet');
      return FALSE;
    }

    $ebt_types = [];
    foreach ($types as $block_type => $block_content_type) {
      if (strpos($block_type, 'ebt_') !== FALSE) {
        array_push($ebt_types, $block_type);
      }
    }
    return $ebt_types;
  }

}
