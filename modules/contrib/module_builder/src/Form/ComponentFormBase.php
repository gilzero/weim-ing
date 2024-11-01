<?php

namespace Drupal\module_builder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use DrupalCodeBuilder\Exception\SanityException;
use DrupalCodeBuilder\Task\Generate;
use Drupal\module_builder\ExceptionHandler;
use Drupal\module_builder\DrupalCodeBuilder;
use MutableTypedData\Data\DataItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Module Builder component forms.
 */
abstract class ComponentFormBase extends EntityForm {

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

    // Get the component data info based on the entity type. This is the
    // earliest we can do this, as entity forms don't know the entity type they
    // are for when constructed.
    $component_type = $entity->getComponentType();

    try {
      $generate_task = $this->codeBuilder->getTask('Generate', $component_type);
    }
    catch (SanityException $e) {
      $this->sanityException = $e;

      return $this;
    }

    $this->setGenerateTask($generate_task);

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

    return parent::buildForm($form, $form_state);
  }

}
