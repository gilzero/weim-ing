/**
 * @file defines a helper view that can be displayed while loading an ajax request.
 */

import type { FocusableView } from 'ckeditor5/src/ui';
import { View } from 'ckeditor5/src/ui';
import type { Locale } from 'ckeditor5/src/utils';

export default class DrupalAjaxProgressThrobberView extends View implements FocusableView {
  /**
   * Constructs a new DrupalAjaxProgressThrobberView.
   */
  public constructor(locale: Locale) {
    super(locale);
    this.setTemplate({
      tag: 'div',
      attributes: {
        class: ['ck-reset_all-excluded']
      },
    });
  }

  /**
   * @inheritdoc
   */
  public render() {
    super.render();
    if (window.Drupal)
      this.element!.innerHTML = window.Drupal.theme.ajaxProgressThrobber();
  }

  /**
   * Implements `focus` to suppress a warning if this is the first child view of a dropdown modal.
   */
  public focus() {
    this.element?.focus();
  }
}

declare global {
  interface Window {
    Drupal?: {
      theme: {
        ajaxProgressThrobber: (message?: string) => string;
      };
    };
  }
}
