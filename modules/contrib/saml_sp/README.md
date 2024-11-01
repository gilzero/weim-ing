## SAML Service Provider


# Introduction

This package provides two modules:
- SAML Service Provider API
- SAML Drupal Login


The API module lets other modules leverage SAML authentication.

The SAML Drupal Login module uses the API module to enables Drupal to become a
SAML Service Provider so users can authenticate to Drupal (without entering a
username or password) by delegating authentication to a SAML IdP (Identity
Provider).

Version 8.x-3.x of this module relies on version 3 of OneLogin's SAML PHP
Toolkit, which is a significant restructuring of that library.


## Dependencies

Requires the OneLogin SAML-PHP toolkit which is managed by Composer.


## Installation

Option 1 (strongly recommended): You can require the module with Composer:
```
    composer config repositories.drupal composer https://packages.drupal.org/8
    composer require drupal/saml_sp
```
to have composer download the module and the dependent libraries.

Option 2: You can download the module manually, but you will still need to
modify the core composer.json for Composer to install the OneLogin SAML PHP
Toolkit. Change this section:
```
    {
      "extra": {
        "_readme": [
          "By default Drupal loads the autoloader from ./vendor/autoload.php.",
          "To change the autoloader you can edit ./autoload.php."
        ],
        "merge-plugin": {
            "include": [
            "core/composer.json",
            "modules/saml_sp/composer.json" // <-- add this line
          ],
          "recurse": false,
          "replace": false,
          "merge-extra": false
        }
      },
    }
```
to add the modules/saml_sp/composer.json line and run
`composer update`
this will download the library and add it to your composer autoload.php.


## Configuring an IdP

You must specify the remote IdP server in order to use it for authentication.
Typically, you will need to exchange metadata in advance; some systems may
do that automatically, with others you will need to communicate with the
IdP's administrator.

    Note: Multiple IdPs can be configured and multiple can be configured for
    the Drupal Login. This allows you to have multiple Relying Party Trusts
    (RTPs) configured with multiple IdPs and the user can choose which one
    they want to use for authentication from the login form. Different IdPs
    can be configured for each of your different environments (local,
    development, staging, production, etc.). They can be configured with
    different App names and exported using Drupal's configuration management
    system. Then each environment can specify a different IdP configuration for the Drupal login.

If you have received XML metadata for the IdP from its administrator, you can
paste it at the top of the form and the module will automatically parse it
and provide the values below:

Name = Human readable name for IdP.

Entity ID: The IdP's name for itself. It usually looks like a URL.

App name: will be used in the IdP configuration. For example
"demoLocalDrupal".

NameID field: this defaults to user mail and works for most configurations. In
that case the IdP is configured to use email address for NameID.
But if you need to support changing email on the IdP, then you need to add
a custom field to user profile and then choose that field here. The
"Hidden Field Widgets" module (https://www.drupal.org/project/hidden_field)
may be used for that field so that users don't need to worry about it, ever.

IdP login URL: e.g. https:///idp.example.com/saml2/idp/SSOService.php
IdP logout URL: e.g. https:///idp.example.com/saml2/idp/SLOService.php

X.509 certificates: the public certificate of the IdP server.


## Usage

When everything is set and ready to go, the process begins from
http://www.yoursite.com/saml/drupal_login

A returnTo parameter can be appended to the url, if you want to redirect
the user somewhere else than the front page after login. For example the user
profile page http://www.yoursite.com/saml/drupal_login?returnTo=user

The login block and user login form will show a link with
"Log in using Single Sign-On" text on it. The user login page will return the
user to the profile page and the login block will return the user to the same
page where the login process was started from.

## Security kit
[The Security kit](https://drupal.org/project/seckit) module contains protection for [Cross-Site Request Forgery (CSRF)](https://en.wikipedia.org/wiki/Cross-site_request_forgery) attacks. This protection will prevent SAML login from occurring. A new feature has been added to automatically add the login protocol/domain to the Security Kit CSRF Whitelist to prevent the CSRF protection from causing issues.

## TODO

For the 8.x-3.x version, these items are incomplete:
- Single Log Out (SLO)
- updating Drupal account with attributes from the IdP


## MAINTAINERS

 - James Glasgow (jrglasgow) - https://www.drupal.org/u/jrglasgow
 - Jae Proctor (jproctor) - https://www.drupal.org/u/jproctor
 - Luke Meier (Lukey) - https://www.drupal.org/u/lukey
 - Marcus Deglos (manarth) - https://www.drupal.org/u/manarth
 - Joonas Meriläinen (mErilainen) - https://www.drupal.org/u/merilainen
 - Joël Pittet (joelpittet) - https://www.drupal.org/u/joelpittet
 - Rune Schjellerup Philosof (RunePhilosof) - https://www.drupal.org/u/runephilosof
 - Matthew Cowgur (safetypin) - https://www.drupal.org/u/safetypin
 - Jake Lake (lakesta) - https://www.drupal.org/u/lakesta
