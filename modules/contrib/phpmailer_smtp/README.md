INTRODUCTION
------------

Send emails via SMTP using the latest PHPMailer library.

* For a full description of the module, visit the [project page](http://drupal.org/project/phpmailer_smtp).
* Use the [issue queue](http://drupal.org/project/issues/phpmailer_smtp) to submit bug reports and feature suggestions, or to track changes,
  

REQUIREMENTS
------------

* Access to an SMTP server
* The [Mail System module](http://www.drupal.org/project/mailsystem).
* PHPMailer library 6 - installed via composer

INSTALLATION
------------

`composer require drupal/phpmailer_smtp`

This installs the module and the PHPMailer library. Enable the module as usual.

If you can't use composer, [Ludwig](https://www.drupal.org/project/ludwig) is supported.

CONFIGURATION
-------------

* Configure user permissions at Administer >> User management >> Access
  control >> PHPMailer SMTP module.

  Only users with the **"administer phpmailer smtp settings"** permission are
  allowed to access the module configuration page.
* Configure your SMTP server settings at Administer >> Configuration >>
  PHPMailer SMTP.
* Configure email format settings at Administer >> Configuration >>
  PHPMailer SMTP >> Format.
* Configure your Mail System settings at Administer >> Configuration >>
  Mail System.

  Select "PHPMailer SMTP" as "Sender" and "Formatter" under "Default Mail System" and 
  "Module-Specific Configuration" as required.

EXAMPLES
--------

Port 465 is now encouraged: https://tools.ietf.org/html/rfc8314

**Using Google Mail or Google Apps as SMTP server**

  To send e-mails with Google Mail use the following settings:

    SMTP server:     smtp.gmail.com
    SMTP port:       465
    Secure protocol: SSL
    Username:        your_google_mail_name@gmail.com or your_username@your_google_apps_domain.com
    Password:        your_google_mail_password

  Under "Advanced SMTP settings" check "Always set Reply-To address".
  
  Also note the sending limits for Google Mail accounts:
  http://mail.google.com/support/bin/answer.py?hl=en&answer=22839

  General instructions for Google Mail accounts:
  http://mail.google.com/support/bin/answer.py?answer=13287

**Errors when trying to send a test email**

  If you see the following error messages when trying to send a test email from
  the settings page:

    SMTP -> ERROR: Failed to connect to server: Connection timed out (110)
    SMTP Error: Could not connect to SMTP host. 

  it means the mail server can not be reached, usually because your hosting
  provider is blocking the port by a firewall. The solution is to ask your
  provider to allow outgoing connections to your mail server.

**Debug settings**

  PHPMailer SMTP supports rerouting all e-mails for debugging purposes, to
  prevent you from accidentally sending out e-mails to real users from a
  development site.  To enable this feature, add the following lines to the end
  of your settings.php (usually located in sites/default):

    $conf['system.maintenance']['phpmailer_smtp_debug_email'] = 'your_debug_email@yoursite.com';

  This will change the recipient of all e-mails to the configured address.

OAUTH2
------

A simple plugin type is used for OAuth2 integration.

To implement, extend `Drupal\phpmailer_smtp\Plugin\PhpmailerOauth2\PhpmailerOauth2PluginBase`. 

The method `getAuthOptions()` must return an array of options which PHPMailer expects, including
the provider.  See `__construct()` in [PHPMailer's OAuth2 class](https://github.com/PHPMailer/PHPMailer/blob/master/src/OAuth.php).

**Example Oauth2 Provider**

The [PHPMailer OAuth2](https://www.drupal.org/project/phpmailer_oauth2) module 
provides a plugin to authenticate with Azure using the client [Azure Active Directory Provider for OAuth 2.0 Client](https://github.com/theNetworg/oauth2-azure).

**PHPMailer documentation**

* [PHPMailer Wiki](https://github.com/PHPMailer/PHPMailer/wiki)
* [PHPMailer examples](https://github.com/PHPMailer/PHPMailer/tree/master/examples)
