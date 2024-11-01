((Drupal, once) => {
  'use strict';

  Drupal.behaviors.materialBaseMdcFunctions = {
    attach: (context, settings) => {

      // Using MDC auto init feature.
      // See https://material.io/develop/web/components/auto-init/
      window.mdc.autoInit();

      // Handling MDC Drawer.
      const drawerElement = document.querySelector('.mdc-drawer');
      const drawer = drawerElement?.MDCDrawer;

      const handleDrawerOpen = (e = null) => {
        if (e) {
          e.preventDefault();
        }

        if (drawer) {
          drawer.open = true;
        }
      };

      const handleDrawerClose = (e = null) => {
        if (e) {
          e.preventDefault();
        }

        if (drawer) {
          drawer.open = false;
        }
      };

      const handleDrawerToggle = (e = null) => {
        if (e) {
          e.preventDefault();
        }

        if (drawer) {
          drawer.open = !drawer.open;
        }
      };

      // Handle Chips.
      const handleChip = (checkbox) => {
        const formItem = checkbox.closest('.form-item');
        const icon = checkbox.closest('.mb-chip').querySelector('.mb-chip__icon');

        if (checkbox.checked) {
          formItem.classList.add('input-checked');
          if (icon) {
            icon.dataset.icon = icon.textContent;
            icon.textContent = 'done';
          }
        } else {
          formItem.classList.remove('input-checked');
          if (icon) {
            icon.textContent = icon.dataset.icon;
          }
        }
      }

      const handleMenuDropdownToggleClick = (toggle, e) => {
        e.preventDefault();

        const currentDropdown = toggle.closest('.mdc-menu-dropdown');
        const currentDropdownMenu = currentDropdown.querySelector('.mdc-menu').MDCMenu;
        const isExpanded = currentDropdown.classList.contains('mdc-menu-dropdown--expanded');

        let otherDropdowns = [];
        if (currentDropdown.closest('.mdc-menu-dropdown__group')) {
          otherDropdowns = Array.from(currentDropdown.closest('.mdc-menu-dropdown__group').querySelectorAll('.mdc-menu-dropdown')).filter(dropdown => dropdown !== currentDropdown);
        }
        otherDropdowns.forEach(dropdown => dropdown.classList.remove('mdc-menu-dropdown--expanded'));

        if (isExpanded) {
          currentDropdown.classList.remove('mdc-menu-dropdown--expanded');
        } else {
          currentDropdownMenu.open = true;
          currentDropdown.classList.add('mdc-menu-dropdown--expanded');
        }
      }

      const closeAllMenuDropdowns = () => {
        document.querySelectorAll('.mdc-menu-dropdown').forEach(dropdown => dropdown.classList.remove('mdc-menu-dropdown--expanded'));
      }

      // Global behaviors of the page.
      once('materialBaseMdcFunctions', 'html').forEach(() => {
        // Drawer.
        const drawerOpenButtons = document.querySelectorAll('.drawer-open__button');
        drawerOpenButtons.forEach(element => {
          element.addEventListener('click', handleDrawerOpen);
        });

        const drawerCloseButtons = document.querySelectorAll('.drawer-close__button');
        drawerCloseButtons.forEach(element => {
          element.addEventListener('click', handleDrawerClose);
        });

        const drawerToggleButtons = document.querySelectorAll('.drawer-toggle__button');
        drawerToggleButtons.forEach(element => {
          element.addEventListener('click', handleDrawerToggle);
        });

        const drawerMenuItems = document.querySelectorAll('.drawer .menu-item a');
        drawerMenuItems.forEach(element => {
          element.addEventListener('click', () => handleDrawerClose());
        });

        // Close menu dropdowns on outside click.
        document.body.addEventListener('click', e => {
          if (!e.target.closest('.mdc-menu-dropdown')) {
            closeAllMenuDropdowns();
          }
        });
      });

      // Chips.
      once('chipBehavior', '.form-item-chip input[type=checkbox]', context).forEach(element => {
        if (element.checked) {
          handleChip(element);
        }
        element.addEventListener('click', () => handleChip(element));
      });

      // Handling MDC dropdown menu.
      once('menuDropdownToggleClick', '.mdc-menu-dropdown .mdc-menu-dropdown__toggle', context).forEach(element => {
        element.addEventListener('click', e => handleMenuDropdownToggleClick(element, e));
      });

      // Dropdown menu item.
      once('menuDropdownItemClick', '.mdc-menu-dropdown .mdc-deprecated-list-item', context).forEach(element => {
        element.addEventListener('click', closeAllMenuDropdowns);
      });

      // Displaying status messages.
      once('snackbarMessageShow', '.messages.mdc-snackbar', context).forEach(element => {
        if (element.MDCSnackbar) {
          element.MDCSnackbar.open();
        }
      });

    }
  };
}) (Drupal, once);
