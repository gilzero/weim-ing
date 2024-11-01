<?php

namespace Drupal\google_places\Plugin\AiAutomatorType;

use Drupal\ai_automators\Attribute\AiAutomatorType;
use Drupal\ai_automators\PluginBaseClasses\ExternalBase;
use Drupal\ai_automators\PluginInterfaces\AiAutomatorTypeInterface;
use Drupal\google_places\GooglePlacesApi;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The rules for a google places field.
 */
#[AiAutomatorType(
  id: 'google_places_address_to_google_places_multi',
  label: new TranslatableMarkup('Google Places: Multifield Filler'),
  field_rule: 'google_places_multifield',
  target: '',
)]
class AddressToGooglePlacesMulti extends ExternalBase implements AiAutomatorTypeInterface, ContainerFactoryPluginInterface {

  /**
   * The Google Places API.
   *
   * @var \Drupal\google_places\GooglePlacesApi
   */
  protected $googlePlacesApi;

  /**
   * Google Places Mapping.
   */
  protected $googlePlacesMapping = [
    'automator_google_places_sku_display_name_field' => [
      'title' => 'Display Name',
      'select_type' => 'string',
      'sku' => 'Basic',
      'field_mask' => 'displayName',
      'multiple' => FALSE,
      'key' => 'text',
    ],
    'automator_google_places_sku_website_field' => [
      'title' => 'Website',
      'select_type' => 'link',
      'sku' => 'Advanced',
      'field_mask' => 'websiteUri',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_maps_url_field' => [
      'title' => 'Google Maps URL',
      'select_type' => 'link',
      'sku' => 'Basic',
      'field_mask' => 'googleMapsUri',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_phone_field' => [
      'title' => 'Phone',
      'select_type' => 'telephone',
      'sku' => 'Advanced',
      'field_mask' => 'internationalPhoneNumber',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_rating_field' => [
      'title' => 'Rating',
      'select_type' => 'decimal',
      'sku' => 'Advanced',
      'field_mask' => 'rating',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_rating_amount_field' => [
      'title' => 'Rating Amount',
      'select_type' => 'integer',
      'sku' => 'Advanced',
      'field_mask' => 'userRatingCount',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_business_status_field' => [
      'title' => 'Business Status',
      'select_type' => 'string',
      'sku' => 'Basic',
      'field_mask' => 'businessStatus',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_business_type_field' => [
      'title' => 'Business Type',
      'select_type' => 'string',
      'sku' => 'Basic',
      'field_mask' => 'primaryType',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_other_business_type_field' => [
      'title' => 'Other Business Type',
      'select_type' => 'string',
      'sku' => 'Location Only',
      'field_mask' => 'types',
      'multiple' => TRUE,
      'key' => '',
    ],
    'automator_google_places_sku_office_hours_field' => [
      'title' => 'Office Hours',
      'select_type' => 'office_hours',
      'sku' => 'Advanced',
      'field_mask' => 'regularOpeningHours',
      'multiple' => FALSE,
      'key' => '',
    ],
    'automator_google_places_sku_reviews_field' => [
      'title' => 'Reviews',
      'select_type' => 'string_long',
      'sku' => 'Advanced',
      'field_mask' => 'reviews',
      'multiple' => TRUE,
      'key' => '',
    ],
    'automator_google_places_sku_images_field' => [
      'title' => 'Images',
      'select_type' => 'image',
      'sku' => 'Per Photo',
      'field_mask' => 'photos',
      'multiple' => TRUE,
      'key' => '',
    ],
  ];

  /**
   * Construct a boolean field.
   *
   * @param array $configuration
   *   Inherited configuration.
   * @param string $plugin_id
   *   Inherited plugin id.
   * @param mixed $plugin_definition
   *   Inherited plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, GooglePlacesApi $googlePlacesApi) {
    $this->googlePlacesApi = $googlePlacesApi;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('google_places.api')
    );
  }

  /**
   * {@inheritDoc}
   */
  public $title = 'Google Places: Multifield Filler';

  /**
   * {@inheritDoc}
   */
  public function needsPrompt() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function advancedMode() {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function checkIfEmpty($value) {
    return empty($value[0]['value']) ? [] : ['spoof'];
  }

  /**
   * {@inheritDoc}
   */
  public function placeholderText() {
    return "";
  }

  /**
   * {@inheritDoc}
   */
  public function allowedInputs() {
    return ['address'];
  }

  /**
   * {@inheritDoc}
   */
  public function extraFormFields(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, FormStateInterface $form_state, array $defaultValues = []) {
    // The data we can collect and where it should be filled in.
    $options = [];
    foreach (['string', 'string_long', 'link', 'telephone', 'decimal', 'integer', 'office_hours', 'boolean', 'image'] as $type) {
      $options[$type] = ['' => $this->t('-- Leave Empty --')] + $this->getGeneralHelper()->getFieldsOfType($entity, $type);
    }

    $form['google_places_mapping'] = [
      '#type' => 'details',
      '#title' => $this->t('Mapping'),
      '#open' => TRUE,
      '#weight' => 20,
    ];

    foreach ($this->googlePlacesMapping as $key => $value) {
      $multiple = $value['multiple'] ? 'can' : 'cannot';
      $form['google_places_mapping'][$key] = [
        '#type' => 'select',
        '#title' => $this->t($value['title']),
        '#description' => $this->t('The field to populate. This @multiple have multiple fields. <em>SKU: @sku</em>', [
          '@sku' => $value['sku'],
          '@multiple' => $multiple,
        ]),
        '#options' => $options[$value['select_type']],
        '#default_value' => $defaultValues[$key] ?? '',
      ];
    }

    $form['google_places_mapping']['automator_google_places_amount_of_images'] = [
      '#type' => 'number',
      '#title' => $this->t('Amount of Images'),
      '#description' => $this->t('The amount of images to fetch from the place. <em>Per Photo Cost.</em>'),
      '#default_value' => $defaultValues['automator_google_places_amount_of_images'] ?? 1,
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function generate(ContentEntityInterface $entity, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    $values = [];
    $fieldMasks = [];
    foreach ($automatorConfig as $key => $value) {
      if (strpos($key, 'google_places_sku_') === 0 && $value !== '') {
        $fieldMasks[] = $this->googlePlacesMapping['automator_' . $key]['field_mask'];
      }
    }
    // Amount of images.
    $amountOfImages = $automatorConfig['google_places_amount_of_images'] ?? 1;

    // Go through all the addresses and do all the calls.
    foreach ($entity->{$automatorConfig['base_field']} as $address) {
      // Get the address.
      $search = $this->buildSearchFromAddress($address->getValue());
      $data = $this->googlePlacesApi->placesSearchApi($search, 'places.id');

      if (isset($data['places'][0]['id'])) {
        $response = $this->googlePlacesApi->placesDetailsApi($data['places'][0]['id'], implode(',', $fieldMasks));
        // Limit images to the amount of images.
        if (isset($response['photos'])) {
          $response['photos'] = array_slice($response['photos'], 0, $amountOfImages);
        }
        $values = array_merge_recursive($values, $response);
      }
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public function verifyValue(ContentEntityInterface $entity, $value, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {
    if (!empty($value)) {
      return TRUE;
    }
  }

  /**
   * {@inheritDoc}
   */
  public function storeValues(ContentEntityInterface $entity, array $values, FieldDefinitionInterface $fieldDefinition, array $automatorConfig) {

    // If image is set.
    $imagesField = $automatorConfig['google_places_sku_images_field'] ?? '';
    if ($imagesField) {
      $fileHelper = $this->getFileHelper();
      // Get the field definition for the image field from the entity.
      $imageFieldDefinition = $entity->getFieldDefinition($imagesField);
    }
    $images = [];
    foreach ($values as $fieldType => $value) {
      // Look up the key.
      foreach ($this->googlePlacesMapping as $key => $mapping) {
        $key = str_replace('automator_', '', $key);
        if ($fieldType == $mapping['field_mask']) {
          $fieldName = $automatorConfig[$key] ?? '';
          // If there is no value, we skip.
          if (!$fieldName) {
            continue;
          }
          switch ($mapping['select_type']) {
            case 'string':
            case 'link':
            case 'telephone':
            case 'decimal':
            case 'integer':
              $entity->set($fieldName, $mapping['key'] ? $value[$mapping['key']] : $value);
              break;

            case 'string_long':
              // A rating.
              $renderedValues = [];
              foreach ($value as $review) {
                $rendered = 'By ' . $review['authorAttribution']['displayName'] . ' on ' . $review['publishTime'] . "\n";
                $rendered .= 'Rating: ' . $review['rating'] . "\n";
                $rendered .= 'Text: ' . $review['text']['text'];
                $renderedValues[] = $rendered;
              }
              $entity->set($fieldName, $renderedValues);
              break;

            case 'office_hours':
              if (isset($value['periods'][0])) {
                $openingHours = [];
                foreach ($value['periods'] as $period) {
                  $openingHours[] = [
                    'day' => $period['open']['day'],
                    'starthours' => str_pad($period['open']['hour'], 2, 0, STR_PAD_LEFT) . str_pad($period['open']['minute'], 2, 0, STR_PAD_LEFT),
                    'endhours' => str_pad($period['close']['hour'], 2, 0, STR_PAD_LEFT) . str_pad($period['close']['minute'], 2, 0, STR_PAD_LEFT),
                  ];
                }
                $entity->set($fieldName, $openingHours);
              }
              break;

            case 'boolean':
              $entity->set($fieldName, $value ? '1' : '0');
              break;

            case 'image':
              foreach ($value as $image) {
                $newImageBinary = $this->googlePlacesApi->getPhoto($image['name'], ['maxWidthPx' => 2540]);
                $newImage = $fileHelper->generateImageMetaDataFromBinary($newImageBinary, $fileHelper->createFilePathFromFieldConfig('google_places.jpg', $imageFieldDefinition, $entity));
                if (isset($newImage['target_id'])) {
                  // Attribution.
                  $newImage['alt'] = $image['authorAttributions'][0]['displayName'];
                  $images[] = $newImage;
                }
              }
              $entity->set($fieldName, $images);
              break;
          }
        }
      }
    }
    // Lastly we switch the boolean that it has run.
    $entity->set($fieldDefinition->getName(), '1');
  }

  /**
   * Build search from address.
   *
   * @param array $address
   *   The address.
   *
   * @return string
   *   The search string.
   */
  public function buildSearchFromAddress(array $address) {
    $parts = [];

    if (!empty($address['organization'])) {
      $parts[] = $address['organization'];
    }
    if (!empty($address['address_line1'])) {
      $parts[] = $address['address_line1'];
    }
    if (!empty($address['postal_code'])) {
      $parts[] = $address['postal_code'];
    }
    if (!empty($address['locality'])) {
      $parts[] = $address['locality'];
    }
    if (!empty($address['country_code'])) {
      $parts[] = $address['country_code'];
    }

    return implode(', ', $parts);
  }

}
