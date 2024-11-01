<?php

namespace Drupal\ckeditor5_icons\Controller;

use Drupal\ckeditor5_icons\CKEditor5IconsInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a controller for handling asynchronous metadata loading.
 *
 * This controller can serve:
 *  - Font Awesome 6 Free metadata.
 *  - Font Awesome 5 Free metadata.
 *  - Font Awesome custom metadata (provided by the `fontawesome` module).
 */
class MetadataController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The current HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The module's service.
   *
   * @var \Drupal\ckeditor5_icons\CKEditor5IconsInterface
   */
  protected $service;

  /**
   * The Font Awesome manager from the contrib module (optional).
   *
   * @var \Drupal\fontawesome\FontAwesomeManager|null
   */
  protected $fontAwesomeManager;

  /**
   * Constructs a ConfigureIconForm object.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current HTTP request.
   * @param \Drupal\ckeditor5_icons\CKEditor5IconsInterface $service
   *   The module's service.
   * @param \Drupal\fontawesome\FontAwesomeManager|null $fontAwesomeManager
   *   The Font Awesome manager from the contrib module (optional).
   */
  public function __construct(Request $request, CKEditor5IconsInterface $service, $fontAwesomeManager) {
    $this->request = $request;
    $this->service = $service;
    $this->fontAwesomeManager = $fontAwesomeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('ckeditor5_icons.CKEditor5Icons'),
      $container->get('module_handler')->moduleExists('fontawesome') ? $container->get('fontawesome.font_awesome_manager') : NULL
    );
  }

  /**
   * Gets a JSON response for loading the Font Awesome 6 Free metadata.
   *
   * The metadata is located in `libraries/fontawesome6/metadata`.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response containing the Font Awesome 6 metadata JSON.
   */
  public function getFontAwesome6MetadataResponse() {
    $response = new Response();
    $this->addHeaders($response);
    $response->setContent(Json::encode([
      'categories' => $this->service->getFontAwesomeCategories('6'),
      'icons' => $this->service->getFontAwesomeIcons('6'),
    ]));
    return $response;
  }

  /**
   * Gets a JSON response for loading the Font Awesome 5 Free metadata.
   *
   * The metadata is located in `libraries/fontawesome5/metadata`.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response containing the Font Awesome 5 metadata JSON.
   */
  public function getFontAwesome5MetadataResponse() {
    $response = new Response();
    $this->addHeaders($response);
    $response->setContent(Json::encode([
      'categories' => $this->service->getFontAwesomeCategories('5'),
      'icons' => $this->service->getFontAwesomeIcons('5'),
    ]));
    return $response;
  }

  /**
   * Gets a JSON response for loading the Font Awesome custom metadata.
   *
   * If the `fontawesome` module is installed, this will enable loading
   * the metadata returned from that module.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The HTTP response containing the Font Awesome custom metadata JSON.
   */
  public function getFontAwesomeCustomMetadataResponse() {
    $response = new Response();
    $this->addHeaders($response);
    $response->setContent(Json::encode([
      'categories' => $this->fontAwesomeManager->getCategories(),
      'icons' => $this->fontAwesomeManager->getIcons(),
    ]));
    return $response;
  }

  /**
   * Adds any required headers to the response.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to add the headers to.
   */
  protected function addHeaders($response) {
    $response->headers->set('Content-Type', 'application/json');
    $response->headers->set('Access-Control-Allow-Origin', $this->request->getSchemeAndHttpHost());
    // Allows browser caching of the metadata.
    $response->setCache(['public' => TRUE]);
    $response->setExpires(new \DateTimeImmutable('+1 day'));
  }

}
