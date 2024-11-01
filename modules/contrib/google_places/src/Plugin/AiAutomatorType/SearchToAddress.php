<?php

namespace Drupal\google_places\Plugin\AiAutomatorType;

use Drupal\ai_automators\Attribute\AiAutomatorType;
use Drupal\ai_automators\PluginBaseClasses\ExternalBase;
use Drupal\ai_automators\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\google_places\GooglePlacesApi;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for an an address.
 */
#[AiAutomatorType(
  id: 'google_places_search_to_address',
  label: new TranslatableMarkup('Google Places: Search to Address'),
  field_rule: 'address',
  target: '',
)]
class SearchToAddress extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public $title = 'Google Places: Search to Address';

  /**
   * The Google Places API.
   */
  public GooglePlacesApi $googlePlacesApi;

  /**
   * Construct an image field.
   *
   * @param array $configuration
   *   Inherited configuration.
   * @param string $plugin_id
   *   Inherited plugin id.
   * @param mixed $plugin_definition
   *   Inherited plugin definition.
   * @param \Drupal\google_places\GooglePlacesApi $googlePlacesApi
   *   The Google Places requester.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GooglePlacesApi $googlePlacesApi,
  ) {
    $this->googlePlacesApi = $googlePlacesApi;
  }

  /**
   * {@inheritDoc}
   */
  final public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_places.api'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function helpText() {
    return $this->t("Do a Google Maps search and get back X amount of addresses. No LLM possible, use Google Places: LLM Text Address Finder for that.");
  }

  /**
   * {@inheritDoc}
   */
  public function placeholderText() {
    return "Hotels near Trafalgar Square, London, UK";
  }

  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    $fieldMask = 'places.displayName,places.addressComponents';

    $prompts = parent::generate($entity, $fieldDefinition, $automatorConfig);
    $addresses = [];
    foreach ($prompts as $prompt) {
      $response = $this->googlePlacesApi->placesSearchApi($prompt, $fieldMask);
      if (!empty($response['places'][0])) {
        foreach ($response['places'] as $place) {
          // Displayname is a must.
          if (!empty($place['displayName']['text'])) {
            $components = [];
            foreach ($place['addressComponents'] as $component) {
              $components[$component['types'][0] . '_long'] = $component['longText'];
              $components[$component['types'][0] . '_short'] = $component['shortText'];
            }
            $street = $components['route_long'] ?? '';
            if ($street) {
              $street .= !empty($components['street_number_short']) ? ' ' . $components['street_number_short'] : '';
            }
            $address = [
              'country_code' => $components['country_short'] ?? '',
              'administrative_area' => $components['administrative_area_level_1_long'] ?? '',
              'locality' => $components['postal_town_long'] ?? '',
              'postal_code' => $components['postal_code_long'] ?? '',
              'address_line1' => $street,
              'organization' => $place['displayName']['text'] ?? '',
            ];
          }
          $addresses[] = $address;
        }
      }
    }
    return $addresses;
  }

}
