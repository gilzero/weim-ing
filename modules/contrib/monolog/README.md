# INTRODUCTION

This module integrates Drupal with the fantastic [Monolog library by Seldaek](https://github.com/Seldaek/monolog)
to provide a better logging solution. Some benefits of using this module are:

- Configurable logging levels
- A multitude of handlers, formatters and processors
- All the power and flexibility of Monolog

The Drupal Monolog module will override core drupal logging behaviors. In
order to still use Drupal loggers (`logger` tagged services) such as 
Watchdog or contributed modules you need to add them to this module 
configuration, see the "Log to database" chapter.

Monolog sends your logs to files, sockets, inboxes, databases and various web
services. This module is a thin wrapper to integrate the Monolog library with
the Drupal logging system. For more information on how the Monolog library
itself works, take a look to the [documentation](https://github.com/Seldaek/monolog/blob/master/doc/01-usage.md).

# REQUIREMENTS

No Special Requirements.

# INSTALLATION

The Monolog module needs to be installed via Composer, which will also download
the required library. Look at [Using Composer with Drupal](https://www.drupal.org/node/2404989)
for further information.

# CONFIGURATION

## Quick start

Monolog module does not have a UI, all the configuration is done in services
files.

You should create a site specific services.yml (Eg: monolog.services.yml) in the
same folder of your settings.php and then add this line to settings.php
itself:

```
$settings['container_yamls'][] = 'sites/default/monolog.services.yml';
```

The simplest configuration that allows Monolog to log to a rotating file might
be:

```
parameters:
  monolog.channel_handlers:
    default: ['rotating_file']

services:
  monolog.handler.rotating_file:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/debug.log', 10, 'DEBUG']
```

This configuration will log every message with a log level greater (or equal)
than *debug* to a file called *debug.log* located into the *logs* folder in your
private filesystem.Files will be rotated every day and the maximum number of
files to keep will be *10*.

## How it works

### Handlers

Handlers are registered as services in the [Drupal Service Container](https://www.drupal.org/docs/8/api/services-and-dependency-injection/services-and-dependency-injection-in-drupal-8).
You can define as many handlers as you need.

Each handler has a name (that must be under the *monolog.handler.* namespace),
an implementing class and a list of arguments.

Mapping among logger channels and Monolog handlers is done defining parameters.
Under the *monolog.channel_handlers* parameter it is possible to define where to
send logs from a specific channel.

The *default* mapping should exist as the fallback one.

In the previous example all logs will be sent to the
*monolog.handler.rotating_file* handler (note that only the handler name is used
, not the full service name).

The following example will send all PHP specific logs to a separate file:

```
parameters:
  monolog.channel_handlers:
    php: ['rotating_file_php']
    default: ['rotating_file_all']

services:
  monolog.handler.rotating_file_php:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/php.log', 10, 'DEBUG']
  monolog.handler.rotating_file_all:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/debug.log', 10, 'DEBUG']
```

The following code:

```
\Drupal::logger('php')->debug('debug message');
```

will write a message to the *private://logs/php.log* file.

#### Log to database

The Monolog module automatically register a handler for every enabled Drupal
logger. To log to the standard watchdog table it is possible to enable the
Database Logging module and use *drupal.dblog* as handler:

```
parameters:
  monolog.channel_handlers:
    default: ['drupal.dblog']
```

### Formatters

Monolog can alter the format of the message using *formatters*.
A formatter needs to be registered as services in the [Drupal Service Container](https://www.drupal.org/docs/8/api/services-and-dependency-injection/services-and-dependency-injection-in-drupal-8).

The module provides a set of already defined formatters like line formatter and
json formatter. We suggest you to use the [Devel module](https://www.drupal.org/project/devel)
or [Drupal Console](https://drupalconsole.com) to find all of them.

The following example will send all PHP specific logs to a separate file in the
json format:

```
parameters:
  monolog.channel_handlers:
    php:
      handlers:
        - name: 'rotating_file_php'
          formatter: 'json'
    default: ['rotating_file_all']

services:
  monolog.handler.rotating_file_php:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/php.log', 10, 'DEBUG']
  monolog.handler.rotating_file_all:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/debug.log', 10, 'DEBUG']
```

If no formatter is specified the module will fall back to line formatter.

It's possible to send a log to multiple handlers each with its own formatter:

```
parameters:
  monolog.channel_handlers:
    php:
      handlers:
        - name: 'rotating_file'
          formatter: 'json'
        - name: 'drupal.dblog'
          formatter: 'line'
    default:
      handlers:
        - name: 'syslog'

services:
  monolog.handler.rotating_file:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/debug.log', 10, 'DEBUG']
  monolog.handler.syslog:
    class: Monolog\Handler\SyslogHandler
    arguments: ['myfacility', 'local6', 'DEBUG']
```

This will send PHP logs to the rotating file handler with json formatter and to
Drupal database handler with line formatter. All other logs goes to Syslog with
default formatter (line).

When sending logs to stdout or stderr it is preferable to use the `drush` line
formatter to avoid conversion errors when Drush runs a command that uses Batch
API.

#### Conditional formatters

Sometimes it is helpful to choose between two different formatters based on a condition. For example, you may want to
use a `json` formatter for web requests, but a `drush` formatter for CLI requests.

This can be achieved using the `ConditionalFormatter` formatter. This formatter accepts two formatters and a condition:

```
monolog.formatter.drush_or_json:
  class: Drupal\monolog\Logger\Formatter\ConditionalFormatter
  arguments: ['@monolog.formatter.drush', '@monolog.formatter.json', '@monolog.condition_resolver.cli']
  shared: false
```

The third argument must be a service that implements `Drupal\monolog\Logger\ConditionResolver\ConditionResolverInterface`.
The `cli` condition resolver (that checks if the current request is a CLI request) and the `drush_or_json` formatter
are already provided by this module, but you can create your condition resolvers and conditional formatters.

#### Conditional handlers

Sometimes it is helpful to choose between two different handlers based on a condition. For example, you may want to
use an handler for web requests that prints to stdout, but a standard drush handler for CLI requests.

```yaml
parameters:
  monolog.channel_handlers:
    default:
      handlers:
        - name: default_conditional_handler
          # This is the same conditional formatter explained in the previous section.
          formatter: drush_or_json

services:
  monolog.handler.default_conditional_handler:
    class: Drupal\monolog\Logger\Handler\ConditionalHandler
    arguments:
      - '@monolog.handler.drupal.drupaltodrush'
      - '@monolog.handler.website'
      - '@monolog.condition_resolver.cli'

  monolog.handler.website:
    class: Monolog\Handler\StreamHandler
    arguments: ['php://stdout']

```

The example above uses the `drupaltodrush` handler provided by drush. You need
to add the following line to your `settings.php` to make it work:

```php
// Make sure to register the drush logger, needed by our default monolog handler.
$GLOBALS['conf']['container_service_providers'][] = \Drush\Drupal\DrushLoggerServiceProvider::class;
```

For context, the configuration above can be useful for container based environments, where normal web ui operations
log to stdout (tools like docker or kubernetes can collect these logs).

At the same time, having logs sent to stdout during drush operations is not recommended since it can interfere with drush's json output serialization
(see https://github.com/consolidation/site-process/issues/33 and https://github.com/consolidation/site-process/issues/44).

The configuration above makes it so that for cli operations (e.g. `drush updatedb`) the default drush logger is used.
This makes it possible to actually see and gather logs (instead of them being hidden) and avoids the serialization problems mentioned above.

### Processors

Monolog can alter the messages being written to a logging facility using
*processors*. The module provides a set of already defined processors to add
information like the current user, the request uri, the client IP and so on.

Processors are defined as services under the *monolog.processor.* namespace.
We suggest you to use the [Devel module](https://www.drupal.org/project/devel)
or [Drupal Console](https://drupalconsole.com) to find all of them.

To edit the list of used processors you need to override the
*monolog.processors* parameter in `sites/default/monolog.services.yml` and set
the ones you need:

```
parameters:
  monolog.processors:
    - 'message_placeholder'
    - 'current_user'
    - 'request_uri'
    - 'ip'
    - 'referer'
    - 'filter_backtrace'
```

It's also possible to define different processors for each handler:

```
parameters:
  monolog.channel_handlers:
    php:
      handlers:
        - name: 'rotating_file'
          formatter: 'json'
          processors: ['current_user']
        - name: 'syslog'
          processors: ['request_uri']
    default:
      handlers: ['drupal.dblog']

services:
  monolog.handler.rotating_file:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['private://logs/debug.log', 10, 'DEBUG']
  monolog.handler.syslog:
    class: Monolog\Handler\SyslogHandler
    arguments: ['myfacility', 'local6', 'DEBUG']
```

This will send PHP logs to the rotating file handler with json formatter and
with only the `current_user` processor. The same logs are sent to Syslog with
only the `request_uri` processor.

To add a processor to the whole logger instance you can use the
`monolog.logger.processors:` parameter:

```
parameters:
  monolog.logger.processors: ['debug']
```

This is used, for example, to add the `debug` processor defined in the
WebProfiler module.

#### Log to database

When using the *drupal.dblog* handler you should not include the
`message_placeholder` processor to maintain the same behavior of the original
Drupal logging system:

```
parameters:
  monolog.channel_handlers:
    default:
      handlers:
        - name: 'drupal.dblog'
          processors:
            - 'current_user'
            - 'request_uri'
            - 'ip'
            - 'referer'
            - 'filter_backtrace'
            - 'introspection'
```

## Examples

* RotatingFileHandler: logs to filesystem
```
  monolog.handler.rotating_file_debug:
    class: Monolog\Handler\RotatingFileHandler
    arguments: ['public://logs/debug.log', 10, 'DEBUG']
```

* SlackHandler: logs to a Slack channel
```
  monolog.handler.slack:
    class: Monolog\Handler\SlackHandler
    arguments: ['slack-token', 'monolog', 'Drupal', true, null, 'ERROR']
```

* DrupalMailHandler: sends log by mail (use this instead of the Monolog
  nativeSwiftMailerHandler)
```
  monolog.handler.mail:
    class: Drupal\monolog\Logger\Handler\DrupalMailHandler
    arguments: ['mail@example.com', 'DEBUG']
```

* [FingersCrossedHandler](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#wrappers--special-handlers)
```
  monolog.handler.fg:
    class: Monolog\Handler\FingersCrossedHandler
    arguments: ['@monolog.handler.slack', null, 100]
```

You can find the complete list of Processors/Handlers/Formatters [here](https://github.com/Seldaek/monolog/blob/master/doc/02-handlers-formatters-processors.md#handlers).

## Extending Monolog

Handlers and Processors are Drupal/Symfony Services.
It is possible to define new ones or alter the existing ones just using Drupal
OOP standard approaches.
