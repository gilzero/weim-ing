/**
 * @file contains the icon picker icon for FontAwesome icons.
 */

import type { Locale } from 'ckeditor5/src/utils';
import { View } from 'ckeditor5/src/ui';
import { getFAStyleClass, getValidIconStyle } from '../iconutils';
import type { FontAwesomeStyle, FontAwesomeVersion, IconDefinition, IconName } from '../icontypes';

export default class IconPickerFAIcon extends View {
  /**
   * Constructs a new IconPickerFAIcon.
   * 
   * @param locale
   *   The locale.
   * @param faVersion
   *   The version of Font Awesome being used.
   * @param iconName
   *   The name of the icon this button is for.
   * @param iconDefinition
   *   The definition of the icon this button is for.
   * @param iconStyle
   *   The preferred style to display the icon in (optional).
   */
  public constructor(locale: Locale, faVersion: FontAwesomeVersion, iconName: IconName, iconDefinition: IconDefinition, iconStyle?: FontAwesomeStyle | null) {
    super(locale);

    this.setTemplate({
      tag: 'span',
      attributes: {
        class: ['ck-reset_all-excluded']
      },
      children: [
        {
          tag: 'i',
          attributes: {
            class: [
              'ck',
              'ckeditor5-icons__icon',
              getFAStyleClass(faVersion, getValidIconStyle(iconDefinition, iconStyle)),
              'fa-' + iconName
            ]
          }
        }
      ]
    });
  }
}
