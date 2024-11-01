<?php

namespace Drupal\ui_patterns_settings\Plugin\UiPatterns\SettingType;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\ui_patterns_settings\Plugin\ComplexSettingTypeBase;

/**
 * Complex setting type for links.
 *
 * @UiPatternsSettingType(
 *   id = "links",
 *   label = @Translation("Links")
 * )
 */
class LinksSettingType extends ComplexSettingTypeBase {

  /**
   * Normalize menu items.
   *
   * Don't inject URL object into patterns templates, use "title" as item
   * label and "url" as item target.
   *
   * @param array $items
   *   The items to convert.
   *
   * @return array
   */
  public static function normalize(array $items): array {
    foreach ($items as $index => $item) {
      if (!is_array($item)) {
        unset($items[$index]);
        continue;
      }
      if (array_key_exists("text", $item)) {
        // Examples: links.html.twig, breadcrumb.html.twig, pager.html.twig,
        // views_mini_pager.html.twig.
        $item["title"] = $item["text"];
        unset($item["text"]);
      }
      if (!array_key_exists("title", $item)) {
        $item["title"] = $index;
      }
      if (array_key_exists("href", $item)) {
        // Examples: pager.html.twig, views_mini_pager.html.twig.
        $item["url"] = $item["href"];
        unset($item["href"]);
      }
      if (!isset($item["url"]) && isset($item["link"])) {
        // Example: links.html.twig.
        $item["url"] = $item["link"]["#url"];
        if (isset($item["link"]["#options"])) {
          $item["url"]->mergeOptions($item["link"]["#options"]);
        }
        unset($item["link"]);
      }
      $item = self::normalizeUrl($item);
      if (array_key_exists("below", $item)) {
        $item["below"] = self::normalize($item["below"]);
      }
      $items[$index] = $item;
    }
    $items = array_values($items);
    return $items;
  }

  /**
   * Normaize URL in an item.
   *
   * Useful for: menu.html.twig, links.html.twig.
   */
  private static function normalizeUrl(array $item): array {
    if (!array_key_exists("url", $item)) {
      return $item;
    }
    $url = $item["url"];
    if (!($url instanceof Url)) {
      return $item;
    }
    if ($url->isRouted() && ($url->getRouteName() === '<nolink>')) {
      unset($item["url"]);
    }
    elseif ($url->isRouted() && ($url->getRouteName() === '<button>')) {
      unset($item["url"]);
    }
    else {
      $item["url"] = $url->toString();
    }
    $options = $url->getOptions();
    self::setHrefLang($options);
    self::setActiveClass($options, $url);
    if (isset($options["attributes"])) {
      $item["link_attributes"] = new Attribute($options["attributes"]);
    }
    return $item;
  }

  /**
   * Set hreflang attribute.
   *
   * Add a hreflang attribute if we know the language of this link's URL and
   * hreflang has not already been set.
   *
   * @param array $options
   *   The URL options.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  private static function setHrefLang(array &$options): void {
    if (isset($options['language'])
      && ($options['language'] instanceof LanguageInterface)
      && !isset($options['attributes']['hreflang'])
    ) {
      $options['attributes']['hreflang'] = $options['language']->getId();
    }
  }

  /**
   * Set the attributes to have the active class placed by JS.
   *
   * @param array $options
   *   The URL options.
   * @param \Drupal\Core\Url $url
   *   The URL object.
   *
   * @see \Drupal\Core\Utility\LinkGenerator::generate()
   */
  private static function setActiveClass(array &$options, Url $url): void {
    // Set the "active" class if the 'set_active_class' option is not empty.
    if (!empty($options['set_active_class']) && !$url->isExternal()) {
      // Add a "data-drupal-link-query" attribute to let the
      // drupal.active-link library know the query in a standardized manner.
      if (!empty($options['query'])) {
        $query = $options['query'];
        ksort($query);
        $options['attributes']['data-drupal-link-query'] = Json::encode($query);
      }

      // Add a "data-drupal-link-system-path" attribute to let the
      // drupal.active-link library know the path in a standardized manner.
      if ($url->isRouted() && !isset($options['attributes']['data-drupal-link-system-path'])) {
        // @todo System path is deprecated - use the route name and parameters.
        $system_path = $url->getInternalPath();

        // Special case for the front page.
        if ($url->getRouteName() === '<front>') {
          $system_path = '<front>';
        }

        if (!empty($system_path)) {
          $options['attributes']['data-drupal-link-system-path'] = $system_path;
        }
      }
    }
  }

}
