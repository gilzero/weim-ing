// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { Widget } from 'ckeditor5/src/widget';
import InsertIconifyIconsCommand from './inserticonifyiconscommand';

export default class IconifyIconsEditing extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [Widget];
  }

  /**
   * @inheritdoc
   */
  init() {
    this._defineSchema();
    this._defineConverters();
    this._defineCommands();
  }

  /**
   * Registers iconifyIcon as an element in the DOM converter.
   *
   * @private
   */
  _defineSchema() {
    const { schema } = this.editor.model;

    // Register <img src>
    schema.register('iconifyIconImg', {
      isObject: true,
      isInline: true,
      allowWhere: '$text',
      allowAttributes: ['class', 'src', 'alt'],
    });
  }

  /**
   * Defines handling of iconify icon element in the content.
   *
   * @private
   */
  _defineConverters() {
    const { conversion } = this.editor;

    // Allow attributes for iconifyIconImg element.
    conversion.attributeToAttribute({ model: 'class', view: 'class' });
    conversion.attributeToAttribute({ model: 'src', view: 'src' });
    conversion.attributeToAttribute({ model: 'alt', view: 'alt' });

    conversion.for('downcast').elementToElement({
      model: 'iconifyIconImg',
      view: (modelElement, { writer: viewWriter }) => {
        const classAttr = modelElement.getAttribute('class');
        const src = modelElement.getAttribute('src');
        const alt = modelElement.getAttribute('alt');

        return viewWriter.createEmptyElement('img', {
          class: classAttr,
          src,
          alt,
        });
      },
    });

    conversion.for('upcast').elementToElement({
      view: {
        name: 'img',
        classes: 'iconify-icons-ckeditor',
      },
      model: (viewElement, { writer: modelWriter }) => {
        const classAttr = viewElement.getAttribute('class');
        const src = viewElement.getAttribute('src');
        const alt = viewElement.getAttribute('alt');

        return modelWriter.createElement('iconifyIconImg', {
          class: classAttr,
          src,
          alt,
        });
      },
    });
  }

  /**
   * Defines the iconify icon insert command.
   *
   * @private
   */
  _defineCommands() {
    this.editor.commands.add(
      'insertIconifyIcons',
      new InsertIconifyIconsCommand(this.editor),
    );
  }
}
