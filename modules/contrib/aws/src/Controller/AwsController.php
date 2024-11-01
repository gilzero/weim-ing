<?php

declare(strict_types=1);

namespace Drupal\aws\Controller;

use Drupal\aws\Traits\AwsServiceTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for AWS routes.
 */
class AwsController extends ControllerBase {

  use AwsServiceTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return (new static())
      ->setAws($container->get('aws'));
  }

  /**
   * Gets the title for the service config page.
   *
   * @param string $service_id
   *   The service ID.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The title of the service.
   */
  public function getTitle($service_id) {
    $service = $this->aws->getService($service_id);
    return $this->t('Edit %service', ['%service' => $service['namespace']]);
  }

  /**
   * Shows AWS overview page.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview() {
    $list_builder = $this->entityTypeManager()->getListBuilder('aws_profile');

    $build['profiles'] = [
      '#type' => 'details',
      '#title' => $this->t('Profiles'),
      '#open' => TRUE,
      'list' => $list_builder->render(),
    ];

    $rows = [];
    $overrides = $this->aws->getOverrides();
    foreach ($overrides as $service_id => $override) {
      $service = $this->aws->getService($service_id);
      $profile = $this->aws->getProfile($service_id);

      $rows[] = [
        $service['namespace'],
        ucfirst($override['version']),
        $profile->label(),
        [
          'data' => [
            '#type' => 'dropbutton',
            '#dropbutton_type' => 'extrasmall',
            '#links' => [
              'edit' => [
                'title' => $this->t('Edit'),
                'url' => Url::fromRoute('aws.service.edit', [
                  'service_id' => $service_id,
                ]),
              ],
              'delete' => [
                'title' => $this->t('Delete'),
                'url' => Url::fromRoute('aws.service.delete', [
                  'service_id' => $service_id,
                ]),
              ],
            ],
          ],
        ],
      ];
    }

    $build['overrides'] = [
      '#type' => 'details',
      '#title' => $this->t('Service Overrides'),
      '#open' => TRUE,
      'list' => [
        '#type' => 'table',
        '#header' => [
          $this->t('Service'),
          $this->t('Version'),
          $this->t('Profile'),
          $this->t('Operations'),
        ],
        '#rows' => $rows,
      ],
    ];

    return $build;
  }

}
