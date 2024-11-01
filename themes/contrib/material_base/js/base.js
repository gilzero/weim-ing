((Drupal, once) => {
  'use strict';

  Drupal.behaviors.materialBaseFunctions = {
    attach: (context, settings) => {

      // Handles body classes.
      const addBodyClass = (className, e = null) => {
        if (e) {
          e.preventDefault();
        }
        document.body.classList.add(className);
      };

      const removeBodyClass = (className, e = null) => {
        if (e) {
          e.preventDefault();
        }
        document.body.classList.remove(className);
      };

      const toggleBodyClass = (className, e = null) => {
        if (e) {
          e.preventDefault();
        }
        document.body.classList.toggle(className);
      };

      // Handles Accordions.
      const handleAccordionToggle = (toggle, e) => {
        e.preventDefault();

        const currentSection = toggle.closest('.mb-accordion__section');

        // Collapse other sections.
        const otherSections = Array.from(toggle.closest('.mb-accordion').querySelectorAll('.mb-accordion__section')).filter(section => section !== currentSection);
        otherSections.forEach(section => {
          const otherPanel = section.querySelector('.mb-accordion__section-panel');
          slideUp(otherPanel, 200);
          section.classList.remove('mb-accordion__section--expanded');
        });

        // Toggle current section.
        const currentPanel = currentSection.querySelector('.mb-accordion__section-panel');
        if (!currentPanel) {
          return;
        }
        if (window.getComputedStyle(currentPanel).display === 'none') {
          slideDown(currentPanel, 200);
          currentSection.classList.add('mb-accordion__section--expanded');
        }
        else {
          slideUp(currentPanel, 200);
          currentSection.classList.remove('mb-accordion__section--expanded');
        }
      };

      // Handles dropdowns.
      const handleDropdown = (element, e) => {
        e.preventDefault();

        const currentDropdown = element.closest('.mb-dropdown');

        // Collapse other dropdowns.
        let otherDropdowns = [];
        if (currentDropdown.closest('.mb-dropdown__group')) {
          otherDropdowns = Array.from(currentDropdown.closest('.mb-dropdown__group').querySelectorAll('.mb-dropdown')).filter(dropdown => dropdown !== currentDropdown);
        }
        otherDropdowns.forEach(otherDropdown => {
          const otherDropdownPanel = otherDropdown.querySelector('.mb-dropdown__panel');
          otherDropdown.classList.remove('mb-dropdown--expanded');
          slideUp(otherDropdownPanel, 200);
        });

        const currentDropdownPanel = currentDropdown.querySelector('.mb-dropdown__panel');
        if (!currentDropdownPanel) {
          return;
        }
        if (window.getComputedStyle(currentDropdownPanel).display === 'none' ) {
          slideDown(currentDropdownPanel, 200);
          currentDropdown.classList.add('mb-dropdown--expanded');
        } else {
          slideUp(currentDropdownPanel, 200);
          currentDropdown.classList.remove('mb-dropdown--expanded');
        }
      };

      const closeAllDropdowns = () => {
        const dropdowns = document.querySelectorAll('.mb-dropdown');
        dropdowns.forEach(dropdown => {
          const dropdownPanel = dropdown.querySelector('.mb-dropdown__panel');
          slideUp(dropdownPanel, 200);
          dropdown.classList.remove('mb-dropdown--expanded');
        });
      };

      // Handles clear input.
      const handleInputClear = (element) => {
        const input = element.closest('.form-item').querySelector('input');
        input.value = '';
        input.focus();
      }

      // Handles search open.
      const handleSearchOpen = () => {
        addBodyClass('search-open');
        const fieldInput = context.querySelector('.search-field input');
        const length = fieldInput.value.length;
        fieldInput.focus();
        fieldInput.setSelectionRange(length, length);
      };

      // Handles search toggle.
      const handleSearchToggle = () => {
        if (document.body.classList.contains('search-open')) {
          removeBodyClass('search-open');
        }
        else {
          handleSearchOpen();
        }
      };

      // Global behaviors of the page.
      once('materialBaseFunctions', 'html').forEach(() => {
        // Overlay.
        const overlayOpenButtons = document.querySelectorAll('.overlay-open__button');
        overlayOpenButtons.forEach(element => {
          element.addEventListener('click', event => addBodyClass('overlay-open', event));
        });

        const overlayCloseButtons = document.querySelectorAll('.overlay-close__button');
        overlayCloseButtons.forEach(element => {
          element.addEventListener('click', event => removeBodyClass('overlay-open', event));
        });

        const overlayMenuItems = document.querySelectorAll('.overlay .menu-item a');
        overlayMenuItems.forEach(element => {
          element.addEventListener('click', () => removeBodyClass('overlay-open'));
        });

        // Drawer.
        const drawerOpenButtons = document.querySelectorAll('.drawer-open__button');
        drawerOpenButtons.forEach(element => {
          element.addEventListener('click', event => addBodyClass('drawer-open', event));
        });

        const drawerCloseButtons = document.querySelectorAll('.drawer-close__button');
        drawerCloseButtons.forEach(element => {
          element.addEventListener('click', event => removeBodyClass('drawer-open', event));
        });

        const drawerToggleButtons = document.querySelectorAll('.drawer-toggle__button');
        drawerToggleButtons.forEach(element => {
          element.addEventListener('click', event => toggleBodyClass('drawer-open', event));
        });

        const drawerMenuItems = document.querySelectorAll('.drawer .menu-item a');
        drawerMenuItems.forEach(element => {
          element.addEventListener('click', () => removeBodyClass('drawer-open'));
        });

        const drawerOverlay = document.querySelectorAll('.drawer__overlay');
        drawerOverlay.forEach(element => {
          element.addEventListener('click', () => removeBodyClass('drawer-open'));
        });

        // Close dropdowns on outside click.
        document.body.addEventListener('click', e => {
          if (!e.target.closest('.mb-dropdown')) {
            closeAllDropdowns();
          }
        });

        // Copy target text to clipboard.
        const copyTarget = new ClipboardJS('.copy-target__button', {
          text: trigger => trigger.getAttribute('data-target'),
        });
        copyTarget.on('success', event => {
          const targetElement = event.trigger.closest('.copy-target');
          targetElement.classList.add('just-clicked');

          setTimeout(() => {
            targetElement.classList.remove('just-clicked');
          }, 3000);

          event.clearSelection();
        });
      });

      // Accordion.
      once('accordionToggleClick', '.mb-accordion .mb-accordion__section-toggle', context).forEach(element => {
        element.addEventListener('click', e => handleAccordionToggle(element, e));
      });

      // Dropdown.
      once('dropdownToggleClick', '.mb-dropdown .mb-dropdown__toggle', context).forEach(element => {
        element.addEventListener('click', e => handleDropdown(element, e));
      });

      // Dropdown link.
      once('dropdownLinkClick', '.mb-dropdown__panel a', context).forEach(element => {
        element.addEventListener('click', closeAllDropdowns);
      });

      // Handling common form item focus state.
      once('formItemFocusState', '.form-item input', context).forEach(element => {
        element.addEventListener('focus', () => element.closest('.form-item').classList.add('form-item--focused'));
        element.addEventListener('blur', () => element.closest('.form-item').classList.remove('form-item--focused'));
      });

      // Handling textarea focus state.
      once('formItemFocusState', '.form-item textarea', context).forEach(element => {
        element.addEventListener('focus', () => element.closest('.form-item').classList.add('form-item--focused'));
        element.addEventListener('blur', () => element.closest('.form-item').classList.remove('form-item--focused'));
      });

      // Clear input.
      once('inputClearClick', '.input-clear', context).forEach(element => {
        element.addEventListener('click', () => handleInputClear(element));
      });

      // Search stuff.
      once('searchOpen', '.search-open__button', context).forEach(element => {
        element.addEventListener('click', handleSearchOpen);
      });

      once('searchClose', '.search-close__button', context).forEach(element => {
        element.addEventListener('click', () => removeBodyClass('search-open'));
      });

      once('searchToggle', '.search-toggle__button', context).forEach(element => {
        element.addEventListener('click', handleSearchToggle);
      });

      once('searchAutocomplete', '.search-field input.form-autocomplete', context).forEach(element => {
        element.addEventListener('autocompleteopen', () => addBodyClass('search-autocomplete-open'));
        element.addEventListener('autocompleteclose', () => removeBodyClass('search-autocomplete-open'));
      });

      // Status messages close button.
      once('messageCloseClick', '.messages .messages__close-button', context).forEach(element => {
        element.addEventListener('click', () => element.closest('.messages').style.display = 'none');
      });

      // Fixed status messages auto hide.
      once('messageAutoHide', '.messages--fixed', context).forEach(element => {
        setTimeout(() => element.style.display = 'none', 5000);
      });

    }
  };
}) (Drupal, once);
