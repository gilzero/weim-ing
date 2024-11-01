<?php

declare(strict_types=1);

namespace Drupal\monolog;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Gelf\Message;
use Monolog\Formatter\GelfMessageFormatter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the `logger.factory` service with the Monolog factory.
 */
class MonologServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Register the gelf formatter only if the gelf package is installed.
    if (\class_exists(Message::class)) {
      $container->register('monolog.formatter.gelf', GelfMessageFormatter::class)
        ->setShared(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('logger.factory');
    $definition
      ->setClass('Drupal\monolog\Logger\MonologLoggerChannelFactory')
      ->setArguments([
        new Reference('request_stack'),
        new Reference('current_user'),
        new Reference('messenger'),
        new Reference('service_container'),
      ])
      ->clearTags();

    // Allow existing Drupal loggers to be added as handlers.
    $drupalLoggers = $container->findTaggedServiceIds('logger');
    foreach ($drupalLoggers as $id => $tags) {
      $handlerId = \sprintf('monolog.handler.drupal.%s',
        \preg_replace('/^logger\./', '', $id));

      // Allow the handler to be explicitly defined elsewhere.
      if (!$container->has($handlerId)) {
        $definition = $container->register($handlerId,
          'Drupal\monolog\Logger\Handler\DrupalHandler');
        $definition->addArgument(new Reference($id));
        $definition->setShared(FALSE);
      }
    }
  }

}
