<?php

namespace Drupal\iconify_icons\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\iconify_icons\IconifyServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class AutocompleteController extends ControllerBase {

  /**
   * Drupal Iconify service.
   *
   * @var \Drupal\iconify_icons\IconifyServiceInterface
   */
  protected $iconify;

  /**
   * Drupal configuration service container.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $iconify = $container->get('iconify_icons.iconify_service');
    $configFactory = $container->get('config.factory');
    return new static($iconify, $configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(IconifyServiceInterface $iconify, ConfigFactory $config_factory) {
    $this->iconify = $iconify;
    $this->configFactory = $config_factory;
  }

  /**
   * Handler for autocomplete request.
   */

  /**
   * Handler for autocomplete request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response with icon(s).
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \JsonException
   */
  public function handleAutocomplete(Request $request): JsonResponse {
    $results = [];
    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $typed_string = Tags::explode($input);
      $typed_string = mb_strtolower(array_pop($typed_string));
      // Optional parameters.
      $parameters = [
        'width' => 24,
        'height' => 24,
        'color' => 'currentColor',
        'flip' => '',
        'rotate' => '',
      ];
      // Selected collection in widget settings.
      $selected_collections = $request->query->get('collection');
      // Load the icon data, so we can check for a valid icon.
      $icon_data = $this->iconify->getIcons($typed_string, $selected_collections);
      // Check each icon to see if it starts with the typed string.
      $icons_svg = $this->iconify->generateSvgIcons($icon_data, $parameters);
      foreach ($icons_svg as $icon => $icon_svg) {
        [$collection, $icon_name] = explode(':', $icon, 2);
        $results[] = [
          'value' => $icon_name . ' (' . $collection . ')',
          'label' => $this->getRenderableLabel(
            $icon_name,
            $icon_svg,
            $collection
          ),
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Helper to render icons in different families.
   *
   * @param string $icon_name
   *   The icon name.
   * @param string $icon_svg
   *   The icon svg.
   * @param string $collection
   *   The icon collection.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   A renderable markup object.
   */
  protected function getRenderableLabel(string $icon_name, string $icon_svg, string $collection): FormattableMarkup {
    return new FormattableMarkup('<div class="iconify-result">' . $icon_svg . '<span class="iconify-result-icon-name">:icon_name</span><strong class="iconify-result-collection">:collection</strong></div>', [
      ':icon_name' => $icon_name,
      ':collection' => $collection,
    ]);
  }

}
