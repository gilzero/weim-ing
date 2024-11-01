<?php

namespace Drupal\module_builder_devel\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\module_builder\Form\ProcessForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for updating DCB's test sample analysis data.
 */
class ProcessTestSamplesForm extends ProcessForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Note that we can't inject the DCB tasks because they throw sanity
    // exceptions.
    return new static(
      // Switch to the wrapper service that uses the test samples environment.
      $container->get('module_builder_devel.drupal_code_builder.test_samples'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected static function getCollectTask() {
    return \Drupal::service('module_builder_devel.drupal_code_builder.test_samples')->getTask('Testing\CollectTesting');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Remove the results; they're confusing here.
    // TODO: DCB should let us show what's in the test sample data location!
    unset($form['results']);

    unset($form['intro']);

    $form['analyse']['#title'] = $this->t("Collect sample analysis data");

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

    // No need to use a batch, the job list is much smaller.
    $results = [];
    $task_handler_collect->collectComponentDataIncremental($job_list, $results);

    $this->messenger()->addStatus(t("Finished analysing code for the test sample data."));
  }

}
