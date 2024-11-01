# Drulma

Drulma is a base theme for Drupal.
It is built to use the [Bulma](https://bulma.io/) CSS framework.

## Features

* Implements markup from Bulma adapted to Drupal.
* Comes with a simple layout out of the box, a hero header and some regions.
* Uses [BulmaJS](https://vizuaalog.github.io/BulmaJS/) for JS interactions
(no jQuery).

## Installation

After installing, you can optionally install
the [Drulma Companion module](https://www.drupal.org/project/drulma_companion).
This module implements additional blocks such as navbar and tabs.
It also adds template suggestions for fontawesome 5 icons.
To be able to use the fontawesome 5 icons the
[Libraries provider fontawesome](https://www.drupal.org/project/lp_fontawesome)
module is needed.
This makes fontawesome installation optional.

The theme can't depend on the module to be installed until
[this issue is solved](https://www.drupal.org/project/drupal/issues/474684)

## Default layout

By default all the blocks installed and content region will
be wrapped in a `container` class so the content is nicely centered
but this can be removed on the configuration of the theme.
This is the main reason why the Drulma companion depends
on the [block class](https://www.drupal.org/project/block_class)
module.

It also tries to be minimalistic in the regions used.
The header is implemented as a
[Bulma hero](https://bulma.io/documentation/layout/hero/)

## Version and loading of Bulma.

By default Bulma is going to be loaded using the
[jsdelivr CDN](https://www.jsdelivr.com/) and the
version specified in the drulma.libraries.yml file.

Install the
[Libraries provider module](https://www.drupal.org/project/libraries_provider)
to use a local version, update the version or use
[Bulmaswatch](https://jenil.github.io/bulmaswatch) themes.

## BulmaJS

The following components are implemented:

* [Navbar](https://vizuaalog.github.io/BulmaJS/docs/0.9/navbar): Open navbar on
mobile devices.
* [Message](https://vizuaalog.github.io/BulmaJS/docs/0.9/message): Dismiss messages.
* [File](https://vizuaalog.github.io/BulmaJS/docs/0.9/file): Nicer file widget.

Contributions to add more or integrate them better are welcome.

## Documentation

The most useful docs are at [bulma.io](https://bulma.io/documentation/).


If popularity of this project rises a separate page for Drulma will be
created to learn about how Drupal and Bulma are integrated.

## Subtheming

Drush 9 Does not support commands coming from themes
so you need to install Drulma companion in order to generate a subtheme.
If the companion is only used for the subtheme generation it can be uninstalled.

You can read more about themes providing drush commands at:

https://github.com/drush-ops/drush/pull/3089

https://www.drupal.org/project/drupal/issues/2002606#comment-10786044

## Contributions

The project is open to improvements on how to override
Drupal markup to make it more adapted to Bulma but also
feel free to open any discussion about how to make Bulma
and Drupal play nicely together.

Patches on drupal.org are accepted but merge requests on
[gitlab](https://gitlab.com/upstreamable/drulma) are preferred.

## Real time communication

You can join the [#drulma](https://drupalchat.me/channel/drulma)
channel on [drupalchat.me](https://drupalchat.me).

## Related projects

[Bulma CSS](https://www.drupal.org/project/bulma) is an earlier implementation
of Bulma as a Drupal theme. The project seems to overcomplicate the theme
implementation while Drulma tries to implement almost every feature of that
project in more flexible way. You will find less global settings in Drulma since
they are in the blocks provided by Drulma companion or easily overridable with a
theme twig template.

### Differences

* Drulma supports Fontawesome 5 with a set of template suggestions. No icon
guessing.
* The navbar is implemented as a block so you can have many instances.
* In Drulma you can remove all the `container` classes from configuration so you
get a wider layout.
* Drulma supports Drush 9 and above only (Drulma companion needed).
* Quickedit not tested in Drulma (the Bulma CSS theme has some overrides that
maybe can be adapted).
* Comments are not supported in Drulma. Patches are welcome.
* No breadcrumb configuration (easily overridable with a template) but it can be
a good first contribution.
