/**
 * @file registers the Iconify Icons toolbar button and binds functionality to
 *   it.
 */

// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { ButtonView } from 'ckeditor5/src/ui';
import icon from '../../../../icons/iconify.svg';

export default class IconifyIconsUI extends Plugin {
  init() {
    const { editor } = this;
    const options = this.editor.config.get('iconifyIcons');
    if (!options) {
      return;
    }

    const { openDialog, dialogSettings, collections = {} } = options;
    if (typeof openDialog !== 'function') {
      return;
    }

    // Add collections to dialogSettings
    dialogSettings.collections = collections;

    // This will register the iconify icons toolbar button.
    editor.ui.componentFactory.add('iconifyIcons', (locale) => {
      const buttonView = new ButtonView(locale);

      // Create the toolbar button.
      buttonView.set({
        label: Drupal.t('Iconify Icons'),
        icon,
        tooltip: true,
      });

      // Bind the state of the button to the command.
      this.listenTo(buttonView, 'execute', () => {
        openDialog(
          Drupal.url('iconify_icons/dialog'),
          ({ settings }) => {
            editor.execute('insertIconifyIcons', settings);
          },
          dialogSettings,
        );
      });

      return buttonView;
    });
  }
}
