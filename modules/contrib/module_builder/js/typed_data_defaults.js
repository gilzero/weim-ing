/**
 * @file
 * Provides dynamic default values for form elements based on expressions.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  // Implements the Expression Language custom functions in JS.
  // See \MutableTypedData\ExpressionLanguage\DataAddressLanguageProvider
  // See \DrupalCodeBuilder\ExpressionLanguage\FrontEndFunctionsProvider
  var DataAddressExpressionLanguage = {
    get: function(address) {
      let $item = jQuery("input[data-typed-data-address='" + address + "']");

      return $item.val();
    },

    machineToClass: function(value) {
      var pieces = value.split('_');
      pieces = pieces.map(x => x.charAt(0).toUpperCase() +  x.slice(1));
      return pieces.join('');
    },

    machineToLabel: function(value) {
      var pieces = value.split('_');
      pieces = pieces.map(x => x.charAt(0).toUpperCase() +  x.slice(1));
      return pieces.join(' ');
    },

    stripBefore: function(string, marker) {
      return string.substring(string.indexOf(marker) + 1);
    },

  };

  /**
   * Dynamically sets default values on form elements based on expressions.
   */
  Drupal.behaviors.typedDataDefaults = {
    attach: function (context, settings) {

      // Track form elements that the user has edited, so we don't clobber them.
      // An element that has an initial value counts as having been touched by
      // the user, until the user clears it.
      $("form.module-builder-module-form :input").filter(function(index, element) {
        return jQuery(element).attr('value');
      }).data("edited", true);

      $("form.module-builder-module-form :input").change(function(e) {
        var $target = $(e.target);

        $target.data("edited", ($target.val().length !== 0));
      });

      // console.log(drupalSettings.moduleBuilder.typedDataDefaults.defaults);

      jQuery.each(drupalSettings.moduleBuilder.typedDataDefaults.defaults, function(address, details) {
        let $target = jQuery("input[data-typed-data-address='" + address + "']");

        let expression = details['expression'];

        // Extract all the data variables.
        let sources = [...expression.matchAll(/\bmodule:[a-z_\d:]+/g)];

        let sourceLookup = {};
        jQuery.each(sources, function(i, sourceMatches) {
          let source = sourceMatches[0];
          // Strip out the quotes.
          let sourceElementName = source.replace(/'/g, '');

          let sourceElement = jQuery("input[data-typed-data-address='" + sourceElementName + "']");

          sourceLookup[source] = sourceElement;
        });
        // Wait, why do we need a source lookup???? can't we do it on the fly?

        expression = expression.replaceAll(' ~ ', ' + ');

        let dependency = details['dependencies'][0];
        let $dependency = jQuery("input[data-typed-data-address='" + dependency + "']");

        $dependency.change(function() {
          // Bail if the dependency has been emptied.
          if ($dependency.val() == '') {
            return;
          }

          // Bail if the target has been edited by the user, so we don't clobber
          // their work.
          if ($target.data("edited")) {
            return;
          }

          $target.val(eval(expression));
        });
      });

    }
  };
})(jQuery, Drupal, drupalSettings);
