/**
 * @file
 * Provides JS behaviour for the generate code form.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.moduleBuilder = {
    attach: function (context, settings) {

      $('.generated-files .generated-code:not(:first)').each(function () {
        $(this).hide();
      });
      // Reloading the page will cause browsers to set the radio which was
      // previously selected, so ensure the corresponding textarea is visible.
      $('.generated-files .form-radio:checked').change();

      $('.generated-files .form-radio').change(function() {
        if (this.checked) {
          $('.generated-files .generated-code').hide();

          // Show the corresponding code textarea.
          var filename = $(this).attr('data-generated-file');
          $('.generated-files textarea[data-generated-file="' + filename + '"]').parents('.generated-code').show();
        }
    });


    }
  };
})(jQuery, Drupal, drupalSettings);
