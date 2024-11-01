<?php

namespace Drupal\module_builder_test_component_type\Form;

use DrupalCodeBuilder\Factory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\module_builder\Form\ModuleMiscForm;
use Drupal\module_builder_test_component_type\TestGenerateTask;
use DrupalCodeBuilder\Task\Generate;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\CliDumper;

/**
 * Uses a custom Generate task for a standardized set of form elements.
 */
class TestComponentMiscForm extends ModuleMiscForm {

  /**
   * {@inheritdoc}
   */
  public function setGenerateTask(Generate $generate_task) {
    $dcb_container =  Factory::getContainer();

    // Can't use the container directly, as it won't know about the class we
    // want to use instead.
    $generate_task = new TestGenerateTask(
      $dcb_container->get('environment'),
      'module',
      $dcb_container->get('Generate\ComponentClassHandler'),
      $dcb_container->get('Generate\ComponentCollector'),
      $dcb_container->get('Generate\FileAssembler'),
    );

    $this->codeBuilderTaskHandlerGenerate = $generate_task;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Comment this out to see the form state data during form operations.
    return $form;

    if ($form_state->has('data')) {
      $data = $form_state->get('data');

      $cloner = new VarCloner();
      $dumper = new CliDumper();
      $output = $dumper->dump($cloner->cloneVar($data->export()), TRUE);

      $form['data_post_build'] = [
        '#type' => 'textarea',
        '#title' => 'Form state data after form build',
        '#value' => $output,
        '#rows' => substr_count($output, "\n") + 1,
      ];
    }

    return $form;
  }

}
