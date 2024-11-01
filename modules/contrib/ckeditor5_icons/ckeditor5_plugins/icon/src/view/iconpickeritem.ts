/**
 * @file contains the icon picker grid button for FontAwesome icons.
 */

import type { Locale } from 'ckeditor5/src/utils';
import { ButtonView } from 'ckeditor5/src/ui';
import IconPickerFAIcon from './iconpickerfaicon';
import type { FontAwesomeStyle, FontAwesomeVersion, IconDefinition, IconName } from '../icontypes';

export default class IconPickerItem extends ButtonView {
  /**
   * The FontAwesome icon view.
   */
  private readonly faIcon: IconPickerFAIcon;

  /**
   * Constructs a new IconPickerItem.
   * 
   * @param locale
   *   The locale.
   * @param faVersion
   *   The version of Font Awesome being used.
   * @param iconName
   *   The name of the icon this button is for.
   * @param iconDefinition
   *   The definition of the icon this button is for.
   */
  public constructor(locale: Locale, faVersion: FontAwesomeVersion, iconName: IconName, iconDefinition: IconDefinition, iconStyle: FontAwesomeStyle | null) {
    super(locale);

    const bind = this.bindTemplate;

    this.set({
      label: locale.t(iconDefinition.label),
      class: 'ckeditor5-icons__grid-item',
      isOn: false,
      withText: true
    });

    this.faIcon = new IconPickerFAIcon(locale, faVersion, iconName, iconDefinition, iconStyle);
    this.faIcon.extendTemplate({
      attributes: {
        class: ['ck', 'ck-icon', 'ck-button__icon', 'ck-icon_inherit-color']
      }
    });

    this.extendTemplate({
      attributes: {
        title: iconName
      },
      on: {
        mouseover: bind.to('mouseover'),
        focus: bind.to('focus')
      }
    });

    this.registerChild([this.faIcon]);
  }

  /**
   * @inheritdoc
   */
  public override render() {
    super.render();
    const element = this.element!;
    element.insertBefore(this.faIcon.element!, element.firstChild);
  }
}
