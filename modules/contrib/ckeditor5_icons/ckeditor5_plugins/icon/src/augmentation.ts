import type { DataFilter } from '@ckeditor/ckeditor5-html-support';
import type { Alignment, AlignmentAttributeDefinition, IconConfig, Size, SizeAttributeDefinition, StyleAttributeDefinition } from './iconconfig';
import type { FontAwesomeStyle } from './icontypes';
import type InsertIconCommand from './inserticoncommand';
import type ModifyIconCommand from './modifyiconcommand';

declare module '@ckeditor/ckeditor5-core' {
  interface EditorConfig {
    icon?: IconConfig;
  }
  interface CommandsMap {
    insertIcon: InsertIconCommand;
    styleIcon: ModifyIconCommand<FontAwesomeStyle, StyleAttributeDefinition>;
    sizeIcon: ModifyIconCommand<Size, SizeAttributeDefinition>;
    alignIcon: ModifyIconCommand<Alignment, AlignmentAttributeDefinition>;
  }
  interface PluginsMap {
    [DataFilter.pluginName]: DataFilter;
  }
}
