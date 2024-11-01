<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use MutableTypedData\Data\DataItem;

/**
 * Form for editing basic information, and also for adding new module entities.
 */
class ModuleNameForm extends ComponentSectionForm {

  /**
   * {@inheritdoc}
   */
  protected function getFormComponentProperties(DataItem $data) {
    // Get the list of component properties this section form uses from the
    // handler, which gets them from the entity type annotation.
    $component_entity_type_id = $this->entity->getEntityTypeId();
    $component_sections_handler = $this->entityTypeManager->getHandler($component_entity_type_id, 'component_sections');

    // Need to override this method to hardcode the form operation name, because
    // there is a mismatch between our system which wants this to be the 'name'
    // op, but uses the 'edit' op's route and form class.
    // TODO: clean up!
    $operation = 'name';
    $component_properties_to_use = $component_sections_handler->getSectionFormComponentProperties($operation);
    return $component_properties_to_use;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $module = $this->entity;

    // The name.
    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Readable name'),
      '#maxlength' => 255,
      '#default_value' => isset($module->name) ? $module->name : '',
      '#description' => $this->t("The form of the module name that appears in the UI."),
      '#required' => TRUE,
    );

    // The machine name.
    $form['id'] = array(
      '#type' => 'machine_name',
      '#title' => $this->t('Name'),
      '#description' => $this->t("The module's machine name, used in function and file names. May only contain lowercase letters, numbers, and underscores."),
      '#maxlength' => 32,
      '#default_value' => $module->id,
      '#machine_name' => array(
        'exists' => '\Drupal\module_builder\Entity\ModuleBuilderModule::load',
        'source' => ['name'],
        'standalone' => TRUE,
      ),
    );

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Write location'),
      '#maxlength' => 128,
      '#default_value' => isset($module->location) ? $module->location : '',
      '#description' => $this->t("A sub-path inside the Drupal root into which to place this module. Do not include the module name. If left empty, defaults to the existing module's location if it exists, 'modules/custom' if that exists, and finally 'modules'."),
    ];

    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);

    if ($this->entity->isNew()) {
      $button = $actions['submit'];
      $button['#value'] = $this->t('Save basic information');
      // Babysit core bug: dropbutton with only one item looks wrong.
      unset($button['#dropbutton']);

      $actions = [
        'submit' => $button,
      ];
    }

    return $actions;
  }

  /**
   * Copies top-level form values to entity properties
   *
   * This should not change existing entity properties that are not being edited
   * by this form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the current form should operate upon.
   * @param array $form
   *   A nested array of form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $values = array_diff_key($values, $this->entity->getPluginCollections());
    }

    // Set the base properties.
    foreach (['name', 'id', 'location'] as $key) {
      $entity->set($key, $values[$key]);

      // Remove so it doesn't end up in the $entity->data array.
      unset($values[$key]);
    }

    // Call the parent to set the data array properties.
    parent::copyFormValuesToEntity($entity, $form, $form_state);
  }

}
