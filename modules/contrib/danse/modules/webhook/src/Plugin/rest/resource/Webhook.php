<?php

namespace Drupal\danse_webhook\Plugin\rest\resource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\danse_webhook\Payload;
use Drupal\danse_webhook\Plugin\Danse\Webhook as WebhookPlugin;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Represents Example records as resources.
 *
 * @RestResource (
 *   id = "danse_webhook",
 *   label = @Translation("DANSE Event"),
 *   uri_paths = {
 *     "canonical" = "/api/danse-webhook/{id}",
 *     "https://www.drupal.org/link-relations/create" = "/api/danse-webhook"
 *   }
 * )
 */
class Webhook extends ResourceBase implements DependentPluginInterface {

  /**
   * The webhook plugin.
   *
   * @var \Drupal\danse_webhook\Plugin\Danse\Webhook
   */
  protected WebhookPlugin $plugin;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): Webhook {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->plugin = $container->get('plugin.manager.danse.plugin')->createInstance('webhook');
    return $instance;
  }

  /**
   * Responds to POST requests and saves the new record.
   *
   * @param mixed $record
   *   Data to write into the database.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response object.
   */
  public function post($record): Response {
    if ($this->validate($record) && $payload = Payload::fromArray($record)) {
      // @todo Read agent and topic from request.
      /**
       * @var \Drupal\danse_webhook\Payload $payload
       */
      $this->plugin->createWebhookEvent('agent', 'label', $payload);
      return new Response('ok', 201);
    }
    return new Response('Invalid record', 400);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(): array {
    return [];
  }

  /**
   * Validates the given record.
   *
   * @param array $record
   *   The record.
   *
   * @return bool
   *   TRUE, if the record is valid, FALSE otherwise.
   */
  protected function validate(array &$record): bool {
    foreach (['extid', ['recipients', 'recipient'], 'label', 'message'] as $item) {
      if (is_array($item)) {
        foreach ($item as $subitem) {
          if (isset($record[$subitem])) {
            continue 2;
          }
        }
        return FALSE;
      }
      if (!isset($record[$item])) {
        return FALSE;
      }
    }

    if (!is_string($record['label']) || !is_string($record['message'])) {
      return FALSE;
    }
    if (!isset($record['recipients'])) {
      $record['recipients'] = [];
    }
    elseif (!is_array($record['recipients'])) {
      return FALSE;
    }
    if (isset($record['recipient'])) {
      if (is_string($record['recipient'])) {
        $record['recipients'][] = $record['recipient'];
        unset($record['recipient']);
      }
      else {
        return FALSE;
      }
    }

    return TRUE;
  }

}
