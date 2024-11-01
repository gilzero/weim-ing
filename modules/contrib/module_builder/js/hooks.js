/**
 * @file
 * Defines Javascript behaviors for the Module Builder module.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  /**
   * Filters hooks as the user types.
   */
  Drupal.behaviors.filterHooks = {
    attach: function (context, settings) {
       // The text box for filtering.
      var $input = $('input.hooks-filter-text');
      var $container = $($input.attr('data-container'));
      // The hook group details elements.
      var $groups = $container.find('details');

      // Holds the hooks jQuery elements to hide, keyed by hook name.
      var hooks = {};
      // Hash keyed by hook name whose values are group names.
      var hooks_with_group = {};
      // Holds the groups jQuery elements to open/close, keyed by group name.
      var groups = {};
      // Holds arrays of hooks checkboxes jQuery elements, nested by group name.
      var hook_checkboxes_by_group = {};

      // Build up arrays of references for easy access later.
      $groups.each(function(index, group) {
        var $group = $(group);

        var group_name = $group.find('summary').html();

        groups[group_name] = $group;
        hook_checkboxes_by_group[group_name] = Array();

        // Get the DIV around the each checkbox, so that we can hide the whole
        // form element: checkbox and label.
        // We know the group only contains checkbox items, so we're loose about
        // what we find, as different core themes use a different class for
        // specific element types.
        var $group_hooks = $group.find('.form-item');

        $group_hooks.each(function(index, hook) {
          var $hook = $(hook);
          var hook_name = $hook.find('input').attr('value');

          hooks[hook_name] = $hook;
          hooks_with_group[hook_name] = group_name;
          hook_checkboxes_by_group[group_name].push($hook.find('input'));
        });
      });

      /**
       * Apply the filter.
       */
      function filterHooks(e) {
        var query = $(e.target).val().toLowerCase();

        var groupVisibility = {};

        if (query == '') {
          resetFilter();
          return;
        }

        // Iterate over all hooks.
        Object.keys(hooks_with_group).forEach(function (hook_name, index) {
          var textMatch = hook_name.indexOf(query) !== -1;

          hooks[hook_name].toggle(textMatch);

          // A group should be made visible if it has a matched hook, and hidden
          // if it has no matched hooks at all.
          if (textMatch) {
            var group_name = hooks_with_group[hook_name];
            groupVisibility[group_name] = true;
          }
        });

        // Show and open all the relevant groups.
        Object.keys(groups).forEach(function (group_name, index) {
          var $group = groups[group_name];

          var showGroup = (group_name in groupVisibility);

          $group.attr('open', showGroup);
          $group.toggle(showGroup);
        });
      }

      /**
       * Reset the page when the filter is cleared.
       */
      function resetFilter(e) {
        // Unhide all the hooks.
        $.each(hooks, function(index, $item) {
          $item.toggle(true);
        });

        // Unhide all the group details.
        $.each(groups, function (group_name, $item) {
          $item.toggle(true);
        });

        // Restore the groups' details state: groups containing a selected hook
        // should be open; all others should be closed.
        $.each(hook_checkboxes_by_group, function (group_name, group_hooks) {
          var any_enabled_hooks = group_hooks.reduce(
            (accumulator, currentValue) => accumulator || currentValue.prop("checked"),
            false,
          );

          groups[group_name].prop('open', any_enabled_hooks);
        });
      }

      $input.on('keyup', filterHooks);
    }
  };
})(jQuery, Drupal, drupalSettings);
