<?php

namespace Drupal\iconify_icons\Twig\Extension;

use Drupal\iconify_icons\IconifyServiceInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Iconify icons Twig extension.
 */
class IconifyIcon extends AbstractExtension {

  /**
   * The iconify icons service.
   *
   * @var \Drupal\iconify_icons\IconifyServiceInterface
   */
  protected $iconify;

  /**
   * IconifyIcons constructor.
   */
  public function __construct(IconifyServiceInterface $iconify) {
    $this->iconify = $iconify;
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction(
        'iconify_icon',
        $this->iconifyIcon(...)
      ),
    ];
  }

  /**
   * Gets SVG icon from the name.
   *
   * @param string $icon
   *   The full icon name.
   * @param array|null $settings
   *   The name to get the icon from or null.
   *
   * @return string
   *   The SVG icon.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException.
   * @throws \JsonException
   */
  public function iconifyIcon(string $icon, ?array $settings): string {
    // Take 'Icon name (collection name)', match the collection name from
    // inside the parentheses.
    // @see \Drupal\Core\Entity\Element\EntityAutocomplete::extractEntityIdFromAutocompleteInput
    if (preg_match('/(.+\\s)\\(([^\\)]+)\\)/', $icon, $matches)) {
      $icon_name = trim($matches[1]);
      $collection = trim($matches[2]);
      return $this->iconify->generateSvgIcon($collection, $icon_name, $settings ?? []);
    }

    return '';
  }

}
