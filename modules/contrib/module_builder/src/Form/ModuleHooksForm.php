<?php

namespace Drupal\module_builder\Form;

use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\module_builder\ExceptionHandler;
use DrupalCodeBuilder\Exception\SanityException;
use MutableTypedData\Data\DataItem;

/**
 * Form for selecting hooks.
 */
class ModuleHooksForm extends ComponentSectionForm {

  /**
   * {@inheritdoc}
   */
  protected function getFormComponentProperties(DataItem $data) {
    // Return no properties, so the call to parent::form() doesn't try to
    // add the 'hooks' property we set in the entity annotation.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity_component_data = $this->getComponentDataObject();

    if ($entity_component_data->hasProperty('hook_implementation_type')) {
      $this->buildFormElement($form, $form_state, $entity_component_data->hook_implementation_type);
    }

    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#placeholder' => $this->t('Filter by hook name'),
      '#attributes' => [
        'class' => ['hooks-filter-text'],
        'data-container' => '.module-builder-hooks',
        'autocomplete' => 'off',
        'title' => $this->t('Enter a part of the hook name to filter by.'),
      ],
    ];

    // Get the Task handler.
    // No need to catch DCB exceptions; create() has already done that.
    // TODO: inject.
    $mb_task_handler_report = \Drupal::service('module_builder.drupal_code_builder')->getTask('ReportHookData');

    // Call a method in the Task handler to perform the operation.
    $hook_info = $mb_task_handler_report->listHookOptionsStructured();

    $form['groups'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['module-builder-hooks']],
    ];

    // Create a fieldset for each group, containing checkboxes.
    foreach ($hook_info as $group => $hook_group_info) {
      $form['groups'][$group] = array(
        '#type' => 'details',
        '#title' => $group,
        //'#open' => TRUE,
      );

      $hook_names = array_keys($hook_group_info);

      // Need to differentiate the key, otherwise FormAPI treats this as an
      // error on submit.
      $group_default_value = isset($entity_component_data['hooks']) ? array_intersect($hook_names, $entity_component_data['hooks']) : [];
      $form['groups'][$group][$group . '_hooks'] = array(
        '#type' => 'checkboxes',
        '#options' => array_combine($hook_names, array_column($hook_group_info, 'name')),
        '#default_value' => $group_default_value,
      );

      if (!empty($group_default_value)) {
        $form['groups'][$group]['#open'] = TRUE;
      }

      foreach ($hook_group_info as $hook => $hook_info_single) {
        $description = $hook_info_single['description'];

        if ($hook_info_single['core']) {
          // Get MAJOR, MINOR and PATCH components from the current version
          // string.
          [$major, $minor, $patch] = explode('.', \Drupal::VERSION);

          // Documentation URLs on api.drupal.org changed from 9 onwards.
          if ($major >= 9) {
            // MAJOR version string.
            $url_version_suffix = $major;
          }
          else {
            // MAJOR.MINOR.x version string.
            $url_version_suffix = implode('.', [$major, $minor, 'x']);
          }

          // External Uri.
          $url = Url::fromUri('https://api.drupal.org/api/function/' . $hook . '/' . $url_version_suffix);
          $description .= ' ' . Link::fromTextAndUrl(t('[documentation]'), $url)->toString();
        }

        $form['groups'][$group][$group . '_hooks'][$hook]['#description'] = $description;
      }
    }

    $form_state->set('module_builder_groups', array_keys($hook_info));

    $form['#attached']['library'][] = 'module_builder/hooks';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // No validation for hooks, and we need to override the parent method that
    // expects a 'data' form element which this form doesn't have.
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

    // We can't just iterate over $values, because of a core bug with
    // EntityForm, so we need to know which keys to look at.
    // See https://www.drupal.org/node/2665714.
    $groups = $form_state->get('module_builder_groups');

    $hooks = [];
    foreach ($groups as $group) {
      $group_values = $values[$group . '_hooks'];
      // Filter out empty values. (FormAPI *still* doesn't do this???)
      $group_hooks = array_filter($group_values);
      // Store as a numeric array.
      $group_hooks = array_keys($group_hooks);

      $hooks = array_merge($group_hooks, $hooks);;
    }

    $data = $entity->get('data');

    $data['hooks'] = $hooks;
    // Quick hack.
    // TODO: make this generic.
    if ($form_state->hasValue('hook_implementation_type')) {
      $data['hook_implementation_type'] = $form_state->getValue('hook_implementation_type');
    }

    $entity->set('data', $data);
  }

}
