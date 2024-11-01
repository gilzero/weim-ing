/**
 * @file
 * belgrade theme main JS file.
 *
 */

(function (Drupal, drupalSettings) {
  // Initiate all Toasts on page.
  Drupal.behaviors.belgradeToast = {
    attach(context, settings) {
      once('initToast', '.toast', context).forEach((el) => {
        const toastList = new bootstrap.Toast(el);
        toastList.show();
      });
    },
  };

  // Accordion buttons containing Edit links.
  Drupal.behaviors.accordionButtonLinks = {
    attach(context, settings) {
      once(
        'accordionButttonLinks',
        '.fieldset-legend.accordion-button a',
        context,
      ).forEach((el) => {
        // Prevent accordion collapse when clicking on links
        el.addEventListener('click', function (e) {
          if (e.target.href) {
            const targetUrl = e.target.href;
            window.location.href = targetUrl;
          }
        });
      });
    },
  };

  // Collapse and accordion if a field is required.
  Drupal.behaviors.focusRequired = {
    attach(context, settings) {
      const inputs = document.querySelectorAll('form .accordion input');
      [].forEach.call(inputs, function (input) {
        input.addEventListener('invalid', function (e) {
          const accordion = input.closest('.collapse');
          const collapseAccordion = bootstrap.Collapse.getInstance(accordion);
          if (collapseAccordion) {
            collapseAccordion.show();
          }
        });
      });
    },
  };

  // Collapse certain accordions on mobile
  Drupal.behaviors.collapseAccordionMob = {
    attach() {
      const breakPoint =
        drupalSettings.responsive.breakpoints['belgrade.sm-max'];
      const x = window.matchMedia(breakPoint);
      if (x.matches) {
        // If media query matches collapse the bef
        const befAccordions = document.querySelectorAll(
          '.bef-exposed-form .collapse',
        );
        if (befAccordions.length) {
          [].forEach.call(befAccordions, function (bef) {
            const collapseBef = bootstrap.Collapse.getInstance(bef);
            if (collapseBef) {
              collapseBef.hide();
            }
          });
        }
      }
    },
  };
})(Drupal, drupalSettings);
