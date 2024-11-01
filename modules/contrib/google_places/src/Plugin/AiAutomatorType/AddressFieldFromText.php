<?php

namespace Drupal\google_places\Plugin\AiAutomatorType;

use Drupal\ai\AiProviderPluginManager;
use Drupal\ai\Service\AiProviderFormHelper;
use Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface;
use Drupal\ai_automators\Attribute\AiAutomatorType;
use Drupal\ai_automators\PluginBaseClasses\RuleBase;
use Drupal\ai_automators\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\google_places\GooglePlacesApi;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for an address field.
 */
#[AiAutomatorType(
  id: 'google_places_address_field_from_text',
  label: new TranslatableMarkup('Google Places: LLM Text Address Finder'),
  field_rule: 'address',
  target: '',
)]
class AddressFieldFromText extends RuleBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public $title = 'Google Places: LLM Text Address Finder';

  /**
   * The Google Places API.
   */
  public GooglePlacesApi $googlePlacesApi;

  /**
   * The Prompt to Json.
   */
  public PromptJsonDecoderInterface $promptJsonDecoder;

  /**
   * Construct an image field.
   *
   * @param array $configuration
   *   Inherited configuration.
   * @param string $plugin_id
   *   Inherited plugin id.
   * @param mixed $plugin_definition
   *   Inherited plugin definition.
   * @param \Drupal\ai\AiProviderPluginManager $aiPluginManager
   *   The AI Plugin Manager.
   * @param \Drupal\ai\Service\AiProviderFormHelper $formHelper
   *   The AI Form Helper.
   * @param \Drupal\ai\Service\PromptJsonDecoder\PromptJsonDecoderInterface $promptJsonDecoder
   *   The Prompt to Json.
   * @param \Drupal\google_places\GooglePlacesApi $googlePlacesApi
   *   The Google Places requester.
   */
  final public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AiProviderPluginManager $aiPluginManager,
    AiProviderFormHelper $formHelper,
    PromptJsonDecoderInterface $promptJsonDecoder,
    GooglePlacesApi $googlePlacesApi,
  ) {
    parent::__construct($aiPluginManager, $formHelper, $promptJsonDecoder);
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
      $container->get('ai.provider'),
      $container->get('ai.form_helper'),
      $container->get('ai.prompt_json_decode'),
      $container->get('google_places.api'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function helpText() {
    return $this->t("Scrape data for addresses and give them back in a structured format. The prompt has to always answer with searching for places.");
  }

  /**
   * {@inheritDoc}
   */
  public function placeholderText() {
    return "From the context text, find all geographical places that exists that can be plotted on Google Maps and give them back as something that would give a credible answer in Google maps. Add city and country if you know it. Try to figure out country from the context. Also give the title of the location.\n\nContext:\n{{ context }}";
  }

  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    $prompts = parent::generate($entity, $fieldDefinition, $automatorConfig);
    $total = [];
    $instance = $this->prepareLlmInstance('chat', $automatorConfig);
    // Add to get functional output.
    foreach ($prompts as $prompt) {
      // Add to get functional output.
      $prompt .= "\n-------------------------------------\n\nDo not include any explanations, only provide a RFC8259 compliant JSON response following this format without deviation.\n[{\"value\": {\"search_text\": \"The search text for Google Maps\", \"title\": \"A title of the location, like a company name, a country name or a persons name.\"}}]\n\n";
      $prompt .= "Examples would be:\n";
      $prompt .= "[{\"value\": {\"search_text\": \"Radisson Collection Hotel, Berlin, Germany\", \"title\": \"Radisson Collection Hotel\"}},{\"value\": {\"search_text\": \"Spandauer Straße, Berlin, Germany\", \"title\": \"Spandauer Straße\"}}]\n";
      $prompt .= "[{\"value\": {\"search_text\": \"Gothenburg, Sweden\", \"title\": \"Gothenburg\"}},{\"value\": {\"search_text\": \"Sannegårdens Pizzeria Johanneberg, Gibraltargatan 52, 412 58 Göteborg, Sweden\", \"title\": \"Sannegårdens Pizzeria Johanneberg\"}}]\n";
      $prompt .= "[{\"value\": {\"search_text\": \"Oliver Schrott Kommunikation offices, Germany\", \"title\": \"Oliver Schrott Kommunikation offices\"}},{\"value\": {\"search_text\": \"Sannegårdens Pizzeria Johanneberg, Gibraltargatan 52, 412 58 Göteborg, Sweden\", \"title\": \"Sannegårdens Pizzeria Johanneberg\"}}]\n";
      $values = $this->runChatMessage($prompt, $automatorConfig, $instance);
      if (!empty($values)) {
        $total = array_merge_recursive($total, $values);
      }
    }

    return $total;
  }

  /**
   * {@inheritDoc}
   */
  public function verifyValue(ContentEntityInterface $entity, $value, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    if ($value) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function storeValues(ContentEntityInterface $entity, array $values, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {

    $addresses = [];

    foreach ($values as $value) {
      if (isset($value['title']) && isset($value['search_text'])) {
        $place = $this->googlePlacesApi->getPlaceInfo($value['search_text']);
        if (isset($place['result']['address_components'])) {
          $renderList = [];
          foreach ($place['result']['address_components'] as $part) {
            $renderList[$part['types'][0]] = [
              'long_name' => $part['long_name'],
              'short_name' => $part['short_name'],
            ];
          }

          if (!empty($renderList['country']['short_name'])) {
            $street = $renderList['route']['long_name'] ?? '';
            if ($street && !empty($renderList['street_number']['long_name'])) {
              $street .= ' ' . $renderList['street_number']['long_name'];
            }

            $locality = $renderList['locality']['long_name'] ?? '';
            if (empty($locality)) {
              $locality = $renderList['postal_town']['long_name'] ?? '';
            }

            $addresses[] = [
              'country_code' => $renderList['country']['short_name'] ?? '',
              'administrative_area' => $renderList['administrative_area_level_1']['long_name'] ?? '',
              'locality' => $locality,
              'postal_code' => $renderList['postal_code']['long_name'] ?? '',
              'sorting_code' => $renderList['sorting_code']['long_name'] ?? '',
              'address_line1' => $street,
              'organization' => $value['title'] ?? '',
            ];

          }
        }
      }
    }

    // Then set the value.
    $entity->set($fieldDefinition->getName(), $addresses);
    return TRUE;
  }

}
