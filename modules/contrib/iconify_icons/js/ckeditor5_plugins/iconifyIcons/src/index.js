// eslint-disable-next-line import/no-unresolved,import/no-extraneous-dependencies
import { Plugin } from 'ckeditor5/src/core';
import IconifyIconsEditing from './iconifyiconsediting';
import IconifyIconsUi from './iconifyiconsui';

class IconifyIcons extends Plugin {
  /**
   * @inheritdoc
   */
  static get requires() {
    return [IconifyIconsEditing, IconifyIconsUi];
  }

  /**
   * @inheritdoc
   */
  static get pluginName() {
    return 'IconifyIcons';
  }
}

export default {
  IconifyIcons,
};
