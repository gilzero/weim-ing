# UI Patterns

Define and expose self-contained UI patterns as Drupal plugins and use them
seamlessly as drop-in templates for:
- [panels](https://www.drupal.org/project/panels)
- [field groups](https://www.drupal.org/project/field_group)
- views
- [Display Suite](https://www.drupal.org/project/ds) view modes and field
  templates.
- other integrations depending on the available modules.

This module also integrates with tools like [PatternLab](https://patternlab.io/)
or modules like [Component Libraries](https://www.drupal.org/project/components)
thanks to [definition overrides](https://www.drupal.org/docs/contributed-modules/ui-patterns/define-your-patterns#s-override-patterns-behavior).

This project provides 6 modules:
- **UI Patterns**: the main module, it exposes the UI Patterns system APIs, and
  it does not do much more than that.
- **UI Patterns Library**: allows to define patterns via YAML and generates a
  pattern library page available at `/patterns` to be used as documentation for
  content editors or as a showcase for business. Use this module if you don't
  plan to use more advanced component library systems such as
  [PatternLab](https://patternlab.io/) or [Fractal](https://fractal.build/).
- **UI Patterns Layouts**: allows to use patterns as layouts. This module allows
  patterns to be used on
  [Display Suite](https://www.drupal.org/project/ds) view modes or on
  [Panels](https://www.drupal.org/project/panels)
  out of the box.
- **UI Patterns Views**: allows to use patterns as Views row templates.
- **UI Patterns Field Group**: allows to use patterns to format field groups
  provided by the [Field Group](https://www.drupal.org/project/field_group)
  module.
- **UI Patterns Display Suite**: allows to use patterns to format
  [Display Suite](https://www.drupal.org/project/ds) field templates.


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

The module has no menu or modifiable settings. There is no configuration.

The submodules provide new configuration options depending on the submodule
specificities.
