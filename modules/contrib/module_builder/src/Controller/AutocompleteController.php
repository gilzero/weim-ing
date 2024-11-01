<?php

namespace Drupal\module_builder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the route for autocomplete properties.
 *
 * We use a custom autocomplete route because the property address needs to be
 * passed to this controller as a route parameter, so that it can find the
 * property to get the options for.
 *
 * - Autocompletion is case-insensitive.
 * - The '.' and '_' characters are treated interchangeably. This is so you
 *   don't need to remember which word breaks have which of these when typing
 *   parts of service names: 'form.b' and 'form_b' will both autocomplete to the
 *   actual service name 'form_builder'.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request for properties with extra options.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param string $property_address
   *   The address of the property this autocomplete request is for, as a string
   *   imploded with ':'.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The matching options.
   */
  public function handleAutocomplete(Request $request, $property_address) {
    $results = [];

    if ($input = $request->query->get('q')) {
      try {
        // TODO: inject.
        $generate_task = \Drupal::service('module_builder.drupal_code_builder')->getTask('Generate', 'module');
      }
      catch (\Exception $e) {
        // If we get here we should be ok.
      }

      // Get the definition that the autocomplete is for.
      $component_data = $generate_task->getRootComponentData();
      $root_definition = $component_data->getDefinition();
      $autocomplete_property = $root_definition->getNestedProperty($property_address);

      $options = array_keys($autocomplete_property->getOptions());

      // Escape regex characters and the delimiters.
      $pattern = preg_quote($input, '@');
      // Match case-insensitively, to make it easier to work with event name
      // constants.
      $regex = '@' . $pattern . '@i';
      // Allow the '_' and '.' characters to be used interchangeably.
      // The '_' MUST go first in the $search array, as if '\.' goes first, then
      // the underscores in the $replace string will get found in the second
      // pass.
      $regex = str_replace(['_', '\.'], '[._]', $regex);
      $matched_keys = preg_grep($regex, $options);

      foreach ($matched_keys as $key) {
        $results[] = [
          'value' => $key,
          'label' => $key,
        ];
      }
    }

    return new JsonResponse($results);
  }

}
