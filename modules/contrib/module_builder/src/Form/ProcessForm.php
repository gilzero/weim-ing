<?php

namespace Drupal\module_builder\Form;

use DrupalCodeBuilder\Factory;
use DrupalCodeBuilder\Exception\StorageException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Utility\Error;
use DrupalCodeBuilder\Exception\SanityException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for running the DCB analysis process.
 */
class ProcessForm extends FormBase {

  /**
   * The code builder wrapper service.
   *
   * @var \Drupal\module_builder\Drupal
   */
  protected $drupalCodeBuilder;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Creates a ProcessForm instance.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Messenger service.
   */
  public function __construct(
    $drupal_code_builder,
    MessengerInterface $messenger
  ) {
    $this->drupalCodeBuilder = $drupal_code_builder;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Note that we can't inject the DCB tasks because they throw sanity
    // exceptions.
    return new static(
      $container->get('module_builder.drupal_code_builder'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_builder_process';
  }

  /**
   * Gets the collect task.
   *
   * This exists to allow easy overriding of the task by the MB devel module.
   */
  protected static function getCollectTask() {
    return \Drupal::service('module_builder.drupal_code_builder')->getTask('Collect');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $task_handler_report = $this->drupalCodeBuilder->getTask('ReportHookDataFolder');
      $task_report_summary = $this->drupalCodeBuilder->getTask('ReportSummary');
    }
    catch (SanityException $e) {
      if ($e->getFailedSanityLevel() == 'data_directory_exists') {
        $this->messenger->addError($this->t("The hooks data directory does not exist, or is not writeable. Check your settings and your filesystem."));

        return $form;
      }

      // We're in right place to do something about a hooks processed sanity
      // problem, so no need to show a message for that.
    }

    // The task handler returns sane values for these even if there's no hook
    // data.
    $last_update = $task_handler_report->lastUpdatedDate();
    $directory = Factory::getEnvironment()->getHooksDirectory();

    $form['intro'] = array(
      '#markup' => '<p>' . t("Module Builder analyses your site's code to find data about Drupal components such as hooks, plugins, tagged services, and more." . ' '
        . "This processed data is stored in your local filesystem." . ' '
        . "You should update the code analysis when updating site code, or updating Module Builder or Drupal Code Builder."
        ) . '</p>',
    );

    $form['analyse'] = [
      '#type' => 'fieldset',
      '#title' => "Perform analysis",
    ];

    $form['analyse']['last_update'] = array(
      '#markup' => '<p>' . (
        $last_update ?
          t('Your last data update was %date.', array(
            '%date' => \Drupal::service('date.formatter')->format($last_update, 'large'),
          )) :
          t("The site's code has not yet been analysed.")
        ) . '</p>',
    );

    $form['analyse']['clear_caches'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Clear all caches before analysis"),
      '#description' => $this->t("Select this if you have changed custom code."),
    ];

    $form['analyse']['submit'] = array(
      '#type' => 'submit',
      '#value' => $last_update
        ? t('Update code analysis')
        : t('Perform code analysis'),
    );

    if ($last_update) {
      try {
        $analysis_data = $task_report_summary->listStoredData();
      }
      catch (StorageException $e) {
        // Bail if the storage has a problem.
        $this->messenger()->addError($e->getMessage());
        return $form;
      }

      $form['results'] = [
        '#type' => 'fieldset',
        '#title' => "Analysis results",
      ];

      $form['results']['text'] = array(
        '#markup' => '<p>' . t('You have the following data saved in %dir: ', array(
          '%dir' => $directory,
        )) . '</p>',
      );

      foreach ($analysis_data as $type => $type_data) {
        $form['results'][$type] = [
          '#type' => 'details',
          '#title' => "{$type_data['label']} ({$type_data['count']})",
          '#open' => FALSE,
        ];

        if (is_array(reset($type_data['list']))) {
          $items = [];
          foreach ($type_data['list'] as $group_name => $group_items) {
            $items = array_merge($items, array_keys($group_items));
          }
        }
        else {
          $items = array_keys($type_data['list']);
        }

        $form['results'][$type]['items'] = [
          '#theme' => 'item_list',
          '#items' => $items,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Safe to do this without exception handling: it's already been checked in
    // the form builder.
    $task_handler_collect = static::getCollectTask();
    $job_list = $task_handler_collect->getJobList();

    $batch = array(
      'title' => t('Analysing site code'),
      'operations' => [],
      'file' => \Drupal::service('extension.list.module')->getPath('module_builder') . '/includes/module_builder.admin.inc',
      'finished' => [get_class($this), 'batchFinished'],
    );

    // Start with a batch operation to clear all caches if requested.
    if ($form_state->getValue('clear_caches')) {
      $batch['operations'][] = [
        [get_class($this), 'batchFlushCaches'],
        [],
      ];
    }

    // Split the jobs into batches of 10.
    $job_batches = array_chunk($job_list, 10);
    foreach ($job_batches as $job_batch) {
      // Run all jobs directly, without batch API. Also need to comment out
      // the call to batch_set()! Useful for seeing debug output.
      // $fake = [];
      // $task_handler_collect->collectComponentDataIncremental($job_batch, $fake);

      $batch['operations'][] = [
        [get_class($this), 'batchOperation'],
        [
          $job_batch,
        ],
      ];
    }

    batch_set($batch);
  }

  /**
   * Implements callback_batch_operation().
   */
  public static function batchFlushCaches(&$context) {
    drupal_flush_all_caches();

    $context['message'] = t("Clearing caches.");
  }

  /**
   * Implements callback_batch_operation().
   */
  public static function batchOperation($job_batch, &$context) {
    $task_handler_collect = static::getCollectTask();

    try {
      $task_handler_collect->collectComponentDataIncremental($job_batch, $context['results']);
    }
    catch (\Exception $e) {
      // Store an exception message and bail on this operation.
      $context['results']['errors'][] = $e->getMessage();

      Error::logException(\Drupal::logger('module_builder'), $e);

      return;
    }

    // Assemble a progress message.
    $labels = [];
    $message_pieces = [];
    foreach ($job_batch as $job) {
      if (isset($job['item_label'])) {
        // One job among several for a collector.
        $labels[$job['process_label']][] = $job['item_label'];
      }
      else {
        // Singleton job.
        // Put it in the labels array to preserve the order.
        $labels[$job['process_label']] = NULL;
      }
    }

    foreach ($labels as $process_label => $item_labels) {
      if (is_null($item_labels)) {
        $message_pieces[] = $process_label;
      }
      else {
        $message_pieces[] = t('@task for @items', array(
          '@task' => $process_label,
          '@items' => implode(', ', $item_labels),
        ));
      }
    }

    $context['message'] = t("Processed: @list.", array(
      '@list' => implode(', ', $message_pieces),
    ));
  }

  /**
   * Implements callback_batch_finished().
   */
  public static function batchFinished($success, $results, $operations) {
    if (isset($results['errors'])) {
      \Drupal::messenger()->addError(t("The code analysis process produced errors: @messages", [
        '@messages' => implode(', ', $results['errors']),
      ]));
    }
    else {
      \Drupal::messenger()->addStatus(t("Finished analysing site code. See results below for details."));
    }
  }

}
