<?php

namespace Drupal\ckeditor5_icons\Routing;

use Drupal\ckeditor5_icons\CKEditor5IconsInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for handling asynchronous metadata loading.
 */
class MetadataRouting implements ContainerInjectionInterface {
  /**
   * The module's service.
   *
   * @var \Drupal\ckeditor5_icons\CKEditor5IconsInterface
   */
  protected $service;

  /**
   * Constructs a MetadataRouting object.
   *
   * @param \Drupal\ckeditor5_icons\CKEditor5IconsInterface $service
   *   The module's service.
   */
  public function __construct(CKEditor5IconsInterface $service) {
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ckeditor5_icons.CKEditor5Icons')
    );
  }

  /**
   * Gets the collection of metadata routes.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function getRoutes() {
    return [
      'ckeditor5_icons.fontawesome6_metadata' => new Route(
        '/' . $this->service->getFontAwesomeMetadataPath('6'),
        [
          '_controller' => 'Drupal\ckeditor5_icons\Controller\MetadataController::getFontAwesome6MetadataResponse',
          '_format' => 'json',
          '_disable_route_normalizer' => 'TRUE',
        ],
        [
          '_csrf_token' => 'TRUE',
        ]
      ),
      'ckeditor5_icons.fontawesome5_metadata' => new Route(
        '/' . $this->service->getFontAwesomeMetadataPath('5'),
        [
          '_controller' => 'Drupal\ckeditor5_icons\Controller\MetadataController::getFontAwesome5MetadataResponse',
          '_format' => 'json',
          '_disable_route_normalizer' => 'TRUE',
        ],
        [
          '_csrf_token' => 'TRUE',
        ]
      ),
      'ckeditor5_icons.fontawesome_metadata' => new Route(
        '/' . $this->service->getFontAwesomeMetadataPath(''),
        [
          '_controller' => 'Drupal\ckeditor5_icons\Controller\MetadataController::getFontAwesomeCustomMetadataResponse',
          '_format' => 'json',
          '_disable_route_normalizer' => 'TRUE',
        ],
        [
          '_module_dependencies' => 'fontawesome',
          '_csrf_token' => 'TRUE',
        ]
      ),
    ];
  }

}
