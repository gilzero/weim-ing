/**
 * @file registers the icon picker and binds functionality to it.
 */
// cSpell:ignore svgs

import type { PluginInterface } from '@ckeditor/ckeditor5-core/src/plugin';
import type { Command } from 'ckeditor5/src/core';
import { Plugin } from 'ckeditor5/src/core';
import { createDropdown } from 'ckeditor5/src/ui';
import iconsIcon from 'fontawesome6/svgs/solid/icons.svg';
import type { InsertIconEvent } from './view/iconpickerview';
import IconPickerView from './view/iconpickerview';
import { getFontAwesomeMetadata, metadataLoaded } from './iconutils';
import type { FontAwesomeStyle, FontAwesomeVersion, CategoryDefinitions, IconDefinitions, IconName } from './icontypes';
import { Locale } from 'ckeditor5/src/utils';
import DrupalAjaxProgressThrobberView from './view/drupalajaxprogressthrobberview';

export default class IconPicker extends Plugin implements PluginInterface {
  /**
   * The plugin's name in the PluginCollection.
   */
  public static get pluginName(): 'IconPicker' {
    return 'IconPicker' as const;
  }

  /**
   * @inheritdoc
   */
  public init() {
    const { commands, config, ui } = this.editor,
      command = commands.get('insertIcon')!,
      componentFactory = ui.componentFactory;

    const faVersion: FontAwesomeVersion = config.get('icon.faVersion')!;
    const recommendedIcons: IconName[] | null | undefined = config.get('icon.recommendedIcons');

    // Registers the icon toolbar button.
    componentFactory.add('icon', locale => {
      const dropdownView = createDropdown(locale);
      let iconPickerView: IconPickerView | undefined, loadingView: DrupalAjaxProgressThrobberView | null = null, lastRequestTime: number | undefined;

      // Creates the toolbar button.
      dropdownView.buttonView.set({
        label: locale.t('Icons'),
        icon: iconsIcon,
        tooltip: true
      });

      // Binds the state of the button to the command.
      dropdownView.bind('isEnabled').to(command, 'isEnabled');

      // Handles the opening of the icon picker modal.
      dropdownView.on('change:isOpen', async () => {
        if (!iconPickerView) {
          if (!metadataLoaded()) {
            const now = Date.now();
            if (lastRequestTime && now - lastRequestTime < 1000) return; // Prevents request spamming.
            lastRequestTime = now;
            if (!loadingView) { // Shows the loading spinner.
              loadingView = new DrupalAjaxProgressThrobberView(locale);
              loadingView.extendTemplate({ attributes: { class: ['ck', 'ckeditor5-icons__picker-loading'], tabindex: '-1' } });
              dropdownView.panelView.children.add(loadingView);
            }
          }
          const { categories, icons, styles } = await getFontAwesomeMetadata(this.editor);
          if (!iconPickerView) { // A previous request may have already resolved and been handled.
            iconPickerView = this._createIconPickerView(locale, command, faVersion, categories, icons, styles, recommendedIcons);
            dropdownView.panelView.children.add(iconPickerView);
            if (dropdownView.isOpen)
              iconPickerView.focus();
          }
          if (loadingView) { // Removes the loading spinner.
            dropdownView.panelView.children.remove(loadingView);
            loadingView = null;
          }
        }
      });

      return dropdownView;
    });
  }

  /**
   * Creates the instance of `IconPickerView` to be shown when the icon picker is opened.
   * 
   * @returns
   *   The instance of `IconPickerView`.
   */
  private _createIconPickerView(locale: Locale, command: Command, faVersion: FontAwesomeVersion, faCategories: CategoryDefinitions, faIcons: IconDefinitions, styles: FontAwesomeStyle[], recommendedIcons?: IconName[] | null): IconPickerView {
    const iconPickerView = new IconPickerView(locale, faVersion, faCategories, faIcons, styles, recommendedIcons);
    this.listenTo<InsertIconEvent>(iconPickerView, 'execute', (_eventInfo, iconName, iconStyle) => { // Inserts the icon when the icon picker view fires the `execute` event.
      command.execute({ iconFA: iconName, iconStyle });
    });
    return iconPickerView;
  }
}
