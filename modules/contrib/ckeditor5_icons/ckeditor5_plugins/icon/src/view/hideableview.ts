/**
 * @file defines a helper view that can be conveniently created and hidden when necessary.
 */

import type { TemplateDefinition } from 'ckeditor5/src/ui';
import { View } from 'ckeditor5/src/ui';
import type { Locale } from 'ckeditor5/src/utils';

export default class HideableView extends View {
  /**
   * The visibility of this view.
   * 
   * @observable
   */
  public declare isVisible: boolean;

  /**
   * Constructs a new HideableView.
   */
  public constructor(locale: Locale, tag: string, children?: Iterable<View | TemplateDefinition>, isVisible: boolean = true) {
    super(locale);

    this.set('isVisible', isVisible);

    this.setTemplate({
      tag: tag,
      attributes: {
        class: ['ck', this.bindTemplate.to('isVisible', value => value ? '' : 'ck-hidden')]
      },
      children: children
    });
  }
}
