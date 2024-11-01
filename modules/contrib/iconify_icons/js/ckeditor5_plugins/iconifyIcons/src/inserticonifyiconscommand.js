/**
 * @file defines InsertIconifyIconsCommand, which is executed when the icon
 * toolbar button is pressed.
 */

// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { Command } from 'ckeditor5/src/core';

export default class InsertIconifyIconsCommand extends Command {
  execute(settings) {
    this.editor.model.change((writer) => {
      const classes = 'iconify-icons-ckeditor';

      const attributes = {
        src: settings.icon_src,
        alt: settings.icon_alt,
        class: classes,
      };

      const iconifyIconImg = writer.createElement('iconifyIconImg', attributes);

      const docFrag = writer.createDocumentFragment();
      writer.append(iconifyIconImg, docFrag);
      this.editor.model.insertContent(docFrag);
    });
  }

  refresh() {
    const { model } = this.editor;
    const { selection } = model.document;
    const allowedIn = model.schema.findAllowedParent(
      selection.getFirstPosition(),
      'iconifyIconImg',
    );
    this.isEnabled = allowedIn !== null;
  }
}
