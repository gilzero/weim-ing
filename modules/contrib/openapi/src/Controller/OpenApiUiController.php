<?php

namespace Drupal\openapi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openapi\Plugin\openapi\OpenApiGeneratorInterface;
use Drupal\openapi_ui\Plugin\openapi_ui\OpenApiUiInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * API Specification controller base.
 */
class OpenApiUiController extends ControllerBase {

  /**
   * Gets the OpenAPI output in JSON format.
   *
   * @return string
   *   The page title.
   */
  public function title(OpenApiUiInterface $openapi_ui, OpenApiGeneratorInterface $generator, Request $request) {
    return $this->t(
      '%generator OpenApi Documentation',
      [
        '%generator' => $generator->getLabel(),
        '%interface' => $openapi_ui->getLabel(),
      ]
    );
  }

  /**
   * Gets the OpenAPI output in JSON format.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function generate(OpenApiUiInterface $openapi_ui, OpenApiGeneratorInterface $openapi_generator, Request $request) {
    $options = $request->get('options', []);
    $openapi_generator->setOptions($options + $openapi_generator->getOptions());
    $build = [
      '#type' => 'openapi_ui',
      '#openapi_ui_plugin' => $openapi_ui,
      '#openapi_schema' => $openapi_generator->getSpecification(),
    ];
    return $build;
  }

}
