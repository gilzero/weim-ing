// eslint-disable-next-line func-names
(function (Drupal, once, $) {
  Drupal.behaviors.IconifyIconsPreview = {
    attach(context) {
      const elements = once('iconify', '.iconify-icons', context);
      // eslint-disable-next-line func-names
      elements.forEach(function (element) {
        // eslint-disable-next-line no-use-before-define
        const iconPreview = findIconPreview(element);

        // eslint-disable-next-line func-names
        const updateIconPreview = function (svgContent) {
          if (
            svgContent &&
            iconPreview &&
            !element.closest('#iconify-icons-widget-modal')
          ) {
            iconPreview.innerHTML = svgContent;
          }
        };

        // Get the unique settings key from the element's data attribute.
        const settingsKey = element.getAttribute('data-settings-key');
        const sessionKey = `iconify_selected_${settingsKey}`;
        // Retrieve the stored SVG content from session storage.
        const storedSvgContent = sessionStorage.getItem(sessionKey);
        if (settingsKey && drupalSettings.iconify_icons[settingsKey]) {
          // @todo handle remove.
          const iconSvg = drupalSettings.iconify_icons[settingsKey].icon_svg;

          // Initial iconSvg to iconPreview.
          if (iconSvg) {
            updateIconPreview(iconSvg);
          }

          if (storedSvgContent && element.value) {
            updateIconPreview(storedSvgContent);
          }

          // eslint-disable-next-line func-names
          jQuery(element).on('autocompleteselect', async function (event, ui) {
            const selectedValue = ui.item.value;
            const [iconName, collectionWithParentheses] =
              selectedValue.split(' (');
            const collection = collectionWithParentheses.slice(0, -1);
            // eslint-disable-next-line no-use-before-define
            const selectedIcon = await fetchIcon(collection, iconName);
            sessionStorage.setItem(sessionKey, selectedIcon);
            if (element.value) {
              updateIconPreview(selectedIcon);
            }
          });
        } else {
          // eslint-disable-next-line func-names
          jQuery(element).on('autocompleteselect', async function (event, ui) {
            const selectedValue = ui.item.value;
            const [iconName, collectionWithParentheses] =
              selectedValue.split(' (');
            const collection = collectionWithParentheses.slice(0, -1);
            // eslint-disable-next-line no-use-before-define
            const selectedIcon = await fetchIcon(collection, iconName);
            if (element.value) {
              updateIconPreview(selectedIcon);
            }
          });
        }
      });

      // Add beforeunload event listener to clear session storage when the page is changed
      window.addEventListener('beforeunload', () => {
        // eslint-disable-next-line func-names
        elements.forEach(function (element) {
          const settingsKey = element.getAttribute('data-settings-key');
          const sessionKey = `iconify_selected_${settingsKey}`;
          sessionStorage.removeItem(sessionKey);
        });
      });
    },
  };

  // Function to generate SVG icon preview.
  async function fetchIcon(collection, iconName) {
    const url = `https://api.iconify.design/${collection}/${iconName}.svg`;

    try {
      const response = await fetch(url);
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return await response.text();
    } catch (error) {
      // eslint-disable-next-line no-console
      console.error('Error fetching the icon:', error);
      return null;
    }
  }

  // Function to find the nearest .iconify-icons-preview element to a given element.
  function findIconPreview(element) {
    // Traverse up the DOM hierarchy to find the nearest .iconify-icons-preview element.
    let currentElement = element;
    while (currentElement) {
      const iconPreview = currentElement.querySelector(
        '.iconify-icons-preview',
      );
      if (iconPreview) {
        return iconPreview;
      }
      currentElement = currentElement.parentElement;
    }
    return null; // If no .iconify-icons-preview element is found.
  }

  Drupal.behaviors.IconifyIconsWidgetCollectionsFilter = {
    // eslint-disable-next-line no-unused-vars
    attach() {
      const $checkboxesFilter = $('.iconify-icons-widget-checkboxes-filter');
      const $collections = $('.iconify-icons-widget-collections');
      const $collectionGroups = $('.iconify-icons-widget-collections-group');

      // eslint-disable-next-line no-use-before-define
      $checkboxesFilter.on('keyup', handleFiltering);

      function handleFiltering() {
        const searchTerm = $(this).val().replace(/\s+/g, '').toLowerCase();
        const filterRegex = new RegExp(searchTerm, 'i');

        // eslint-disable-next-line func-names
        $collections.find('.form-item label.option').each(function () {
          const $label = $(this);
          const labelText = $label.text().replace(/\s+/g, '').toLowerCase();
          $label.parent().toggle(filterRegex.test(labelText));
        });

        // eslint-disable-next-line func-names
        $collectionGroups.each(function () {
          const $group = $(this);
          const groupLabelText = $group
            .text()
            .replace(/\s+/g, '')
            .toLowerCase();
          const allItemsHidden = $group
            .find('.form-item')
            .toArray()
            .every((item) => !$(item).is(':visible'));
          $group.toggle(!allItemsHidden || filterRegex.test(groupLabelText));
        });
      }
    },
  };
})(Drupal, once, jQuery);
