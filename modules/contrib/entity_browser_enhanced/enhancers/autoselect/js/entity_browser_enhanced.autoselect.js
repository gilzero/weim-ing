/**
 * @file
 * Behaviors for the Entity Browser Autoselect.
 */

(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.entityBrowserAutoselect = {
    attach(context) {
      $(
        once(
          '.views-field-entity-browser-select',
          'register-row-click',
          context,
        ),
      ).each(function () {
        const $browserSelect = $(this);
        if (drupalSettings.entity_browser_widget.auto_select) {
          $browserSelect.click(function (event) {
            event.preventDefault();

            const $input = $browserSelect.find('input.form-checkbox');

            $browserSelect
              .parents('form')
              .find('.entities-list')
              .trigger('add-entities', [[$input.val()]]);
          });
        }
      });
    },
  };
})(jQuery, Drupal, drupalSettings, once);
