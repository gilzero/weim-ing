# Extra Block Types (EBT): Core

EBT modules provide ability to add different blocks in Layout Builder in
few clicks. EBT Core contains helper functions for EBT modules.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/ebt_core).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/ebt_core).


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Creating a new EBT module
- Troubleshooting
- FAQ
- External Documentation
- Maintainers


## Requirements

This module requires the following modules:

- [Field Group](https://www.drupal.org/project/field_group)
- [Media Library Form API Element](https://www.drupal.org/project/media_library_form_element)

EBT Modules use Media module with Media Image type for background images.
Check Media Image type exists before install EBT Core module.


## Recommended modules-

EBT modules provide ability to add different blocks
in Layout Builder in few clicks.
You can install separate block types from this bunch of EBT modules:
- [EBT Accordion / FAQ](https://www.drupal.org/project/ebt_accordion)
- [EBT Basic Button](https://www.drupal.org/project/ebt_basic_button)
- [EBT Block](https://www.drupal.org/project/ebt_block)
- [EBT Bootstrap Button](https://www.drupal.org/project/ebt_bootstrap_button)
- [EBT Call to Action](https://www.drupal.org/project/ebt_cta)
- [EBT Carousel](https://www.drupal.org/project/ebt_carousel)
- [EBT Columns](https://www.drupal.org/project/ebt_columns)
- [EBT Countdown](https://www.drupal.org/project/ebt_countdown)
- [EBT Counter](https://www.drupal.org/project/ebt_counter)
- [EBT Hero](https://www.drupal.org/project/ebt_hero)
- [EBT Image](https://www.drupal.org/project/ebt_image)
- [EBT Image Gallery](https://www.drupal.org/project/ebt_image_gallery)
- [EBT Micromodal](https://www.drupal.org/project/ebt_micromodal)
- [EBT Quote](https://www.drupal.org/project/ebt_quote)
- [EBT Slick Slider](https://www.drupal.org/project/ebt_slick_slider)
- [EBT Slideshow](https://www.drupal.org/project/ebt_slideshow)
- [EBT Stats](https://www.drupal.org/project/ebt_stats)
- [EBT Tabs](https://www.drupal.org/project/ebt_tabs)
- [EBT Text](https://www.drupal.org/project/ebt_text)
- [EBT Tiles](https://www.drupal.org/project/ebt_tiles)
- [EBT Timeline](https://www.drupal.org/project/ebt_timeline)
- [EBT Video](https://www.drupal.org/project/ebt_video)
- [EBT Video and Image gallery](https://www.drupal.org/project/ebt_video_and_image_gallery)
- [EBT Views](https://www.drupal.org/project/ebt_views)
- [EBT Webform](https://www.drupal.org/project/ebt_webform)
- [EBT Webform Popup](https://www.drupal.org/project/ebt_webform_popup)

More about EBT blocks read on EBT Core module page:
[EBT Core](https://www.drupal.org/project/ebt_core)

If you are looking for Extra Paragraph Types, you can check out
these modules:
- [EPT Accordion / FAQ](https://www.drupal.org/project/ept_accordion)
- [EPT Basic Button](https://www.drupal.org/project/ept_basic_button)
- [EPT Block](https://www.drupal.org/project/ept_block)
- [EPT Bootstrap Button](https://www.drupal.org/project/ept_bootstrap_button)
- [EPT Call to Action](https://www.drupal.org/project/ept_cta)
- [EPT Carousel](https://www.drupal.org/project/ept_carousel)
- [EPT Columns](https://www.drupal.org/project/ept_columns)
- [EPT Countdown](https://www.drupal.org/project/ept_countdown)
- [EPT Counter](https://www.drupal.org/project/ept_counter)
- [EPT Hero](https://www.drupal.org/project/ept_hero)
- [EPT Image](https://www.drupal.org/project/ept_image)
- [EPT Image Gallery](https://www.drupal.org/project/ept_image_gallery)
- [EPT Micromodal](https://www.drupal.org/project/ept_micromodal)
- [EPT Quote](https://www.drupal.org/project/ept_quote)
- [EPT Slick Slider](https://www.drupal.org/project/ept_slick_slider)
- [EPT Slideshow](https://www.drupal.org/project/ept_slideshow)
- [EPT Stats](https://www.drupal.org/project/ept_stats)
- [EPT Tabs](https://www.drupal.org/project/ept_tabs)
- [EPT Text](https://www.drupal.org/project/ept_text)
- [EPT Tiles](https://www.drupal.org/project/ept_tiles)
- [EPT Timeline](https://www.drupal.org/project/ept_timeline)
- [EPT Video](https://www.drupal.org/project/ept_video)
- [EPT Video and Image gallery](https://www.drupal.org/project/ept_video_and_image_gallery)
- [EPT Views](https://www.drupal.org/project/ept_views)
- [EPT Webform](https://www.drupal.org/project/ept_webform)
- [EPT Webform Popup](https://www.drupal.org/project/ept_webform_popup)

More about EPT paragraphs read on EPT Core module page:
[EPT Core](https://www.drupal.org/project/ept_core)

If you want to enhance UI for adding Blocks from Layout builder try this module:
[Layout Builder Modal](https://www.drupal.org/project/layout_builder_modal)

If you want to enhance UI for adding Paragraphs try these modules:
[Paragraphs Edit](https://www.drupal.org/project/paragraphs_edit)
[Paragraphs Modal Edit](https://www.drupal.org/project/paragraphs_modal_edit)


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

EBT Core has configuration form with Primary/Secondary colors
and Mobile/Tablet/Desktop breakpoints in
Administration » Configuration » Content authoring
» Extra Block Types (EBT) settings

These configs will be applied to other EBT blocks by default.


## Creating a new EBT module

EBT Core module contains *starterkit* folder with boilerplate for a new EBT module.
You need to replace ebt_starterkit in files and filenames and us it as usual custom module.

If you enable EBT Starterkit module, it will be possible to create
a new EBT module for your project with Drush command (for Drush 12+):

```
drush generate ebt:module
```

Make sure, you use ebt_* prefix for machine_name (e.g. ebt_tabs, ebt_cards).
The prefix ebt_* is required for EBT block functionality.
After that, install generated module as usual Drupal module.
If you are planning to contribute your EBT module,
then export configs with new configs for fields back to /config/install
folder in your new EBT module.

## Troubleshooting

- If you see the error during EBT modules installation:
  "The EBT Carousel needs to be created "Image" media type.
  (Currently using Media type Image version Not created)"
  Then you need to create Image media type:
  Structure » Media types » Add media type
- If you use Field Layout module,
  it will automatically apply Layout Builder for new EBT block types.
  So you will need to disable Layout Builder for displaying block type fields,
  for example:

   `/admin/structure/block/block-content/manage/ebt_cta/display/default`


## FAQ

**Q: Can I use only one EBT block type, for example EBT Slideshow, without other
modules?**

**A:** Yes, sure. EBT block types tend to be standalone modules.
       You can install only needed block types.


## External Documentation

You can also view the EBT documentation at Drupal Book:
https://drupalbook.org/ebt


## Maintainers

- Ivan Abramenko - [levmyshkin](https://www.drupal.org/u/levmyshkin)
- Narine Tsaturyan - [Narine_Tsaturyan](https://www.drupal.org/u/narine_tsaturyan)
