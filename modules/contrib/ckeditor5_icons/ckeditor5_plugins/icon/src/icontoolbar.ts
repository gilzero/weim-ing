/**
 * @file registers the icon toolbar and binds functionality to it.
 */

import type { PluginInterface } from '@ckeditor/ckeditor5-core/src/plugin';
import type { PluginDependencies } from 'ckeditor5/src/core';
import { Collection, type Locale, type ObservableChangeEvent } from 'ckeditor5/src/utils';
import type { ButtonExecuteEvent, DropdownButtonView, DropdownView, ListDropdownItemDefinition } from 'ckeditor5/src/ui';
import { ButtonView, createDropdown, addToolbarToDropdown, addListToDropdown } from 'ckeditor5/src/ui';
import { UiViewModel as ViewModel } from './iconshims';
import type { Size, Alignment } from './iconconfig';
import { sizeOptions, sizeDefault, alignmentOptions, alignmentDefault, faStyleLabels } from './iconconfig';
import { Plugin } from 'ckeditor5/src/core';
import { WidgetToolbarRepository } from 'ckeditor5/src/widget';
import type { FontAwesomeStyle, FontAwesomeVersion, IconName, SelectableOption } from './icontypes';
import type ModifyIconCommand from './modifyiconcommand';
import { createButton } from './view/viewutils';
import DrupalAjaxProgressThrobberView from './view/drupalajaxprogressthrobberview';
import { getFontAwesomeMetadata, getSelectedIconViewElement, metadataLoaded } from './iconutils';
import type { Element, ViewDocumentClickEvent } from 'ckeditor5/src/engine';

export default class IconToolbar extends Plugin implements PluginInterface {
  /**
   * The plugin's name in the PluginCollection.
   */
  public static get pluginName(): 'IconToolbar' {
    return 'IconToolbar' as const;
  }

  /**
   * The plugin's dependencies.
   */
  public static get requires(): PluginDependencies {
    return [WidgetToolbarRepository] as const;
  }

  /**
   * @inheritdoc
   */
  public init() {
    const editor = this.editor,
      commands = editor.commands,
      componentFactory = editor.ui.componentFactory,
      viewDocument = editor.editing.view.document,
      faVersion = editor.config.get('icon.faVersion')!;

    // Makes size and alignment options available to the widget toolbar.
    componentFactory.add('iconSize', locale =>
      createToolbarDropdown<Size>(locale, faVersion, 'Icon size', sizeOptions[sizeDefault].icon, commands.get('sizeIcon')!, sizeOptions, sizeDefault));
    componentFactory.add('iconAlignment', locale =>
      createToolbarDropdown<Alignment>(locale, faVersion, 'Icon alignment', alignmentOptions[alignmentDefault].icon, commands.get('alignIcon')!, alignmentOptions, alignmentDefault));
    componentFactory.add('iconStyle', locale => createStyleDropdown(locale, commands.get('styleIcon')!));

    // Prevents a linked icon from showing the link balloon on click.
    this.listenTo<ViewDocumentClickEvent>(viewDocument, 'click', (event, data) => {
      if (getSelectedIconViewElement(viewDocument.selection)) {
        data.preventDefault();
        event.stop();
      }
    }, { priority: 'high' });
  }

  /**
   * @inheritdoc
   */
  public afterInit() {
    const editor = this.editor;
    const widgetToolbarRepository = editor.plugins.get(WidgetToolbarRepository);
    widgetToolbarRepository.register('icon', {
      items: editor.config.get('icon.toolbarItems')!,
      getRelatedElement: (selection) => getSelectedIconViewElement(selection)
    });
  }
}

/**
 * @param iconFA
 *   All the `fa-` class names of the icon.
 * @param iconNames 
 *   The list of icon names.
 * @returns
 *   The first valid icon name found in the class names, or undefined if there aren't any.
 */
function getIconName(iconFA: string, iconNames: string[]): IconName | undefined {
  return iconFA.split(' ').find(value => iconNames.includes(value)) as IconName | undefined;
}

/**
 * Creates a dropdown with multiple buttons for executing a command.
 *
 * @returns
 *   The dropdown.
 */
function createToolbarDropdown<T extends string>(locale: Locale, faVersion: FontAwesomeVersion, label: string, icon: string | undefined, command: ModifyIconCommand<T>, options: Record<T, SelectableOption>, defaultValue: T): DropdownView {
  const dropdownView = createDropdown(locale), buttonView: DropdownButtonView = dropdownView.buttonView as DropdownButtonView, t = locale.t;
  addToolbarToDropdown(dropdownView, Object.entries<SelectableOption>(options).filter(([_optionValue, option]) => !option.compatibility || option.compatibility.includes(faVersion)).map(([optionValue, option]: [T, SelectableOption]) =>
    createToolbarButton<T>(locale, option.label, option.icon, command, optionValue)));
  buttonView.set({
    label: t(label),
    icon,
    tooltip: t(label),
    withText: !icon,
    class: 'ck-dropdown__button_label-width_auto'
  });
  if (icon === options[defaultValue].icon) { // If the icon for the dropdown is the same as the icon for the default option, it changes to reflect the current selection.
    command.on<ObservableChangeEvent<T>>('change:value', (_eventInfo, _name, value) => {
      const selectableOption: SelectableOption = options[value];
      buttonView.label = t(selectableOption.label);
      if (buttonView.icon && !selectableOption.icon)
        buttonView.children.remove(buttonView.iconView);
      else if (!buttonView.icon && selectableOption.icon)
        buttonView.children.add(buttonView.iconView, 0);
      buttonView.icon = selectableOption.icon;
      buttonView.withText = !selectableOption.icon;
    });
  }
  // Enable button if any of the buttons are enabled.
  dropdownView.bind('isEnabled').to(command, 'isEnabled');
  return dropdownView;
}

/**
 * @returns
 *   A button with the specified parameters.
 */
function createToolbarButton<T extends string>(locale: Locale, label: string, icon: string | null | undefined, command: ModifyIconCommand<T>, value: T): ButtonView {
  const editor = command.editor, buttonView = createButton(locale, label, icon);
  buttonView.tooltip = !!icon; // Displays the tooltip on hover.
  buttonView.isToggleable = true; // Allows the button with the command's current value to display as selected.
  // Disables the button if the command is disabled.
  buttonView.bind('isEnabled').to(command);
  // Allows the button with the command's current value to display as selected.
  buttonView.bind('isOn').to(command, 'value', commandValue => commandValue === value);
  // Executes the command with the button's value on click.
  buttonView.on<ButtonExecuteEvent>('execute', () => {
    command.execute({ value });
    editor.editing.view.focus();
  });
  return buttonView;
}

/**
 * @param locale
 *   The locale.
 * @param command
 *   The command to execute to modify the icon's style.
 * @returns
 *   The style selection dropdown.
 */
function createStyleDropdown(locale: Locale, command: ModifyIconCommand<FontAwesomeStyle>): DropdownView {
  const editor = command.editor, dropdownView = createDropdown(locale);
  dropdownView.buttonView.set({
    tooltip: locale.t('Icon style'),
    withText: true,
    class: 'ck-dropdown__button_label-width_auto'
  });
  dropdownView.bind('isEnabled').to(command, 'isEnabled');
  dropdownView.buttonView.bind('label').to(command, 'value', value => faStyleLabels[value]);

  let loadingView: DrupalAjaxProgressThrobberView | null = null, lastRequestTime: number | undefined, iconWidget: Element | null = null, items: Collection<ListDropdownItemDefinition> | undefined;
  dropdownView.on('change:isOpen', async () => {
    if (!metadataLoaded()) {
      const now = Date.now();
      if (lastRequestTime && now - lastRequestTime < 1000) return; // Prevents request spamming.
      lastRequestTime = now;
      if (!loadingView) {
        loadingView = new DrupalAjaxProgressThrobberView(locale);
        loadingView.extendTemplate({ attributes: { class: ['ck', 'ckeditor5-icons__picker-loading'], tabindex: '-1' } });
        dropdownView.panelView.children.add(loadingView);
      }
    }
    if (command.iconWidget && command.iconWidget !== iconWidget) {
      const iconMetadata = (await getFontAwesomeMetadata(editor)).icons;
      if (!command.iconWidget) return;
      if (!items) {
        if (loadingView) {
          dropdownView.panelView.children.remove(loadingView);
          loadingView = null;
        }
        items = new Collection<ListDropdownItemDefinition>();
        addListToDropdown(dropdownView, items);
      } else items.clear();
      const iconName = getIconName(command.iconWidget.getAttribute('iconFA') as string, Object.keys(iconMetadata) as IconName[]);
      if (iconName) {
        for (const name of iconMetadata[iconName]!.styles) {
          const model = new ViewModel({
            name,
            label: faStyleLabels[name],
            withText: true
          });
          model.bind('isOn').to(command, 'value', value => value === name);
          items.add({ type: 'button', model });
        }
        dropdownView.on('execute', eventInfo => {
          command.execute({ value: eventInfo.source['name'] as FontAwesomeStyle });
          editor.editing.view.focus();
        });
      }
      iconWidget = command.iconWidget;
    }
  });

  return dropdownView;
}
