### 3.0.0 (unreleased)

  * BC Break: Remove the logger level parameters (i.e. `%monolog.level.debug%`).
    Now you should use a level number (monolog) or name (PSR-3), see [README.md](README.md)
  * Update to Monolog 3

### 2.0.0 (2021-12-30)

  * BC Break: Syntax of `monolog.channel_handlers` parameter changed, see [README.md](README.md)
  * Update to Monolog 2
  * Remove elastica, flowdock and logstash formatter services. You can add them
    back in a custom module's services file.
