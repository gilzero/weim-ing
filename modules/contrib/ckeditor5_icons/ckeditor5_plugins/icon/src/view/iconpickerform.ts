/**
 * @file contains the icon picker submit form.
 */

import type { Locale } from 'ckeditor5/src/utils';
import type { DropdownView, ListDropdownItemDefinition } from 'ckeditor5/src/ui';
import { addListToDropdown, ButtonView, createDropdown, submitHandler, View } from 'ckeditor5/src/ui';
import { UiViewModel as ViewModel } from '../iconshims';
import { icons } from 'ckeditor5/src/core';
import { Collection } from 'ckeditor5/src/utils';
import { faStyleLabels } from '../iconconfig';
import type { FontAwesomeStyle, IconDefinition, IconName } from '../icontypes';
import { createButton } from './viewutils';

export default class IconPickerForm extends View {
  /**
   * The name of the currently selected icon.
   */
  private iconName?: IconName;

  /**
   * The selected style of the currently selected icon.
   * 
   * @observable
   */
  public declare iconStyle?: FontAwesomeStyle;

  /**
   * The style selection dropdown view.
   */
  public readonly styleDropdownView: DropdownView;

  /**
   * The items in the style selection dropdown view.
   */
  private readonly styleDropdownItems: Collection<ListDropdownItemDefinition>;

  /**
   * The submit button view – inserts the selected icon when pressed.
   */
  public readonly submitButtonView: ButtonView;

  /**
   * The cancel button view – deselects the selected icon when pressed.
   */
  public readonly cancelButtonView: ButtonView;

  /**
   * Constructs a new IconPickerForm.
   * 
   * @param locale 
   *   The locale.
   */
  public constructor(locale: Locale) {
    super(locale);

    const t = locale.t;

    this.styleDropdownView = this._createStyleDropdown(locale);
    this.styleDropdownItems = new Collection<ListDropdownItemDefinition>();
    addListToDropdown(this.styleDropdownView, this.styleDropdownItems);

    this.submitButtonView = createButton(locale, t('Insert'), icons.check, 'ck-button-save');
    // Submit type of the button will trigger the submit event on entire form when clicked 
    //(see submitHandler() in render() below).
    this.submitButtonView.type = 'submit';

    this.cancelButtonView = createButton(locale, t('Cancel'), icons.cancel, 'ck-button-cancel');
    this.cancelButtonView.delegate('execute').to(this, 'cancel');

    this.setTemplate({
      tag: 'form',
      attributes: {
        class: ['ck', 'ckeditor5-icons__picker-form']
      },
      children: [this.styleDropdownView, this.submitButtonView, this.cancelButtonView]
    });
  }

  /**
   * Refreshes the style selection dropdown.
   */
  public refresh(iconName: IconName | null | undefined, iconDefinition: IconDefinition) {
    if (!iconName || iconName === this.iconName) return; // No new icon selected so no need to rebuild the style options.

    const items = this.styleDropdownItems;
    items.clear();

    for (const name of iconDefinition.styles) {
      const model = new ViewModel({
        name,
        label: faStyleLabels[name],
        withText: true
      });
      model.bind('isOn').to(this, 'iconStyle', value => value === name);
      items.add({ type: 'button', model });
    }

    this.iconName = iconName;
  }

  /**
   * @inheritdoc
   */
  public override render() {
    super.render();

    submitHandler({
      view: this
    });
  }

  /**
   * Focuses the submit button.
   */
  public focus() {
    if (this.submitButtonView.isEnabled)
      this.submitButtonView.focus();
  }

  /**
   * @param locale
   *   The locale.
   * @returns
   *   The style selection dropdown.
   */
  private _createStyleDropdown(locale: Locale): DropdownView {
    const dropdownView = createDropdown(locale), defaultLabel = 'Select a style', t = locale.t;

    dropdownView.buttonView.set({
      label: t(defaultLabel),
      tooltip: t('Styles available for this icon'),
      withText: true,
      class: 'ck-dropdown__button_label-width_auto'
    });
    dropdownView.buttonView.bind('label').to(this, 'iconStyle', value => faStyleLabels[value!]);
    dropdownView.on('execute', eventInfo => this.fire<ChangeStyleEvent>('changeStyle', eventInfo.source['name'] as FontAwesomeStyle));

    return dropdownView;
  }
}

/**
 * The event fired when the icon style is changed.
 */
export type ChangeStyleEvent = {
  name: 'changeStyle';
  args: [iconStyle: FontAwesomeStyle];
};
