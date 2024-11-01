<?php

namespace Drupal\module_builder\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use DrupalCodeBuilder\Exception\SanityException;
use DrupalCodeBuilder\Task\Generate;
use Drupal\module_builder\ExceptionHandler;
use Drupal\module_builder\DrupalCodeBuilder;
use MutableTypedData\Data\DataItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for adopting existing components into a component.
 */
class ComponentAdoptForm extends ComponentFormBase {

  /**
   * The Drupal Code Builder wrapping service.
   *
   * @var \Drupal\module_builder\DrupalCodeBuilder
   */
  protected $codeBuilder;

  /**
   * The DCB Generate Task handler.
   */
  protected $codeBuilderTaskHandlerGenerate;

  /**
   * The DCB Adopt Task handler.
   */
  protected $codeBuilderTaskHandlerAdopt;

  /**
   * The exception thrown by DCB when initialized, if any.
   *
   * @var \DrupalCodeBuilder\Exception\SanityException
   */
  protected $sanityException;

  /**
   * Construct a new form object
   *
   * @param \Drupal\module_builder\DrupalCodeBuilder $code_builder
   *   The Drupal Code Builder service.
   *   This needs to be injected so that submissions after an AJAX operation
   *   work (plus it's good for testing too).
   */
  function __construct(DrupalCodeBuilder $code_builder) {
    $this->codeBuilder = $code_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_builder.drupal_code_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    parent::setEntity($entity);

    try {
      $this->codeBuilderTaskHandlerAdopt = $this->codeBuilder->getTask('Adopt');
    }
    catch (SanityException $e) {
      $this->sanityException = $e;

      return $this;
    }

    return $this;
  }

  /**
   * Sets the generate task.
   *
   * @param \DrupalCodeBuilder\Task\Generate $generate_task
   */
  public function setGenerateTask(Generate $generate_task) {
    $this->codeBuilderTaskHandlerGenerate = $generate_task;
  }

  /**
   * Gets the data object for the entity in the form.
   *
   * @return \MutableTypedData\Data\DataItem
   *   The data item object loaded with entity data.
   */
  protected function getComponentDataObject(): DataItem {
    $component_data = $this->codeBuilderTaskHandlerGenerate->getRootComponentData();
    $entity_component_data = $this->entity->get('data');

    // Add in the component root name and readable name, because these are saved
    // as top-level properties in the entity config, and so aren't in the
    // component data.
    $entity_component_data['root_name'] = $this->entity->id();
    $entity_component_data['readable_name'] = $this->entity->label();

    if ($entity_component_data) {
      // Use import() to allow for changes in DCB's data structure and an older
      // data structure in the saved module entiy.
      $component_data->import($entity_component_data);
    }

    return $component_data;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Do this here, as the parent method adds the actions to the form, so doing
    // this in the form() method would show those.
    if ($this->sanityException) {
      // Pass the DCB exception to the handler, which outputs the error message.
      ExceptionHandler::handleSanityException($this->sanityException);

      return $form;
    }

    $data = $this->getComponentDataObject();
    $existing_module_path = $this->getExistingModule();

    if (empty($existing_module_path)) {
      $this->messenger()->addWarning($this->t("There is no existing module."));
      return $form;
    }

    $analyse_extension_task = $this->codeBuilder->getTask('AnalyseExtension');
    $existing_extension = $analyse_extension_task->createExtension('module', $existing_module_path);

    $adoptable = $this->codeBuilderTaskHandlerAdopt->listAdoptableComponents($data, $existing_extension);

    $form['adopt'] = [
      '#tree' => TRUE,
    ];

    foreach ($adoptable as $address => $items) {
      $form['adopt'][$address] = [
        '#title' => $data->getItem($address)->getLabel(),
        '#type' => 'checkboxes',
        '#options' => $items,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns the path to the module if it has previously been written.
   *
   * @return
   *  A Drupal-relative path to the module folder, or NULL if the module
   *  does not already exist.
   */
  protected function getExistingModule() {
    $module_name = $this->entity->id();

    $registered_in_drupal = \Drupal::service('extension.list.module')->exists($module_name);
    if ($registered_in_drupal) {
      $module = \Drupal::service('extension.list.module')->get($module_name);

      // The user may have deleted the module entirely, and in this situation
      // Drupal's extension system would still have told us it exists.
      $really_exists = file_exists($module->getPath());
      if ($really_exists) {
        return $module->getPath();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Adopt components'),
      '#submit' => ['::submitForm', '::save'],
    ];
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $count = 0;
    foreach ($form_state->getValue('adopt') as $property_name => $component_names) {
      foreach (array_filter($component_names) as $component_name) {
        $count++;
      }
    }

    if (empty($count)) {
      $form_state->setError($form['adopt'], $this->t("At least one component must be selected to adopt."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    // Override. Do NOT call copyFormValuesToEntity() -- there are no standard
    // entity values in this form.
    $entity = clone $this->entity;

    $component_data = $this->getComponentDataObject();
    $existing_module_path = $this->getExistingModule();
    $analyse_extension_task = $this->codeBuilder->getTask('AnalyseExtension');
    $existing_extension = $analyse_extension_task->createExtension('module', $existing_module_path);

    $count = 0;
    foreach ($form_state->getValue('adopt') as $property_name => $component_names) {
      foreach (array_filter($component_names) as $component_name) {
        $this->codeBuilderTaskHandlerAdopt->adoptComponent($component_data, $existing_extension, $property_name, $component_name);
        $count++;
      }
    }

    // Check count because this gets called on validate.
    if ($count) {
      // Setting the success message.
      $this->messenger()->addStatus($this->formatPlural(
        $count,
        'Adopted the component.',
        'Adopted @count components.',
      ));
    }

    // We don't save the entity because this method gets called on validate.
    $data_export = $component_data->export();
    $entity->set('data', $data_export);

    return $entity;
  }

}
