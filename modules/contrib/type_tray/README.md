# Type Tray

## Overview

This module improves the usability of the "Content -> Add" page by:
 * Grouping content types together by category
 * Adding the ability to assign an icon to each content type
 * Adding the ability to assign a thumbnail to a content type, in the list
   version
 * Allowing an extended description (rich-text) to be used in the list version

## Configuration

After installing the module, navigate to
 "Configuration -> Content authoring -> Type Tray Settings" (or visit
 `/admin/config/type-tray/settings`), and define the categories to be used
 there. You can optionally indicate the label to be used on content types that
 are not categorized.

Now edit each content type (for example `/admin/structure/types/manage/article`
 if your type is named `article`), and click the "Type Tray" vertical tab. There
 you can include:
 * The category this content type should be in
 * The path to a thumbnail file describing the desktop representation of the
   content. This can be screenshot of your content with all fields populated, or
   any illustration that help editors have a better idea of how the content will
   look like on the page. It will be displayed in the "List" version of the
   Type Tray page.
 * The path to an icon file. This is a graphic image that conceptually
   represents the content, and will be displayed both in the "Grid" and "List"
   versions of the Type Tray page.
 * The extended description text. Optionally, you can use this field to describe
   when editors should use this type, or any other editorial indications. When
   visiting the Type Tray in the "List" version, this value will be displayed
   if it's not empty, falling-back to the main content type description
   otherwise.
 * Optionally, provide a weight value if you want to sort content types within
   the same category.

Note: The order of groups (categories) in the Type Tray page will be the same
 as the order categories are defined in the Settings page mentioned above.

## Icons and Thumbnails / Licensing

This module encourages sites to use their own art work to best describe their
 content to editors. A small set of icons and thumbnails is included for
 demonstration purposes and can be used as default / fallback.

- Icons included in this project are part of Feather Icons, distributed under
 the MIT license, and can be found at https://feathericons.com .
- Thumbnails included in this project were designed by the Digital Services
 Georgia team (https://digitalservices.georgia.gov), and are distributed under
 CC-BY-3.0.
