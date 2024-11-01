<?php

namespace Drupal\ui_patterns_settings\Plugin\UiPatterns\SettingDataProvider;

use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderBase;
use Drupal\ui_patterns_settings\Plugin\PatternSettingDataProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the ui_patterns_settings_data_provider.
 *
 * @UiPatternsSettingDataProvider(
 *   id = "breadcrumb",
 *   label = @Translation("Breadcrumb"),
 *   description = @Translation("Data Provider for menus."),
 *   settingType = "links"
 * )
 */
class BreadcrumbDataProvider extends PatternSettingDataProviderBase implements PatternSettingDataProviderInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The breadcrumb manager.
   *
   * @var \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
   */
  protected $breadcrumbManager;

  /**
   *
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
                       $plugin_id,
                       $plugin_definition
  ) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('module_handler'),
      $container->get('entity_type.manager'),
    );
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');
    $plugin->setStringTranslation($translation);
    $plugin->breadcrumbManager = $container->get('breadcrumb');
    $plugin->routeMatch = $container->get('current_route_match');
    return $plugin;
  }

  /**
   * Get breadcrumb items.
   *
   * @return array
   *   List of items.
   */
  private function getBreadcrumbItems(): array {
    $breadcrumb = $this->breadcrumbManager->build($this->routeMatch);
    $links = [];
    foreach ($breadcrumb->getLinks() as $link) {
      $links[] = [
        "title" => $link->getText(),
        "url" => $link->getUrl()->toString(),
      ];
    }
    return $links;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($value) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getData($value) {
    return $this->getBreadcrumbItems();
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {
    $breadcrumb = $this->breadcrumbManager->build($this->routeMatch);
    $renderable = $breadcrumb->toRenderable();
    if (isset($renderable["#cache"])) {
      $element["#cache"] = $element["#cache"] ?: [];
      $element["#cache"] = array_merge($element["#cache"], $renderable["#cache"]);
    }
  }

}
