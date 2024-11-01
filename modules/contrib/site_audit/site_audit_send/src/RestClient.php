<?php

namespace Drupal\site_audit_send;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\site_audit_report_entity\Entity\SiteAuditReport;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service description.
 */
class RestClient {

  /**
   * Constructs a \Drupal\system\ConfigFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Method description.
   *
   * @return \Psr\Http\Message\ResponseInterface
   */
  public function testUrl($url) {

    try {

      $client = new Client([
        'base_url' => $url,
        'allow_redirects' => TRUE,
      ]);

      $payload = [
        'test' => TRUE,
      ];

      $response = $client->post($url, [
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => $payload
      ]);
      return $response;
    } catch (GuzzleException $e) {
      if ($e->hasResponse()) {
        return $e->getResponse();
      }
      else {
        return new Response(599, [], NULL, '1.1',
          t('Could not connect to server.'));
      }
    }
  }

  public function postReport(SiteAuditReport $entity) {

    $remote_url = $this->configFactory->get('site_audit_send.settings')
      ->get('remote_url');

    try {
      $client = new Client([
        'base_url' => $remote_url,
        'allow_redirects' => TRUE,
      ]);

      $payload = [];
      foreach ($entity->getFields() as $field_id => $field) {

        $first = $field->first();
        if ($first) {
          $field_data = $first->getValue();
          $field_key = $first->getDataDefinition()->getMainPropertyName();
          // If there is no main property name, pass the entire thing.
          // ie. for the data field.
          if (empty($field_key)) {
            $payload['report'][$field_id] = $field_data;
          }
          else {
            $payload['report'][$field_id] = $field_data[$field_key];
          }
        }
      }

      \Drupal::moduleHandler()->alter('site_audit_remote_payload', $payload);

      $response = $client->post($remote_url, [
        'headers' => [
          'Accept' => 'application/json',
        ],
        'json' => $payload
      ]);

      return $response;

    } catch (GuzzleException $e) {
      if ($e->hasResponse()) {
        return $e->getResponse();
      }
      else {
        return new Response(599, [], NULL, '1.1',
          t('Could not connect to server.'));
      }
    }
  }

  public static function submitSendForm(array &$form, FormStateInterface $form_state) {

  }

}
