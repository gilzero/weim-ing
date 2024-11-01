/**
 * @file The build process always expects an index.js file. Anything exported
 * here will be recognized by CKEditor 5 as an available plugin. Multiple
 * plugins can be exported in this one file.
 *
 * I.e. this file's purpose is to make plugin(s) discoverable.
 */

import Icon from './icon';
import IconGeneralHtmlSupport from './integration/icongeneralhtmlsupport';
import IconLinkEditing from './integration/iconlinkediting';

// Only these plugins will be built when running `b icon` or `w icon`.
export default { Icon, IconGeneralHtmlSupport, IconLinkEditing };
