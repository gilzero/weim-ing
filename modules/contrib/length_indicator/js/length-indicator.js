((Drupal, once) => {
  'use strict';

  Drupal.behaviors.lengthIndicatorInit = {
    attach: (context) => {
      const elements = once('length-indicator', '[length-indicator-enabled]', context);
      Object.values(elements || {}).forEach((element) => {
        const wrapper = element.closest('.form-wrapper');
        const config = {
          element,
          cursor: wrapper.querySelector('.length-indicator__cursor'),
          allIndicators: wrapper.querySelectorAll('.length-indicator__indicator'),
          dir: document.querySelector('html').attributes.dir.value
        }

        element.addEventListener('input', () => {
          setCursorAndActiveIndicator(config);
        });
        setCursorAndActiveIndicator(config);
      });
    }
  };

  /**
   * Sets the cursor and highlights the active indicator part.
   *
   * @param config
   *   Config object.
   */
  const setCursorAndActiveIndicator = (config) => {
    const { element, cursor, allIndicators, dir} = config;
    const total = element.dataset.lengthIndicatorTotal;

    const length = element.value.length;
    let position = (length / total) * 100;
    position = position < 100 ? position : 100;

    const positionDir = dir === 'rtl' ? 'right' : 'left';
    cursor.style[positionDir] = position + '%';

    allIndicators.forEach((elem) => {
      elem.classList.remove("is-active");
    });

    let coloredIndicator = allIndicators[0];
    for (let i = 1; i < allIndicators.length; i++) {
      let indicator = allIndicators[i];
      if (length >= indicator.dataset.pos) {
        coloredIndicator = indicator;
      }
      else {
        break;
      }
    }
    coloredIndicator.classList.add('is-active');
  }

})(Drupal, once);
