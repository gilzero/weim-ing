<?php

namespace Drupal\module_builder\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Utility\Error;
use MutableTypedData\Data\DataItem;
use MutableTypedData\Exception\InvalidInputException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Generic form for entering a section of data for a component.
 *
 * This determines which properties of the component to show from the values of
 * the entity type's code_builder annotation.
 *
 * @see \Drupal\module_builder\EntityHandler\ComponentSectionFormHandler
 */
class ComponentSectionForm extends ComponentFormBase {

  /**
   * Gets the names of properties this form should show.
   *
   * @return string[]
   *   An array of property names.
   */
  protected function getFormComponentProperties(DataItem $data) {
    // Get the list of component properties this section form uses from the
    // handler, which gets them from the entity type annotation.
    $component_entity_type_id = $this->entity->getEntityTypeId();
    $component_sections_handler = $this->entityTypeManager->getHandler($component_entity_type_id, 'component_sections');

    $operation = $this->getOperation();
    $component_properties_to_use = $component_sections_handler->getSectionFormComponentProperties($operation);
    return $component_properties_to_use;
  }

  /**
   * Title callback.
   *
   * @see \Drupal\module_builder\Routing\ComponentRouteProvider
   */
  public function title(Request $request, $entity_type, $op, $title) {
    // Get the entity request parameter. We can't use it as a function parameter
    // because we want this to work with any entity type.
    $entity = $request->attributes->get($entity_type);
    return $this->t($title, [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form = $this->componentPropertiesForm($form, $form_state);

    $form['#attached']['library'][] = 'module_builder/typed_data_defaults';
    $form['#attached']['drupalSettings']['moduleBuilder']['typedDataDefaults']['defaults'] = [];

    return $form;
  }

  /**
   * Add form elements for the specified component properties.
   *
   * @param $form
   *  The form array.
   * @param FormStateInterface $form_state
   *  The form state object.
   *
   * @return
   *  The form array.
   */
  protected function componentPropertiesForm($form, FormStateInterface $form_state) {
    if (!$form_state->has('data')) {
      // The first time we show this form, create the typed data object.
      $component_data = $this->getComponentDataObject();

      $first_load = TRUE;

      $form_state->set('data', $component_data);
    }
    else {
      // During an AJAX rebuild, get the data from the form state.
      $component_data = $form_state->get('data');
    }

    // Get the properties that this form section should show.
    $component_properties_to_use = $this->getFormComponentProperties($component_data);
    if (!empty($first_load)) {
      // Warn about properties in the entity annotation that are not in the
      // data.
      $undefined_properties = array_diff($component_properties_to_use, array_keys($component_data->getProperties()));

      foreach ($undefined_properties as $property_name) {
        $this->messenger()->addError(t("The property '@name' is not defined in Drupal Code Builder. You should ensure you are using an up-to-date version.", [
          '@name' => $property_name,
        ]));
      }
    }
    $component_properties_to_use = array_intersect($component_properties_to_use, array_keys($component_data->getProperties()));

    // Set #tree on the data element.
    $component_type = $this->entity->getComponentType();
    $form[$component_type]['#tree'] = TRUE;

    foreach ($component_properties_to_use as $property_name) {
      $this->buildFormElement($form[$component_type], $form_state, $component_data->{$property_name});
    }

    // Put the data back into the form state, as the building of the form
    // elements may have caused changes.
    $form_state->set('data', $component_data);

    // Developer trapdoor: disable AJAX for easier debugging of the form.
    if (FALSE) {
      // TODO: AAAAAARGH why can't this be done with Iterator classes???
      static::removeAjax($form);
    }

    return $form;
  }

  /**
   * Helper to remove all ajax from the form.
   *
   * Use this when debugging, as if an ajax request is crashing, it's best to
   * turn ajax off and use normal submission to see the error messages
   * immediately rather than pick them out of the log.
   */
  protected static function removeAjax(&$element) {
    foreach ($element as $key => &$value) {
      if (is_array($value) && isset($value['#ajax'])) {
        unset($value['#ajax']);
      }

      if (is_array($value)) {
        static::removeAjax($value);
      }
    }
  }

  /**
   * Adds the form element for a data item into the form array.
   *
   * This is called recursively for complex and multi-valued data items.
   *
   * @param array &$form
   *   The parent form element (or the entire form), passed by reference. The
   *   data item's element is placed with an array key that is its machine
   *   name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param \MutableTypedData\Data\DataItem $data
   *   The data item.
   */
  protected function buildFormElement(&$form, FormStateInterface $form_state, DataItem $data) {
    $element = [];

    // Determine whether to handle multiple data as a single element or a set
    // of deltas.
    // Multiple-valued data gets a set of items that can be added and removed
    // with AJAX buttons...
    $use_multiple_deltas = $data->isMultiple();
    // ... with exceptions: simple data with options that is multiple-
    // valued is just a SELECT element.
    if (!$data->isComplex() && $data->hasOptions()) {
      $use_multiple_deltas = FALSE;
    }
    // ... multiple simple data without options is shown as a text area.
    if (!$data->isComplex()) {
      $use_multiple_deltas = FALSE;
    }

    // Case 1: multiple deltas each handled as a separate form element, within
    // a details wrapper.
    if ($use_multiple_deltas) {
      $element = $this->buildMultipleDeltaFormElement($form, $form_state, $data);
    }
    // Case 2: complex form element.
    elseif ($data->getDefinition()->isComplex()) {
      $element = $this->buildComplexFormElement($form, $form_state, $data);
    }
    // Case 3: single form element.
    else {
      $element = $this->buildSingleFormElement($form, $form_state, $data);
    }

    // Special case for hooks in a test module, as otherwise they're totally
    // unwieldy.
    if ($data->getName() == 'hooks') {
      $element_inner = $element;
      $element = [
        '#type' => 'details',
        '#title' => $data->getLabel(),
      ];
      $element['inner'] = $element_inner;
      // Force the #parents so the inner element data is saved correctly, as
      // the element overall gets #tree set to TRUE.
      $element['inner']['#parents'] = explode(':', $data->getAddress());
    }

    $element['#tree'] = TRUE;

    $form_key = $data->getName();

    $form[$form_key] = $element;
  }

  protected function getFormElementNameFromData($data_item) {
    $pieces = explode(':', $data_item->getAddress());
    $name = array_shift($pieces);
    foreach ($pieces as $piece) {
      $name .= "[$piece]";
    }
    return $name;
  }

  /**
   * Builds a form element for data that uses a single element.
   *
   * Helper for buildFormElement().
   *
   * Note that buildFormElement() is responsible for some attributes of the
   * element.
   */
  protected function buildSingleFormElement(&$form, FormStateInterface $form_state, DataItem $data) {
    $element = [];

    // Case 3A: element with options.
    if ($data->hasOptions()) {
      $options = [];
      $options_have_descriptions = FALSE;

      foreach ($data->getOptions() as $value => $option) {
        $options[$value] = $option->getLabel();

        if ($description = $option->getDescription()) {
          // Set the description on each value. This is a not-terribly-well
          // documented feature in FormAPI. This relies on us not clobbering
          // $element further on!
          $element[$value]['#description'] = $description;

          $options_have_descriptions = TRUE;
        }
      }
      $options_count = count($options);

      if ($data->isMultiple()) {
        $element_type = 'checkboxes';

        // Build up a default value array for the checkboxes from the data's
        // delta items.
        $default_value = [];
        foreach ($data as $delta => $delta_item) {
          $default_value[$delta_item->value] = $delta_item->value;
        }
      }
      else {
        if ($options_count > 8 && !$options_have_descriptions) {
          $element_type = 'select';
          $default_value = $data->value;
        }
        else {
          $element_type = 'radios';
          $default_value = $data->value;

          // Add a 'None' option to non-required radios, as otherwise it's
          // impossible to unselect something.
          if (!$data->isRequired()) {
            $options = [
              '' => $this->t('None'),
            ] + $options;

            // The incoming value will be NULL; set it to an empty string so
            // the 'None' radio is selected.
            if (empty($default_value)) {
              $default_value = '';
            }
          }
        }
      }

      $element += [
        '#type' => $element_type,
        '#title' => $data->getLabel(),
        '#description' => $data->getDescription(),
        '#default_value' => $default_value,
        '#options' => $options,
        // ARGH why isn't this happening automatically like it's supposed to?
        '#empty_option' => $data->isRequired() ? $this->t('- Select -') : $this->t('- None -'),
        '#empty_value' => NULL,
        '#description_display' => 'before',
      ];

      // Use an autocomplete for multi-valued elements with large option
      // lists. This is typically injected services.
      if (count($options) > 20 && $data->isMultiple()) {
        $element['#type'] = 'textfield';
        // This needs to be massive to allow lots of services!
        $element['#maxlength'] = 512;

        $element['#description'] .= ' ' . $this->t("Enter a comma-separated list of names.");

        $element['#default_value'] = implode(', ', $default_value);

        $element['#autocomplete_route_name'] = 'module_builder.autocomplete';
        $element['#autocomplete_route_parameters'] = [
          'property_address' => $data->getAddress(),
        ];

        // Remove the options, as it makes FormAPI think the value must be
        // compared against them.
        unset($element['#options']);
      }

      if ($data->isVariantProperty()) {
        // Put this above the 'Update variant properties' button; compare
        // with the weight set on that.
        $element['#weight'] = -20;

        $wrapper_id = Html::getId($data->getParent()->getAddress() . '-mutable-wrapper');

        $variant_property_form_address = explode(':', $data->getAddress());
        $variant_property_address = array_merge(
          $variant_property_form_address
        );
        $values = $form_state->getValues();
        $variant_value = NestedArray::getValue($values, $variant_property_address);

        if (isset($variant_value)) {
          $data->set($variant_value);
        }
      }
    }
    // Case 3B: boolean element.
    elseif ($data->getType() == 'boolean') {
      $element += [
        '#type' => 'checkbox',
        '#title' => $data->getLabel(),
        '#description' => $data->getDescription(),
        '#default_value' => $data->value,
      ];
    }
    // Case 3C: multi-valued plain data.
    elseif ($data->isMultiple()) {
      $element += [
        '#type' => 'textarea',
        '#title' => $data->getLabel(),
        '#description' => $data->getDescription(),
        '#default_value' => implode("\n", $data->export()),
      ];
    }
    // Case 3D: everything else!
    else {
      $element += [
        '#type' => 'textfield',
        '#title' => $data->getLabel(),
        '#description' => $data->getDescription(),
        '#default_value' => $data->value,
      ];
    }

    // Make form elements required if the data is required, unless there is
    // a default, in which case, either the JS will set it, or data validation
    // will set it on submission, so there's no need to force the user to
    // enter something.
    if ($data->isRequired() && !$data->getDefault()) {
      $element['#required'] = TRUE;
    }

    $element['#attributes']['data-typed-data-address'] = $data->getAddress();

    // dsm($data->getDefault());

    // Note need parentheses around the assignment because of precedence
    // relative to &&.
    if (($default = $data->getDefault()) && $default->getType() == 'expression') {
      $expression = $default->getExpressionWithAbsoluteAddresses($data);

      // Prefix custom EL functions with the JS namespace.
      // TODO: Would be nice to get the names from the EL rather than
      // hardcode them!
      $expression = str_replace('get(', 'DataAddressExpressionLanguage.get(', $expression);
      $expression = str_replace('machineToClass(', 'DataAddressExpressionLanguage.machineToClass(', $expression);
      $expression = str_replace('machineToLabel(', 'DataAddressExpressionLanguage.machineToLabel(', $expression);
      $expression = str_replace('stripBefore(', 'DataAddressExpressionLanguage.stripBefore(', $expression);

      $dependencies = $default->getDependencies();
      if (!empty($dependencies)) {
        // CHEAT; for now only ever one dependency!
        // TODO: this only works for addresses that go up only one level!
        $dependencies[0] = str_replace('..:', $data->getParent()->getAddress() . ':', $dependencies[0]);
      }

      $element['#attached']['drupalSettings']['moduleBuilder']['typedDataDefaults']['defaults'][$data->getAddress()] = [
        'dependencies' => $dependencies,
        'expression' => $expression,
      ];

      foreach ($dependencies as $dependency) {
        $element['#attached']['drupalSettings']['moduleBuilder']['typedDataDefaults']['reactions'][$dependency] = $data->getAddress();
      }
    }

    return $element;
  }

  /**
   * Builds a multi-valued form element.
   *
   * Helper for buildFormElement().
   *
   * Note that buildFormElement() is responsible for some attributes of the
   * element.
   */
  protected function buildMultipleDeltaFormElement(&$form, FormStateInterface $form_state, DataItem $data) {
    // Set up a wrapper for AJAX.
    $wrapper_id = Html::getId($data->getAddress() . '-add-more-wrapper');

    // Use 'details' rather than 'container' so there's a visual indicator
    // of the multi-valued property.
    $element = [
      '#type' => 'details',
      '#title' => $data->getLabel(),
      '#open' => TRUE,
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];

    foreach ($data as $delta => $delta_item) {
      $this->buildFormElement($element, $form_state, $delta_item);

      // Set the label on each delta item to differentiate it from the overall
      // element label.
      $element[$delta]['#title'] = $delta_item->getLabel();

      // Doesn't work; see removeItemSubmit().
      // $element[':' . $delta_item->getName() . '_remove_button'] = [
      //   '#type' => 'submit',
      //   // Needs to be full address for uniquess in the whole form.
      //   '#name' => $data->getAddress() . '_remove_item',
      //   '#value' => t('Remove item'),
      //   // Hack?
      //   '#input' => $delta,
      //   '#limit_validation_errors' => [],
      //   '#submit' => ['::removeItemSubmit'],
      //   '#ajax' => [
      //     'callback' => '::itemButtonAjax',
      //     'wrapper' => $wrapper_id,
      //     'effect' => 'fade',
      //   ],
      // ];
    }

    if ($data->mayAddItem()) {
      if (count($data)) {
        $button_label = $this->t('Add another @label item', [
          '@label' => $data->getLabel(),
        ]);
      }
      else {
        $button_label = $this->t('Add a @label item', [
          '@label' => $data->getLabel(),
        ]);
      }

      $element[':add_button'] = [
        '#type' => 'submit',
        // This allows FormAPI to figure out which button is the triggering
        // element. The name must be unique across all buttons in the form,
        // otherwise, the first matching name will be taken by FormAPI as being
        // the button that was clicked, with unexpected results.
        // See \Drupal\Core\Form\FormBuilder::elementTriggeredScriptedSubmission().
        '#name' => $data->getAddress() . '_add_more',
        '#value' => $button_label,
        '#limit_validation_errors' => [],
        '#submit' => ['::addItemSubmit'],
        '#data_address' => $data->getAddress(),
        '#ajax' => [
          'callback' => '::itemButtonAjax',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    if (count($data)) {
      $element[':remove_button'] = [
        '#type' => 'submit',
        // Needs to be full address for uniquess in the whole form.
        '#name' => $data->getAddress() . '_remove_item',
        '#value' => $this->t('Remove last item'),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeItemSubmit'],
        '#data_address' => $data->getAddress(),
        '#ajax' => [
          'callback' => '::itemButtonAjax',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    return $element;
  }

  /**
   * Builds a form element with multiple child elements.
   *
   * Helper for buildFormElement().
   *
   * Note that buildFormElement() is responsible for some attributes of the
   * element.
   */
  protected function buildComplexFormElement(&$form, FormStateInterface $form_state, DataItem $data) {
    // Set up a wrapper for AJAX.
    $wrapper_id = Html::getId($data->getAddress() . '-complex-wrapper');

    $element = [
      '#type' => 'details',
      '#title' => $data->getLabel(),
      '#open' => TRUE,
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];

    // Don't show an optional and single-valued complex element until the user
    // requests it and it gets set. This is to keep the form clear, and to
    // prevent validation errors of the complex element has required properties
    // but the user doesn't want it.
    if (!$data->isRequired() && !$data->isSet() && !$data->isDelta()) {
      $element[':add_button'] = [
        '#type' => 'submit',
        // This allows FormAPI to figure out which button is the triggering
        // element. The name must be unique across all buttons in the form,
        // otherwise, the first matching name will be taken by FormAPI as being
        // the button that was clicked, with unexpected results.
        // See \Drupal\Core\Form\FormBuilder::elementTriggeredScriptedSubmission().
        '#name' => $data->getAddress() . '_add',
        '#value' => $this->t('Add @component', [
          '@component' => $data->getLabel(),
        ]),
        '#limit_validation_errors' => [],
        '#submit' => ['::addComplexDataSubmit'],
        '#data_address' => $data->getAddress(),
        '#ajax' => [
          'callback' => '::complexButtonAjax',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];

      return $element;
    }

    foreach ($data as $data_item) {
      $this->buildFormElement($element, $form_state, $data_item);
    }

    if (!$data->isRequired() && !$data->isEmpty() && !$data->isDelta()) {
      $element[':remove_button'] = [
        '#type' => 'submit',
        // This allows FormAPI to figure out which button is the triggering
        // element. The name must be unique across all buttons in the form,
        // otherwise, the first matching name will be taken by FormAPI as being
        // the button that was clicked, with unexpected results.
        // See \Drupal\Core\Form\FormBuilder::elementTriggeredScriptedSubmission().
        '#name' => $data->getAddress() . '_remove',
        '#value' => $this->t('Remove @component', [
          '@component' => $data->getLabel(),
        ]),
        '#limit_validation_errors' => [],
        '#submit' => ['::removeComplexDataSubmit'],
        '#data_address' => $data->getAddress(),
        '#ajax' => [
          'callback' => '::complexButtonAjax',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    // NO! has to go immediately after the variant property!
    if ($data->isMutable()) {
      /** @var \MutableTypedData\Data\MutableData $data */
      if (count($data->getProperties()) == 1 && $data->getVariantData()->value) {
        $element['count_notice'] = [
          '#type' => 'container',
          'notice' => [
            '#plain_text' => $this->t("The '@value' @variant variant has no additional properties.", [
              // TODO: add a better way of getting the option label to MTD.
              '@value' => $data->getVariantData()->getOptions()[$data->getVariantData()->value]->getLabel(),
              '@variant' => $data->getVariantData()->getLabel(),
            ]),
          ],
        ];
      }

      // Set up a wrapper for AJAX.
      // Note that we don't to use Html::getUniqueId() because the data's
      // address is already unique, and furthermore, we don't WANT uniqueness
      // because we want the same data item to produce the same HTML ID when
      // we're looking at the variant property form element further on.
      // TODO: this assumes only one root data item in the form, as another
      // data item could have the same addresses!
      $wrapper_id = Html::getId($data->getAddress() . '-mutable-wrapper');

      $element['#attributes'] = [
        'id' => $wrapper_id,
      ];

      // WARNING: assumes the form structure!
      $mutable_property_form_address = explode(':', $data->getAddress());
      $variant_property_address = array_merge(
        $mutable_property_form_address,
        [$data->getVariantData()->getName()]
      );

      // The text for the 'update variant' button depends on whether data has
      // been set yet, and whether there is data that a change of variant would
      // delete.
      if ($data->isEmpty()) {
        $update_variant_button_label = $this->t('Set @variant variant', [
          '@variant' => $data->getVariantData()->getLabel(),
        ]);
      }
      else {
        if (count($data->getProperties()) == 1) {
          $update_variant_button_label = $this->t('Change @variant variant', [
            '@variant' => $data->getVariantData()->getLabel(),
          ]);
        }
        else {
          $update_variant_button_label = $this->t("Change @variant variant and delete '@value' data for this item", [
            '@variant' => $data->getVariantData()->getLabel(),
            '@value' => $data->getVariantData()->getOptions()[$data->getVariantData()->value]->getLabel(),
          ]);
        }
      }

      $element[':update_variant'] = [
        '#type' => 'submit',
        // Needs to be full address for uniquess in the whole form.
        '#name' => $data->getAddress() . '_update_variant',
        '#value' => $update_variant_button_label,
        // We need to validate the variant property so we get its value in the
        // submit handler for this button.
        '#limit_validation_errors' => [
          $variant_property_address,
        ],
        '#element_validate' => ['::updateVariantValidate'],
        '#submit' => ['::updateVariantSubmit'],
        '#data_address' => $data->getVariantData()->getAddress(),
        '#variant_data_name' => $data->getVariantData()->getName(),
        '#ajax' => [
          'callback' => '::variantButtonAjax',
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
        '#prefix' => '<div>',
        '#suffix' => '</div>',
        '#weight' => -10,
      ];

      // Also tweak the weight of the variant property so it goes above the
      // button to change the variant.
      $variant_property_name = $data->getVariantData()->getName();
      $element[$variant_property_name]['#weight'] = -20;
    }

    return $element;
  }

  /**
   * Submission handler for the "Add another item" buttons.
   */
  public static function addItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Get the data item, using the address set in the button.
    $data = $form_state->get('data');
    $data_item = $data->getItem($button['#data_address']);

    // Add a new delta item.
    $data_item->createItem();

    $form_state->set('data', $data);

    $form_state->setRebuild();
  }

  /**
   * Submission handler for the "Remove item" buttons.
   */
  public static function removeItemSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Get the data item, using the address set in the button.
    $data = $form_state->get('data');
    $multiple_data_item = $data->getItem($button['#data_address']);
    // dsm($multiple_data_item);

    // We could remove any of the items here, but the problem is then that
    // FormAPI appears to put the currently entered values back into the form
    // elements whose deltas have closed the gap, which makes it look like it
    // was the last one that was removed anyway.
    $last_delta = count($multiple_data_item) - 1;
    unset($multiple_data_item[$last_delta]);

    $form_state->set('data', $data);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the item count buttons.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function itemButtonAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go up in the form, to the widgets container.
    $button_array_parents = $button['#array_parents'];
    $widgets_container_parents = array_slice($button_array_parents, 0, -1);

    $element = NestedArray::getValue($form, $widgets_container_parents);

    return $element;
  }

  public static function addComplexDataSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    $data = $form_state->get('data');
    $complex_data_item = $data->getItem($button['#data_address']);

    // Set the data to an empty array.
    // Don't use access to instantiate, as that causes problems with recursion:
    // see https://github.com/joachim-n/mutable-typed-data/issues/5.
    $complex_data_item->set([]);

    $form_state->set('data', $data);

    $form_state->setRebuild();
  }

  public static function removeComplexDataSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    $data = $form_state->get('data');
    $complex_data_item = $data->getItem($button['#data_address']);

    $complex_data_item->unset();

    $form_state->set('data', $data);

    $form_state->setRebuild();
  }

  public static function complexButtonAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go up in the form, to the widgets container.
    $button_array_parents = $button['#array_parents'];
    $widgets_container_parents = array_slice($button_array_parents, 0, -1);

    $element = NestedArray::getValue($form, $widgets_container_parents);

    return $element;
  }

  /**
   * Validate handler for the update variant button.
   *
   * This removes variant-dependent values if the variant has changed.
   */
  public static function updateVariantValidate(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // WTF FormAPI?
    // Why does this
    if (array_pop($button['#parents']) != ':update_variant') {
      return;
    }

    // Get the value of the mutable data variant property.
    $values_address = $button['#parents'];
    $values_address[] = $button['#variant_data_name'];

    $values = $form_state->getValues();
    $submitted_variant_value = NestedArray::getValue($values, $values_address);

    // Checking the variant property form element is #required hasn't happened
    // yet at this point. So bail and let FormAPI set a form error.
    if (empty($submitted_variant_value)) {
      return;
    }

    // Get the containing variant data item, using the address set in the
    // button.
    $data = $form_state->get('data');
    $variant_data_item = $data->getItem($button['#data_address']);

    // Clean up the values if the current submission is changing the variant
    // property.
    // We can't determine whether this is happening by comparing the form
    // state's variant value with the data item's variant value, because
    // validateForm()'s call to CleanUpValues() has already set the values on
    // the data item, and then set that back in the form state. Which is ugly,
    // because it means a button validator such as this one doesn't have a
    // proper picture of what is going on. TODO: look at core entity forms and
    // see whether they set the entity back on the form after building it in
    // validation. A quick look suggests that they don't.
    $variant_properties = $variant_data_item->getParent()->getProperties();

    $complex_values_address = $button['#parents'];
    $complex_values = NestedArray::getValue($values, $complex_values_address);

    $cleaned_complex_values = array_intersect_key($complex_values, $variant_properties);
    NestedArray::setValue($values, $complex_values_address, $cleaned_complex_values);

    $form_state->setValues($values);

    // TODO: this bit WOULD work if validateForm() weren't updating the data
    // item stored in the form state. As it stands, it does nothing because
    // the two values will be equal even when the variant is being changed.
    if ($submitted_variant_value != $variant_data_item->value) {
      $complex_values_address = $button['#parents'];

      $complex_values = NestedArray::getValue($values, $complex_values_address);

      $complex_values = array_intersect_key($complex_values, [$button['#variant_data_name'] => TRUE]);

      NestedArray::setValue($values, $complex_values_address, $complex_values);

      $form_state->setValues($values);
    }
  }

  /**
   * Submission handler for the "Update variants" buttons.
   */
  public static function updateVariantSubmit(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    // dsm($button);

    // Get the value of the mutable data variant property.
    $values_address = array_slice($button['#parents'], 0, -1);
    $values_address[] = $button['#variant_data_name'];

    // Get the containing variant data item, using the address set in the
    // button.
    $data = $form_state->get('data');
    $variant_data_item = $data->getItem($button['#data_address']);

    $values = $form_state->getValues();

    $variant_value = NestedArray::getValue($values, $values_address);

    $variant_data_item->value = $variant_value;

    $form_state->set('data', $data);
    // dsm($data);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the variant buttons.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function variantButtonAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();

    // Go up in the form, to the widgets container.
    $button_array_parents = $button['#array_parents'];
    // Get the address of the containing multiple data item.
    // WARNING: this assumes the 'data' form element is at the top in the
    // form structure!
    $widgets_container_parents = array_slice($button_array_parents, 0, -1);

    $element = NestedArray::getValue($form, $widgets_container_parents);

    return $element;
  }

  /**
   * Ajax callback for the variant elements.
   *
   * This returns the new page content to replace the page content made obsolete
   * by the form submission.
   */
  public static function variantElementAjax(array $form, FormStateInterface $form_state) {
    $variant_element = $form_state->getTriggeringElement();

    // Go up in the form, to the widgets container.
    $variant_element_array_parents = $variant_element['#array_parents'];
    // Get the address of the containing multiple data item.
    // WARNING: this assumes the 'data' form element is at the top in the
    // form structure!

    if ($variant_element['#type'] == 'radios') {
      $widgets_container_parents = array_slice($variant_element_array_parents, 0, -2);
    }
    elseif ($variant_element['#type'] == 'select') {
      $widgets_container_parents = array_slice($variant_element_array_parents, 0, -1);
    }

    $element = NestedArray::getValue($form, $widgets_container_parents);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $data = $form_state->get('data');

    // EntityForm::submitForm() has already called $form_state->cleanValues().
    $data_values = $form_state->getValue($data->getName());

    // ARGH. Because FormValidator does general validation before element or
    // button validation, we call this BEFORE button-specific validation can do
    // things to clean up things specific to the button's action, such as
    // removing data for a changed variant.
    $this->cleanUpValues($data_values, $data);

    // Clear the properties we use in this form, so we don't merge with what's
    // already there. Note that $data is initially loaded from the component
    // entity when the form is built.
    $component_properties_to_use = $this->getFormComponentProperties($data);
    foreach ($component_properties_to_use as $property_name) {
      // dsm("CLEAR $property_name");
      $data->removeItem($property_name);
    }

    try {
      $data->set($data_values);
    }
    catch (InvalidInputException $e) {
      $form_state->setError($form, $this->t("There was a problem with the form data."));
    }

    // Validate the data and set any violations as form errors.
    // TODO: we've validating the whole data, some of which doesn't appear on
    // the form -- but there shouldn't be violations outside of the form, since
    // those would have been caught when their form page was saved! In theory!
    $violations = $data->validate();
    foreach ($violations as $address => $violation_messages) {
      $form_address = explode(':', $address);

      // Special case for the component root name, as that uses a dedicated
      // form element.
      if (isset($form_address[1]) && $form_address[1] == 'root_name') {
        $form_address = ['id'];
      }

      $key_exists = NULL;
      $form_element = NestedArray::getValue($form, $form_address, $key_exists);

      // Some form elements group all the deltas of a data item together, such
      // as injected services and textareas. In that case, there is no element
      // for the actual delta, and the error should be set on the parent
      // address.
      if (!$key_exists) {
        array_pop($form_address);
        // dsm($form_address);
        $form_element = NestedArray::getValue($form, $form_address, $key_exists);
      }

      // Filter the violations to those elements shown on this form section.
      // The 2nd element of the address corresponds to the names in the
      // $component_properties_to_use array (since the 1st element is 'module).
      if (count($form_address) > 1 && !in_array($form_address[1], $component_properties_to_use)) {
        continue;
      }

      if (empty($form_element)) {
        $form_element = $form;
      }

      foreach ($violation_messages as $violation_message) {
        // Special handling for an unrecognised value, as this is often caused
        // by analysis data being out of date. Hacky sniffing of the error
        // message!
        if (str_contains($violation_message, 'not one of the options')) {
          // Putting the string inside the TranslatableMarkup is bad, but the
          // alternative of concatenating stringifies the TranslatableMarkup and
          // then causes the HTML of the link to be escaped.
          $violation_message = new TranslatableMarkup($violation_message . ' ' . 'Perhaps you need to <a href=":url">re-run code analysis</a>?', [
            ':url' => Url::fromRoute('module_builder.analyse')->toString(),
          ]);
        }

        $form_state->setError($form_element, $violation_message);
      }
    }

    // TODO: not sure we should do this here! it means that element and button
    // validators have an incorrect picture of what is going on!
    // TODO: figure out why values filled in by validation don't make it to the
    // form at this point!
    $form_state->set('data', $data);
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
    $data = $form_state->get('data');

    // EntityForm::submitForm() has already called $form_state->cleanValues().
    $data_values = $form_state->getValue($data->getName());

    $this->cleanUpValues($data_values, $data);

    // We need to preserve any data for properties not on this form, but
    // completely clear properties that are on this form so we don't merge into
    // any existing data.
    // Note that $data is initially loaded from the component entity when the
    // form is built.
    $component_properties_to_use = $this->getFormComponentProperties($data);
    foreach ($component_properties_to_use as $property_name) {
      $data->removeItem($property_name);
    }

    try {
      $data->set($data_values);
    }
    catch (InvalidInputException $e) {
      \Drupal\Component\Utility\DeprecationHelper::backwardsCompatibleCall(
        \Drupal::VERSION,
        '10.1.0',
        fn() => Error::logException(\Drupal::logger('module_builder'), $e),
        fn() => watchdog_exception('module_builder', $e)
      );
      $this->messenger()->addError($this->t("There was a problem with the form data. The component was not saved."));
      return;
    }

    // Set the name and ID, which use form elements outside of MTD.
    // This only applies to the 'name' component section form.
    if ($form_state->getValue('id')) {
      $data->root_name = $form_state->getValue('id');
      $data->readable_name = $form_state->getValue('name');
    }

    // Let validation fill in required values with defaults, which we didn't
    // mark as required in the form.
    $data->validate();

    $data_export = $data->export();

    $entity->set('data', $data_export);
  }

  /**
   * Recursively clean up the submitted form values.
   *
   * Note that this is called by validateForm(), and so is run BEFORE any
   * button-specific validators. This means that it can't rely on any action-
   * specific clean up of values.
   *
   * @param array &$array
   *   An array of form values, passed by reference. This will be altered in
   *   place.
   * @param \MutableTypedData\Data\DataItem $data
   *   The data item that corresponds to the form values array.
   */
  protected function cleanUpValues(&$array, DataItem $data) {
    // Clean up the data for the old variant if mutable data is having its
    // variant changed.
    if ($data->isMutable() && !$data->isMultiple()) {
      $variant_property_name = $data->getVariantData()->getName();

      if ($array[$variant_property_name] != $data->getVariantData()->value) {
        // The variant property has been changed. Remove everything from the
        // array except for the variant property.
        $array = [
          $variant_property_name => $array[$variant_property_name],
        ];
      }
    }


    foreach ($array as $key => &$value) {
      // Remove buttons.
      if (substr($key, 0, 1) == ':') {
        unset($array[$key]);
        continue;
      }

      // Single checkbox.
      // TODO ARRRGH can't figure out how to safely get a child item in all
      // circumstances.
      // See https://github.com/joachim-n/mutable-typed-data/issues/3
      if (!is_numeric($key) && $data->{$key}->getType() == 'boolean') {
        $array[$key] = (bool) $array[$key];
        continue;
      }

      // Remove empty values, so for example, empty checkboxes in a set don't
      // try to set an empty value on the data item.
      if (is_null($value)) {
        unset($array[$key]);
        continue;
      }

      // Remove empty mutable data. This means that we remove the empty option
      // during an AJAX call to add a delta to a multi-valued mutable item,
      // which can't be set on the data as a variant can't be set to an empty
      // value.
      // TODO: needs test coverage.
      if ($data->isMutable()) {
        if (empty($value)) {
          unset($array[$key]);
          continue;
        }
      }

      if (is_array($value)) {
        if (is_numeric($key)) {
          $this->cleanUpValues($value, $data[$key]);
        }
        elseif ($data->{$key}->hasOptions()) {
          // We're dealing with checkboxes. Convert the keyed array to an numeric
          // array.
          $value = array_values(array_filter($value));
        }
        else {
          $this->cleanUpValues($value, $data->{$key});
        }

        // If the value array is now empty (because recursively cleaning it has
        // removed all its keys), remove it.
        if (empty($value)) {
          unset($array[$key]);
        }

        // For FKW reasons, the form state values get order mixed up when
        // the variant type is set on an additional variant. The new value is
        // first in the array, which MTD won't accept because it expects deltas
        // to be in the correct order. Values are in the right order in
        // updateVariantSubmit(), but in the wrong order when
        // when copyFormValuesToEntity() is called.
        // I've given up figuring out why FormAPI does this so here is a hack
        // to fix the values.
        // See https://www.drupal.org/project/module_builder/issues/3173604
        if (!empty($value) && is_numeric(array_keys($value)[0])) {
          ksort($value);
        }
      }
      else {
        // Some single values need special handling too.
        if ($data->{$key}->isMultiple() && !$data->{$key}->isComplex() && !$data->{$key}->hasOptions()) {
          // Handle a textarea.
          // Text area line breaks are weird, apparently.
          $values = preg_split("@[\r\n]+@", $value);

          $values = array_filter($values);

          $array[$key] = $values;
        }
        elseif ($data->{$key}->isMultiple() && !$data->{$key}->isComplex() && $data->{$key}->hasOptions() && is_string($value)) {
          // Form elements that use an autocomplete will produce a single string
          // value that needs to be split if the data is multi-valued.
          // Autocomplete is used if the data is non-complex and has options.
          $value = preg_split("@[,]+@", $value);
          $value = array_filter($value);
          $array[$key] = array_map('trim', $value);
        }
        elseif (!is_numeric($key) && $data->{$key}->hasOptions() && !$data->{$key}->isRequired()) {
          // Options elements that are not required should be cleaned up, so we
          // don't set an empty string as the data value.
          if ($value == '' || $value == NULL) {
            unset($array[$key]);
          }
        }
      }
    }
  }

  /**
   * Builds the form element for a component.
   *
   * This builds the root level form element, or an element for any part of
   * the property info array that is an array of properties. This is recursed
   * into by elementCompound().
   *
   * @param array $property_address
   *  The property address for the component. This is an array that gives the
   *  location of this component's properties list in the complete property info array
   *  in static::$componentDataInfo. For the root, this will be an empty array;
   *  for a child compound property this will be an address of the form
   *  parent->properties->child->properties.
   * @param array $value_address
   *  The value address for the form element to be created. This is similar to
   *  the property address, but will include items for compound property deltas.
   *  This ensures that buttons and item counts in form storage are unique for
   *  compound elements which are themselves children of multi-valued compound
   *  elements.
   * @param $form_value_address
   *  The form values address for the component. This is used to set the
   *  #parents property on the form element we create, so that the form values
   *  structure matches the original data structure. This is different again
   *  from the other two addresses, as it does not include a level for the
   *  'properties' array, but does include deltas.
   *
   * @return array
   *   The form array for the component's element.
   */
  // TODO: mine this and helpers for old code.
  private function getCompomentElement($form_state, $property_address, $value_address, $form_value_address) {
    $component_element = [];

    $properties = NestedArray::getValue(static::$componentDataInfo, $property_address);

    // TODO: should this be carried through? Check whether preparing a compound
    // property can set values in the array.
    $component_data = [];

    foreach ($properties as $property_name => &$property_info) {
      // Prepare the single property: get options, default value, etc.
      $this->codeBuilderTaskHandlerGenerate->prepareComponentDataProperty($property_name, $property_info, $component_data);

      // Skip the properties that we're not showing on this form section.
      if (!empty($property_info['hidden'])) {
        continue;
      }

      // Add the name of the current property to the address arrays.
      $property_component_address = $property_address;
      $property_component_address[] = $property_name;

      $property_value_address = $value_address;
      $property_value_address[] = $property_name;

      $property_form_value_address = $form_value_address;
      $property_form_value_address[] = $property_name;

      // Create a basic form element for the property.
      $property_element = [
        '#title' => $property_info['label'],
        '#required' => $property_info['required'],
        '#mb_property_address' => $property_component_address,
        '#mb_value_address' => $property_value_address,
        // Explicitly set this so we control the structure of the form
        // submission values. In particular, we don't want to have to pick data
        // out from the the structure the 'table' element would create.
        '#parents' => $property_form_value_address,
      ];

      if (isset($property_info['description'])) {
        $property_element['#description'] = $property_info['description'];
      }

      // Add description to properties that can get defaults filled in by
      // DCB in processing.
      if (!empty($property_info['process_default'])) {
        $property_element['#required'] = FALSE;
        $property_element['#description'] = (isset($property_element['#description']) ? $property_element['#description'] . ' ' : '')
          . t("Leave blank for a default value.");
      }

      // Determine the default value to present in the form element.
      // (Compound elements don't have a default value as they are just
      // containers, but we use the count of the array we get to determine how
      // many deltas to show.)
      $key_exists = NULL;
      $form_default_value = NestedArray::getValue($this->moduleEntityData, array_slice($property_form_value_address, 1), $key_exists);
      // If there is no value set in the module entity data, take the default
      // value that prepareComponentDataProperty() set.
      if (!$key_exists) {
        $form_default_value = $component_data[$property_name];

        if ($property_info['format'] == 'compound') {
          // Bit of a hack: for compound properties, zap the prepared default.
          // The problem is that this will cause a child element to appear in
          // the form, rather than starting with a zero delta.
          // This happens for example with the PHPUnit test component, where
          // the prepared default for the test_modules property tries to set a
          // module name derived from the test class name.
          // This will be fixed in DCB 3.3.x when we get the ability to do
          // defaults in JS.
          $form_default_value = [];
        }
      }

      // The type of the form element depends on the format of the component data
      // property.
      $format = $property_info['format'];
      $format_method = 'element' . ucfirst($format);
      if (!method_exists($this, $format_method)) {
        throw new \Exception("No method '$format_method' exists to handle property '$property_name' with format '$format'.");
        continue;
      }

      $handling = $this->{$format_method}($property_element, $form_state, $property_info, $form_default_value);

      $property_form_value_address_key = implode(':', $property_form_value_address);
      $form_state->set(['element_handling', $property_form_value_address_key], $handling);

      $component_element[$property_name] = $property_element;
    }

    return $component_element;
  }

  /**
   * Set form element properties specific to array component properties.
   *
   * @param &$element
   *  The form element for the component property.
   * @param FormStateInterface $form_state
   *  The form state.
   * @param $property_info
   *  The info array for the component property.
   * @param $form_default_value
   *  The default value for the form element.
   *
   * @return string
   *  The handling type to be applied to this element's value on submit.
   */
  protected function XelementArray(&$element, FormStateInterface $form_state, $property_info, $form_default_value) {
    if (isset($property_info['options'])) {
      if (isset($property_info['options_extra'])) {
        // Show an autocomplete textfield.
        // TODO: use Select or Other module for this when it has a stable
        // release.
        $element['#type'] = 'textfield';
        $element['#maxlength'] = 512;

        $element['#description'] = (isset($element['#description']) ? $element['#description'] . ' ' : '')
          . t("Enter multiple values separated with a comma.");

        $element['#autocomplete_route_name'] = 'module_builder.autocomplete';
        $element['#autocomplete_route_parameters'] = [
          'property_address' => implode(':', $element['#mb_property_address']),
        ];

        if ($form_default_value) {
          $form_default_value = implode(', ', $form_default_value);
        }

        $handling = 'autocomplete';
      }
      else {
        $element['#type'] = 'checkboxes';
        $element['#options'] = $property_info['options'];

        if (is_null($form_default_value)) {
          $form_default_value = [];
        }
        else {
          $form_default_value = array_combine($form_default_value, $form_default_value);
        }

        $handling = 'checkboxes';
      }
    }
    else {
      $element['#type'] = 'textarea';
      if (isset($element['#description'])) {
        $element['#description'] .= ' ';
      }
      else {
        $element['#description'] = '';
      }
      $element['#description'] .= t("Enter one item per line.");

      // Handle a property that DCB has added since the component was saved.
      if (empty($form_default_value) && !is_array($form_default_value)) {
        $form_default_value = [];
      }

      $form_default_value = implode("\n", $form_default_value);

      $handling = 'textarea';
    }

    $element['#default_value'] = $form_default_value;

    return $handling;
  }

  /**
   * Set form element properties specific to boolean component properties.
   *
   * @param &$element
   *  The form element for the component property.
   * @param FormStateInterface $form_state
   *  The form state.
   * @param $property_info
   *  The info array for the component property.
   * @param $form_default_value
   *  The default value for the form element.
   *
   * @return string
   *  The handling type to be applied to this element's value on submit.
   */
  protected function XelementBoolean(&$element, FormStateInterface $form_state, $property_info, $form_default_value) {
    $element['#type'] = 'checkbox';

    $element['#default_value'] = $form_default_value;

    return 'checkbox';
  }

  /**
   * Set form element properties specific to compound component properties.
   *
   * @param &$element
   *  The form element for the component property.
   * @param FormStateInterface $form_state
   *  The form state.
   * @param $property_info
   *  The info array for the component property.
   * @param $form_default_value
   *  The default value for the form element.
   *
   * @return string
   *  The handling type to be applied to this element's value on submit.
   */
  protected function XelementCompound(&$element, FormStateInterface $form_state, $property_info, $form_default_value) {
    // A compound property shows a details element, for which we recurse and
    // show another component.
    $element['#type'] = 'details';
    $element['#open'] = TRUE;

    // Figure out how many items to show.
    // If we're reloading the form in response to the 'add more' button, then
    // form storage dictates the item count.
    // If there's nothing set in form storage yet, it's the first time we're
    // here and the number of items in the entity tells us how many items to
    // show in the form.
    // Finally, if that's empty, then show no items, just a button to add one.
    $item_count = static::getCompoundPropertyItemCount($form_state, $element['#mb_value_address']);
    if (is_null($item_count)) {
      $item_count = count($form_default_value);
      static::setCompoundPropertyItemCount($form_state, $element['#mb_value_address'], $item_count);
    }
    if (empty($item_count)) {
      $item_count = 0;
      static::setCompoundPropertyItemCount($form_state, $element['#mb_value_address'], $item_count);
    }

    // Property cardinality overrides anything else.
    if (isset($property_info['cardinality'])) {
      $item_count = min($item_count, $property_info['cardinality']);

      if ($item_count == $property_info['cardinality']) {
        // We're at the maximum item count.
        $add_more = FALSE;
      }
      else {
        // We're not yet at the cardinality: we can add more.
        $add_more = TRUE;
      }
    }
    else {
      // Unlimited cardinality: can always add more.
      $add_more = TRUE;
    }

    // Set up a wrapper for AJAX.
    $wrapper_id = Html::getUniqueId(implode('-', $element['#mb_value_address']) . '-add-more-wrapper');
    // TODO - use   '#type' => 'container',?
    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';

    // Show the items in a table. This is single-column, with all child
    // properties in the one cell, but we just want the striping for visual
    // clarity.
    $element['table'] = array(
      '#type' => 'table',
    );

    // The address in the properties array to find this component's properties
    // list.
    $component_properties_address = $element['#mb_property_address'];
    $component_properties_address[] = 'properties';

    $component_value_address = $element['#mb_value_address'];
    $component_value_address[] = 'properties';

    $property_form_value_address = $element['#parents'];

    for ($delta = 0; $delta < $item_count; $delta++) {
      $row = [];

      $delta_value_address = $component_value_address;
      $delta_value_address[] = $delta;

      $delta_form_value_address = $property_form_value_address;
      $delta_form_value_address[] = $delta;

      // Put all the properties into a single cell so it's a 1-column table.
      // TODO: WTF NO STRIPING IN SEVEN THEME???
      $delta_component_element = $this->getCompomentElement($form_state, $component_properties_address, $delta_value_address, $delta_form_value_address, []);

      $row['row'] = $delta_component_element;
      $element['table'][$delta] = $row;
    }

    if ($add_more) {
      // Show a button to add items, if they can be added.
      $button_text = ($item_count == 0)
        ? t('Add a @label item', [
          '@label' => strtolower($property_info['label']),
        ])
        : t('Add another @label item', [
          '@label' => strtolower($property_info['label']),
        ]);

      $element['actions']['add'] = array(
        '#type' => 'submit',
        // This allows FormAPI to figure out which button is the triggering
        // element. The name must be unique across all buttons in the form,
        // otherwise, the first matching name will be taken by FormAPI as being
        // the button that was clicked, with unexpected results.
        // See \Drupal\Core\Form\FormBuilder::elementTriggeredScriptedSubmission().
        '#name' => implode(':', $element['#mb_value_address']) . '_add_more',
        '#value' => $button_text,
        '#limit_validation_errors' => [],
        '#submit' => array(array(get_class($this), 'addItemSubmit')),
        '#ajax' => array(
          'callback' => array(get_class($this), 'itemButtonAjax'),
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ),
      );
    }

    if ($item_count > 0) {
      $element['actions']['remove'] = [
        '#type' => 'submit',
        '#name' => implode(':', $element['#mb_value_address']) . '_remove_item',
        '#value' => t('Remove last item'),
        '#limit_validation_errors' => [],
        '#submit' => array(array(get_class($this), 'removeItemSubmit')),
        '#ajax' => array(
          'callback' => array(get_class($this), 'itemButtonAjax'),
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ),
      ];
    }

    return 'compound';
  }

  /**
   * Set form element properties specific to array component properties.
   *
   * @param &$element
   *  The form element for the component property.
   * @param FormStateInterface $form_state
   *  The form state.
   * @param $property_info
   *  The info array for the component property.
   * @param $form_default_value
   *  The default value for the form element.
   *
   * @return string
   *  The handling type to be applied to this element's value on submit.
   */
  protected function XelementString(&$element, FormStateInterface $form_state, $property_info, $form_default_value) {
    if (isset($property_info['options'])) {
      $element['#type'] = 'select';

      $options = [];

      $element['#options'] = $property_info['options'];
      $element['#empty_value'] = '';

      if (empty($form_default_value)) {
        $form_default_value = '';
      }

      $handling = 'select';
    }
    else {
      $element['#type'] = 'textfield';

      $handling = 'textfield';
    }

    $element['#default_value'] = $form_default_value;

    return $handling;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // TODO: remove #mb_action, use #name instead.
    $actions['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#dropbutton' => 'mb',
      // Still no way to get a button's name, apparently?
      '#mb_action' => 'submit',
      '#submit' => array('::submitForm', '::save'),
    );
    if ($this->getNextLink() != 'generate-form') {
      $actions['submit_next'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save and go to next page'),
        '#dropbutton' => 'mb',
        '#mb_action' => 'submit_next',
        '#submit' => array('::submitForm', '::save'),
      );
    }
    $actions['submit_generate'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save and generate code'),
      '#dropbutton' => 'mb',
      '#mb_action' => 'submit_generate',
      '#submit' => array('::submitForm', '::save'),
    );

    return $actions;
  }

  /**
   * Get the value for a property from the form values.
   *
   * This performs various processing depending on the form element type and the
   * property format:
   *  - explode textarea values
   *  - filter checkboxes and store only the keys
   *  - recurse into compound properties
   * The form build process leaves instructions for how to handle each value in
   * the 'element_handling' form state setting, so that here we don't need to
   * repeat the logic based on property info. Furthermore, we can't put this
   * a property info array into form state storage, because it contains closures,
   * which don't survive the serialization process in the database, and so the
   * property info would need to be run through DCB's preparation process all
   * over again.
   *
   * @param array $value_address
   *  The address array of the value in the form state values array. The final
   *  element of this is name of the property and the form element.
   * @param $value
   *  The incoming form value from the form element for this property.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return
   *  The processed value.
   */
  // TODO: check for stuff here to move to cleanUpValues().
  protected function XgetFormElementValue($value_address, $value, FormStateInterface $form_state) {
    // Retrieve the handling type from the form state.
    $property_form_value_address_key = implode(':', $value_address);
    $handling = $form_state->get(['element_handling', $property_form_value_address_key]);

    switch ($handling) {
      case 'textarea':
        // Array format, without options: textarea.
        if (empty($value)) {
          $value = [];
        }
        else {
          // Can't split on just "\n" because for FKW reasons, linebreaks come
          // back through POST as Windows-style "\r\n".
          $value = preg_split("@[\r\n]+@", $value);
        }
        break;

      case 'autocomplete':
        // Array format, with extra options: textfield with autocomplete.
        // Only explode a non-empty string, as explode() will turn '' into an
        // array!
        if (!empty($value)) {
          // Textfield with autocomplete.
          $value = preg_split("@,\s*@", $value);
        }
        break;

      case 'checkboxes':
        // Array format, with options: checkboxes.
        // Filter out empty values. (FormAPI *still* doesn't do this???)
        $value = array_filter($value);
        // Don't store values also in the keys, as some of these have dots in
        // them, which ConfigAPI doesn't allow.
        $value = array_keys($value);
        break;

      case 'compound':
        // Remove the item count buttons from the values.
        unset($value['actions']);
        unset($value['table']);

        foreach ($value as $delta => $item_value) {
          $delta_value_address = $value_address;
          $delta_value_address[] = $delta;

          // Recurse into the child property values.
          foreach ($item_value as $child_key => $child_value) {
            $delta_child_value_address = $delta_value_address;
            $delta_child_value_address[] = $child_key;

            $value[$delta][$child_key] = $this->getFormElementValue($delta_child_value_address, $child_value, $form_state);
          }
        }
        break;

      case 'checkbox':
      case 'select':
      case 'textfield':
        // Nothing to do in these cases: $value is fine as it is.
        break;

      default:
        throw new \Exception("Unknown handling type: {$handling}.");
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $is_new = $this->entity->isNew();

    $module = $this->entity;
    // dsm($module);

    $status = $module->save();

    if ($status) {
      // Setting the success message.
      $this->messenger()->addStatus($this->t('Saved the module: @name.', array(
        '@name' => $module->name,
      )));
    }
    else {
      $this->messenger()->addStatus($this->t('The @name module was not saved.', array(
        '@name' => $module->name,
      )));
    }

    // Optionally advance to next tab or go to the generate page.
    $element = $form_state->getTriggeringElement();
    switch ($element['#mb_action']) {
      case 'submit':
        $operation = $this->getOperation();
        // For a new module, we need to redirect to its edit form, as staying
        // put would leave on the add form.
        if ($operation == 'add') {
          $operation = 'edit';
        }
        // For an existing module, we also redirect so that changing the machine
        // name of the module goes to the new URL.
        $url = $module->toUrl($operation . '-form');
        $form_state->setRedirectUrl($url);
        break;
      case 'submit_next':
        $next_link = $this->getNextLink();
        $url = $module->toUrl($next_link);
        $form_state->setRedirectUrl($url);
        break;
      case 'submit_generate':
        $url = $module->toUrl('generate-form');
        $form_state->setRedirectUrl($url);
        break;
    }
  }

  /**
   * Get the next entity link after the one for the current form.
   *
   * @return
   *  The name of an entity link.
   */
  protected function getNextLink() {
    // Probably a more elegant way of figuring out where we currently are
    // with routes maybe?
    $operation = $this->getOperation();

    // Special case for add and edit forms.
    if ($operation == 'default' || $operation == 'edit') {
      $operation = 'name';
    }

    $handler_class = $this->entityTypeManager->getHandler('module_builder_module', 'component_sections');
    $form_ops = $handler_class->getFormOperations();

    // Add in the 'name' operation, as the handler doesn't return it.
    $form_ops = array_merge(['name'], $form_ops);

    $index = array_search($operation, $form_ops);

    return $form_ops[$index + 1] . '-form';
  }

}
