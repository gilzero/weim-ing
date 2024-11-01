## CONTENTS OF THIS FILE ##

 * Introduction
 * Installation
 * Usage / API
 * How Can You Contribute?
 * Maintainers

## INTRODUCTION ##

Author and maintainer: Pawel Ginalski (gbyte.co)
 * Drupal: https://www.drupal.org/u/gbyte.co
 * Personal: https://gbyte.co/

This module allows for SMTP configurations on a per email key basis by replacing
the implementation of smtp's mail backend plugin. It requires the smtp module.

## INSTALLATION ##

See https://www.drupal.org/documentation/install/modules-themes/modules-8
for instructions on how to install or update Drupal modules.

## USAGE / API ## 

Use the project's settings.php or settings.local.php to add SMTP configurations
keyed by the email key like so:
```php
$settings['smtp_multiple_config']['email_key']['smtp_host'] = 'smtp.gmail.com';
$settings['smtp_multiple_config']['email_key']['smtp_port'] = '465';
$settings['smtp_multiple_config']['email_key']['smtp_protocol'] = 'ssl';
$settings['smtp_multiple_config']['email_key']['smtp_username'] = 'user@gmail.com';
$settings['smtp_multiple_config']['email_key']['smtp_password'] = 'pass';
$settings['smtp_multiple_config']['email_key']['smtp_from'] = 'user@gmail.com';
$settings['smtp_multiple_config']['email_key']['smtp_fromname'] = 'Me';
```

Alternatively use the hook_smtp_multiple_config_alter() hook:
```php
function hook_smtp_multiple_config_alter(array &$config, $key) {

  if ($key === 'comment_notify_comment_notify_mail') {
    $config['smtp_host'] = 'smtp.gmail.com';
    $config['smtp_port'] = '465';
    $config['smtp_protocol'] = 'ssl';
    $config['smtp_username'] = 'user@gmail.com';
    $config['smtp_password'] = 'pass';
    $config['smtp_from'] = 'user@gmail.com';
    $config['smtp_fromname'] = 'Me';
  }
}
```

## HOW CAN YOU CONTRIBUTE? ##

 * Report any bugs, feature or support requests in the issue tracker; if
   possible help out by submitting patches.
   http://drupal.org/project/issues/smtp_multiple

 * If you would like to say thanks and support the development of this module, a
   donation will be much appreciated.
   https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5AFYRSBLGSC3W
   
 * Feel free to contact me for paid support: https://gbyte.co/contact

## MAINTAINERS ##

Current maintainers:
 * Pawel Ginalski (gbyte.co) - https://www.drupal.org/u/gbyte.co
