<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Form\FormStateInterface;
use MutableTypedData\Data\DataItem;

/**
 * Form for selecting other components to implement.
 */
class ModuleMiscForm extends ComponentSectionForm {

  /**
   * Gets the names of properties this form should show.
   *
   * @return string[]
   *   An array of property names.
   */
  protected function getFormComponentProperties(DataItem $data) {
    // Get the list of component properties used in all the other section forms.
    $component_entity_type_id = $this->entity->getEntityTypeId();
    $component_sections_handler = $this->entityTypeManager->getHandler($component_entity_type_id, 'component_sections');
    $used_component_properties = $component_sections_handler->getUsedComponentProperties();

    // Argh, these are in the name form, but hardcoded!
    $used_component_properties[] = 'readable_name';
    $used_component_properties[] = 'root_name';

    // These are not that useful on D8, and at any rate would need to find
    // their way into the hooks form.
    // TODO: add a way to speficy properties we just skip.
    $used_component_properties[] = 'module_hook_presets';

    // Get the list of component properties that are not explicitly set in a
    // form: these are the ones we show here.
    $component_data_property_names = $data->getPropertyNames();
    $component_properties_to_use = array_diff($component_data_property_names, $used_component_properties);

    return $component_properties_to_use;
  }

   /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Change the help text form element to a textarea.
    // The check is for the benefit of functional tests. TODO: clean this up!
    if (isset($form['data']['module_help_text'])) {
      $form['data']['module_help_text']['#type'] = 'textarea';
    }

    return $form;
  }

}
