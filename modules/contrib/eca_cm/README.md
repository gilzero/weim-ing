# ECA Classic Modeller

**A Drupal module that provides a modeller for ECA solely built on top of Drupal
core and ECA core.**

https://www.drupal.org/project/eca_cm

## 0. Contents

- 1. Introduction
- 2. Requirements
- 3. Installation
- 4. Usage
- 5. Complementary modules
- 6. Maintainers
- 7. Support and contribute

## 1. Introduction

This module provides a "classic" modeller, which is built solely on top of
Drupal core and ECA core.

**Important notes:**
1. Please do not use this modeller unless you have no other option. Better
   modellers are available and can be found on the project page of ECA
   (https://www.drupal.org/project/eca). Once there is a better modeller
   available that is usable for screen readers, support of this module will
   considered to be dropped entirely.
2. Please do not see this as a Rules UI equivalent. This is a completely
   different mechanic because ECA is not an equivalent to Rules (see usage
   section).

## 2. Requirements

This module does not require anything outside of Drupal core and ECA.
* ECA: https://www.drupal.org/project/eca

## 3. Installation

Install the module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.

## 4. Usage

After installation, you can then create Classic models via
`/admin/config/workflow/eca/add/core`.

In that model form, you first need to create an event. Then you can create an
action, and optionally a condition. Finally, you need to connect the action
(and condition) as a successor to the event, by navigating back to the created
event, and add the successor at the bottom of the event configuration form.

You must click on the save button in order to persist your configuration changes.
Connecting the components between each other is the main key to define the
execution chain with ECA. When you create more actions that need to be executed
after a certain event or action, don't forget to add it as successor.

## 5. Complementary modules

When the Select2 (https://www.drupal.org/project/select2) module is installed,
the user interface uses that widget to improve selection on the vast number of
available event, condition and action plugins.

When working with Tokens, the contrib Token module
(https://www.drupal.org/project/token) is recommended to be installed.
That module provides a Token browser for a convenient way to browse through
available Tokens.

## 6. Maintainers

* Maximilian Haupt (mxh) - https://www.drupal.org/u/mxh

## 7. Support and contribute

To submit bug reports and feature suggestions, or to track changes visit:
https://www.drupal.org/project/issues/eca_cm

You can also use this issue queue for contributing, either by submitting ideas,
or new features and mostly welcome - patches and tests.
