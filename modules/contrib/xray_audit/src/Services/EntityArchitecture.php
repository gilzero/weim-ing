<?php

namespace Drupal\xray_audit\Services;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;

/**
 * Service to get info about entity architecture.
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EntityArchitecture implements EntityArchitectureInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity file manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfoService;

  /**
   * Construct service Extractor Display Modes.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Service Entity Type Manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Service Entity Field Manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entityDisplayRepository
   *   Service Entity Display Repository.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInfoService
   *   Service Bundle Info.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityFieldManagerInterface $entityFieldManager, EntityDisplayRepositoryInterface $entityDisplayRepository, EntityTypeBundleInfoInterface $bundleInfoService) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->bundleInfoService = $bundleInfoService;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataForEntityFieldArchitecture() {
    $data = [];
    $entities = $this->getContentEntitiesInfo();

    foreach ($entities as $key => $entityInfo) {
      foreach ($entityInfo['bundles'] as $bundle) {
        $data[]['content'] = [
          'entity' => $key,
          'bundle' => $bundle['machine_name'],
          'machine_name' => $bundle['machine_name'],
          'label' => $bundle['label'],
          'type' => 'entity',
        ];

        // Get field info.
        $data_fields = $this->getEntityFieldData($key, $bundle['machine_name']);
        $this->getWidgetInfo($data_fields, $key, $bundle['machine_name']);
        $this->preprocessDataGeneral($data_fields);
        $this->sortMultidimensionalArray($data_fields, 'field_type');
        $this->preprocessDataGeneral($data_fields);
        $data = array_merge($data, array_values($data_fields));
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataForViewDisplayArchitecture(string $entity_type_id, string $bundle): array {
    $data_display_architecture = [];

    // Fields.
    $data_fields = $this->getEntityFieldData($entity_type_id, $bundle);
    if (empty($data_fields)) {
      return $data_display_architecture;
    }
    $this->getWidgetInfo($data_fields, $entity_type_id, $bundle);
    $this->preprocessDataGeneral($data_fields);
    $data_display_architecture['fields'] = $data_fields;

    // Displays.
    $displays = $this->getViewDisplayModesConfiguration($entity_type_id, $bundle);
    if (empty($displays)) {
      return $data_display_architecture;
    }
    $display_data = $this->preprocessViewDisplays($displays);
    $data_display_architecture['displays'] = $display_data;

    return $data_display_architecture;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFieldData(string $entity_type_id, string $bundle) {
    $data = [];
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach ($fields as $id => $field) {
      $data_map = $this->fieldDefinitionMapping();
      $data_map['entity'] = $entity_type_id;
      $data_map['bundle'] = $bundle;
      $data_map['field'] = $field;
      if ($field instanceof BaseFieldDefinition) {
        $data[$id] = $this->getDataFromBaseFieldDefinition($field, $data_map);
        continue;
      }
      if ($field instanceof BaseFieldOverride) {
        $data[$id] = $this->getDataFromBaseFieldBaseOverride($field, $data_map);
        continue;
      }
      if ($field instanceof FieldConfig) {
        $data[$id] = $this->getDataFromBaseFieldConfig($field, $data_map);
        continue;
      }
    }
    return $data;
  }

  /**
   * Get list of all view displays for an entity.
   *
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $bundle
   *   Bundle.
   *
   * @return mixed[]
   *   View displays.
   */
  protected function getViewDisplayModesConfiguration(string $entity_type_id, string $bundle) {
    $view_displays = [];
    $view_display_list = $this->entityDisplayRepository->getViewModeOptionsByBundle($entity_type_id, $bundle);
    $view_machine_name_list = array_keys($view_display_list);
    foreach ($view_machine_name_list as $view_machine_name) {
      $view_displays[] = $this->entityDisplayRepository->getViewDisplay($entity_type_id, $bundle, $view_machine_name);
    }
    return $view_displays;
  }

  /**
   * Preprocess view displays.
   *
   * @param mixed[] $viewDisplays
   *   View displays.
   *
   * @return mixed[]
   *   View displays processed.
   */
  protected function preprocessViewDisplays(array $viewDisplays): array {
    $processed = [];
    foreach ($viewDisplays as $viewDisplay) {
      if ($viewDisplay instanceof EntityViewDisplayInterface) {
        $this->preprocessViewDisplay($processed, $viewDisplay);
      }
    }
    return $processed;
  }

  /**
   * Preprocess view display.
   *
   * @param array $processed
   *   Processed.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $viewDisplay
   *   View display.
   */
  protected function preprocessViewDisplay(array &$processed, EntityViewDisplayInterface $viewDisplay) {
    $processed[$viewDisplay->getMode()] = [
      'mode' => $viewDisplay->getMode(),
      'id' => $viewDisplay->id(),
      'view_display' => $viewDisplay,
      'processed' => $this->getConfigurationFromViewDisplay($viewDisplay),
    ];
  }

  /**
   * Get configuration from a view display.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $viewDisplay
   *   View display.
   *
   * @return array
   *   Configuration of view display.
   */
  protected function getConfigurationFromViewDisplay(EntityViewDisplayInterface $viewDisplay): array {
    $component_configurations = [];
    $components = $viewDisplay->getComponents();
    foreach ($components as $key_component => &$component) {
      $component['machine_name'] = $key_component;
    }

    // Sort the fields.
    usort($components, function ($a, $b) {
      if (!isset($a['weight']) || !isset($b['weight'])) {
        return -1;
      }
      return $a['weight'] <=> $b['weight'];
    });

    foreach ($components as $component) {
      $component_configurations[$component['machine_name']] = [
        'type' => $component['type'] ?? NULL,
        'label' => $component['label'] ?? NULL,
        'field_id' => $component['machine_name'],
        'settings' => !empty($component['settings']) ? $this->implodeMultidimensionalArray($component['settings']) : '',
      ];
    }
    return $component_configurations;
  }

  /**
   * Field definition map.
   *
   * @return array
   *   Data structure.
   */
  protected function fieldDefinitionMapping(): array {
    $data = [];

    $data['entity'] = NULL;
    $data['bundle'] = NULL;
    $data['field_type'] = NULL;
    $data['data_type'] = NULL;
    $data['type'] = NULL;
    $data['name'] = NULL;
    $data['label'] = NULL;
    $data['computed'] = NULL;
    $data['cardinality'] = NULL;
    $data['is_required'] = NULL;
    $data['is_base_field'] = NULL;
    $data['is_read_only'] = NULL;
    $data['is_translatable'] = NULL;
    $data['is_revisionable'] = NULL;
    $data['description'] = NULL;
    $data['data_definitions'] = NULL;
    $data['is_view_display_configurable'] = NULL;
    $data['view_display_options'] = NULL;
    $data['is_form_display_configurable'] = NULL;
    $data['form_display_options'] = NULL;
    $data['field'] = NULL;
    $data['widget_form_default'] = NULL;
    $data['widget_form_default_settings'] = NULL;
    $data['view_display_modes'] = NULL;
    return $data;
  }

  /**
   * Get widget info.
   *
   * @param array $data
   *   Data.
   * @param string $entity_type_id
   *   Entity type id.
   * @param string $bundle
   *   Bundle.
   */
  protected function getWidgetInfo(array &$data, string $entity_type_id, string $bundle) {
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = $this->entityDisplayRepository->getFormDisplay($entity_type_id, $bundle);
    foreach ($data as &$field) {
      $component = $form_display->getComponent($field['name']);
      if (!empty($component)) {
        $field["widget_form_default"] = $component['type'] ?? NULL;
        $field["widget_form_default_settings"] = $component['settings'] ?? [];
      }
    }
  }

  /**
   * Get data from fields instance of BaseFieldDefinition.
   *
   * @param \Drupal\Core\Field\BaseFieldDefinition $field
   *   Base field definition.
   * @param array $data
   *   Data without values.
   *
   * @return array
   *   Data with values.
   */
  protected function getDataFromBaseFieldDefinition(BaseFieldDefinition $field, array $data) {
    $data['field_type'] = self::TYPE_BASE_FIELD_DEFINITION;
    $data['data_type'] = $field->getDataType();
    $data['type'] = $field->getType();
    $data['name'] = $field->getName();
    $data['label'] = $field->getLabel();

    $data['computed'] = $field->isComputed();
    $data['cardinality'] = $field->getCardinality();
    $data['is_required'] = $field->isRequired();
    $data['is_base_field'] = $field->isBaseField();
    $data['is_read_only'] = $field->isReadOnly();
    $data['is_translatable'] = $field->isTranslatable();
    $data['is_revisionable'] = $field->isRevisionable();
    $data['constraints'] = $field->getConstraints();
    $data['settings'] = $field->getSettings();

    $data['default_value'] = $field->getDefaultValueLiteral();
    $data['default_value_callback'] = $field->getDefaultValueCallback();
    $data['description'] = $field->getDescription();

    // Display view.
    $data['is_view_display_configurable'] = $field->isDisplayConfigurable('view');
    $data['view_display_options'] = $field->getDisplayOptions('view');

    // Display form.
    $data['is_form_display_configurable'] = $field->isDisplayConfigurable('form');
    $data['view_form_options'] = $field->getDisplayOptions('form');

    if ($data['label'] instanceof MarkupInterface) {
      $data['label'] = $data['label']->__toString();
    }

    if ($data['description'] instanceof MarkupInterface) {
      $data['description'] = $data['description']->__toString();
    }

    return $data;
  }

  /**
   * Get data from fields instance of BaseFieldOverride.
   *
   * @param \Drupal\Core\Field\Entity\BaseFieldOverride $field
   *   Base field definition.
   * @param array $data
   *   Data without values.
   *
   * @return array
   *   Data with values.
   */
  protected function getDataFromBaseFieldBaseOverride(BaseFieldOverride $field, array $data) {
    $data['field_type'] = self::TYPE_BASE_FIELD_OVERRIDE;
    $this->getDataFromFieldConfigBase($data, $field);
    return $data;
  }

  /**
   * Get data from fields instance of FieldConfig.
   *
   * @param \Drupal\field\Entity\FieldConfig $field
   *   Base field definition.
   * @param array $data
   *   Data without values.
   *
   * @return array
   *   Data with values.
   */
  protected function getDataFromBaseFieldConfig(FieldConfig $field, array $data) {
    $data['field_type'] = self::TYPE_BASE_FIELD_CONFIG;
    $this->getDataFromFieldConfigBase($data, $field);
    return $data;
  }

  /**
   * Get data from fields instance of FieldConfigBase.
   *
   * @param array $data
   *   Data without values.
   * @param \Drupal\Core\Field\FieldConfigBase $field
   *   Base field definition.
   */
  protected function getDataFromFieldConfigBase(array &$data, FieldConfigBase $field) {
    $data['data_type'] = $field->getDataType();
    $data['type'] = $field->getType();
    $data['name'] = $field->getName();
    $data['label'] = $field->getLabel();
    $data['computed'] = $field->isComputed();
    $data['is_required'] = $field->isRequired();
    $data['is_read_only'] = $field->isReadOnly();
    $data['is_translatable'] = $field->isTranslatable();
    $data['description'] = $field->getDescription();
    $data['default_value'] = $field->getDefaultValueLiteral();
    $data['default_value_callback'] = $field->getDefaultValueCallback();
    $data['is_view_display_configurable'] = $field->isDisplayConfigurable('view');
    $data['view_display_options'] = $field->getDisplayOptions('view');
    $data['is_form_display_configurable'] = $field->isDisplayConfigurable('view');
    $data['form_display_options'] = $field->getDisplayOptions('form');
    $data['settings'] = $field->getSettings();
  }

  /**
   * Data to preprocess the data from fields config.
   *
   * @param array $data
   *   Data to preprocess.
   *
   * @SuppressWarnings(PHPMD.NPathComplexity)
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function preprocessDataGeneral(array &$data) {
    foreach ($data as &$field) {
      $field['content'] = [];
      $field['content']['entity'] = $field['entity'];
      $field['content']['bundle'] = $field['bundle'];
      $field['content']['type'] = $field['field_type'];
      $field['content']['machine_name'] = $field['name'];
      $field['content']['label'] = $field['label'];
      $field['content']['computed'] = $field['computed'] ? 'Yes' : 'No';
      $field['content']['data_type'] = $field['type'];
      $field['content']['cardinality'] = $field['cardinality'];

      // Settings.
      if (is_array($field['settings'])) {
        if (isset($field['settings']['target_type'])) {
          $settings = [];
          $settings['handler'] = $field['settings']['handler'];
          $settings['target_type'] = $field['settings']['target_type'];
          if (isset($field['settings']["handler_settings"]["target_bundles"])) {
            $settings["target_bundles"] = implode(', ', $field['settings']["handler_settings"]["target_bundles"]);
          }
          $field['content']['data_type_settings'] = $this->implodeMultidimensionalArray($settings);
        }
        else {
          if (isset($field['settings']['allowed_values']) && is_array($field['settings']['allowed_values'])) {
            $allowed_values = [];
            foreach ($field['settings']['allowed_values'] as $key => $value) {
              $allowed_values[] = sprintf('%s | %s', $key, $value);
            }

            $field['settings']['allowed_values'] = implode(', ', $allowed_values);
          }
          $field['content']['data_type_settings'] = $this->implodeMultidimensionalArray($field['settings']);
        }
        if (isset($field['settings']['link_type'])) {
          switch ($field['settings']['link_type']) {
            case LinkItemInterface::LINK_EXTERNAL:
              $link_type = 'external';
              break;

            case LinkItemInterface::LINK_INTERNAL:
              $link_type = 'internal';
              break;

            case LinkItemInterface::LINK_GENERIC:
            default:
              $link_type = 'generic';
              break;
          }
          $field['settings']['link_type'] = $link_type;
        }
      }

      $field['content']['mandatory'] = $field['is_required'] ? 'Yes' : 'No';
      $field['content']['read_only'] = $field['is_read_only'] ? 'Yes' : 'No';
      $field['content']['translatable'] = $field['is_translatable'] ? 'Yes' : 'No';
      $field['content']['revisionable'] = $field['is_revisionable'] ? 'Yes' : 'No';

      // Get default values.
      $default_value_indexes = ['default_value', 'default_value_callback'];
      foreach ($default_value_indexes as $default_value_index) {
        if (is_array($field[$default_value_index])) {
          $field['content'][$default_value_index] = (string) $this->implodeMultidimensionalArray($field[$default_value_index]);
        }
        elseif (is_string($field[$default_value_index]) || is_numeric($field[$default_value_index])) {
          $field['content'][$default_value_index] = $field[$default_value_index];
        }
        else {
          $field['content'][$default_value_index] = '';
        }
      }

      $field['content']['form_widget'] = '-';
      if (!empty($field['widget_form_default'])) {
        $widget = ['type' => $field['widget_form_default']];
        $widget = array_merge($widget, $field['widget_form_default_settings']);
        unset($widget['placeholder']);
        unset($widget['placeholder_url']);
        unset($widget['placeholder_title']);
        $field['content']['form_widget'] = $this->implodeSimpleArrayWithKey($widget);
      }

      $field['content']['description'] = $field['description'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getContentEntitiesInfo(): array {
    $entityTypesDefinitions = $this->entityTypeManager->getDefinitions();
    $entities = [];

    /** @var \Drupal\Core\Entity\ContentEntityType $definition */
    foreach ($entityTypesDefinitions as $key => $definition) {
      $group = $definition->getGroup();
      if ($group !== 'content') {
        continue;
      }
      $entities[$key]['definition'] = $definition;
      $entities[$key]['label'] = $definition->getLabel();
      $entities[$key]['entity_type'] = $definition->getBundleEntityType();
      $entities[$key]['machine_name'] = $key;
      $entities[$key]['bundles'] = $this->bundleInfoService->getBundleInfo($key);
      foreach ($entities[$key]['bundles'] as $bundle_key => &$bundle) {
        $bundle['machine_name'] = $bundle_key;
        $bundle['label'] = $bundle['label'];
      }
    }

    return $entities;
  }

  /**
   * Implode multidimensional array.
   *
   * @param array $array
   *   Array to implode.
   *
   * @return string
   *   Array imploded.
   */
  protected function implodeMultidimensionalArray(array $array) {
    $array = $this->transformArrayMultidimensional($array);
    return $this->implodeSimpleArrayWithKey($array);
  }

  /**
   * Transform multidimensional array into uni-personal.
   *
   * @param array $array
   *   Transform multidimensional array.
   *
   * @return array
   *   Array one-dimensional.
   */
  protected function transformArrayMultidimensional(array $array) {
    $result = [];
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $result = array_merge($result, $this->transformArrayMultidimensional($value));
        continue;
      }
      if (empty($value)) {
        continue;
      }
      $result[$key] = $value;
    }
    return $result;
  }

  /**
   * Implode a one-dimensional array.
   *
   * @param array $array
   *   Array to implode.
   *
   * @return string
   *   Array imploded.
   */
  protected function implodeSimpleArray(array $array) {
    if (empty($array)) {
      return "";
    }
    return $this->implodeSimpleArrayProcessResult($array);
  }

  /**
   * Implode a one-dimensional array with key.
   *
   * @param array $array
   *   Array to implode.
   *
   * @return string
   *   Array imploded.
   *
   * @SuppressWarnings(PHPMD.CyclomaticComplexity)
   */
  protected function implodeSimpleArrayWithKey(array $array) {
    $result = [];
    foreach ($array as $key => $value) {
      // Transform boolean to string.
      if (is_bool($value)) {
        $value = $value ? '1' : '0';
      }
      elseif (is_array($value)) {
        $array_scalar_values = [];
        foreach ($value as $array_value) {
          if (is_scalar($array_value)) {
            $array_scalar_values[] = $array_value;
          }
          elseif (is_array($array_value)) {
            $array_scalar_values[] = $this->implodeSimpleArrayWithKey($array_value);
          }
        }
        if (!empty(array_filter($array_scalar_values))) {
          $value = implode(', ', $array_scalar_values);
        }
        else {
          continue;
        }
      }

      // Ignore serialize data.
      if (is_string($value) && strpos($value, 'a:') === 0) {
        continue;
      }

      $result[] = $key . ": " . $value;
    }

    if (empty($result)) {
      return "";
    }

    return $this->implodeSimpleArrayProcessResult($result);
  }

  /**
   * Implode simple array process result.
   *
   * @param mixed $result
   *   Result to implode.
   *
   * @return string
   *   Result imploded.
   */
  protected function implodeSimpleArrayProcessResult($result): string {
    if (!is_array($result)) {
      return $result;
    }
    return implode(" || ", $result);
  }

  /**
   * Sor multidimensional array.
   *
   * @param array $array
   *   Array to sort.
   * @param string|int $array_key
   *   Array key used to sort.
   */
  protected function sortMultidimensionalArray(array &$array, $array_key) {
    usort($array, function ($a, $b) use ($array_key) {
      return strnatcmp($a[$array_key], $b[$array_key]);
    });
  }

}
