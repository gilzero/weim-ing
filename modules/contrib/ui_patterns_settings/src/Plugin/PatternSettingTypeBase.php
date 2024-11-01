<?php

namespace Drupal\ui_patterns_settings\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for UI Patterns Setting plugins.
 */
abstract class PatternSettingTypeBase extends PluginBase implements
    ConfigurableInterface,
    PatternSettingTypeInterface,
    ContainerFactoryPluginInterface {

  /**
   * Returns a list of plugin dependencies.
   *
   * @return bool
   *   True if all dependencies exist.
   */
  protected function getSettingTypeDependencies() {
    return [];
  }

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private $routeMatch;

  /**
   * Return pattern definitions for setting .
   *
   * @var \Drupal\ui_patterns\Definition\PatternDefinition
   */
  private $patternDefinition;

  /**
   * Return pattern definitions for setting .
   *
   * @var \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting
   */
  private $patternSettingDefinition;

  /**
   * Return pattern definitions for setting .
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    array $plugin_definition,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    $configuration += $this->defaultConfiguration();
    $this->patternSettingDefinition = $configuration['pattern_setting_definition'];
    $this->patternDefinition = $configuration['pattern_definition'];
    $this->moduleHandler = $module_handler;
    $this->entityTypeManager = $entity_type_manager;
    unset($configuration['pattern_setting_definition']);
    unset($configuration['pattern_definition']);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Return value if set otherwise take the default value.
   *
   * @param mixed $value
   *   The provided value.
   *
   * @return string
   *   The value for this setting
   */
  protected function getValue($value) {
    if ($value === NULL) {
      return $this->getPatternSettingDefinition()->getDefaultValue();
    }
    else {
      return $value ?? "";
    }
  }

  /**
   * Return pattern setting definition.
   *
   * @return \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting
   *   Pattern setting definition.
   */
  protected function getPatternSettingDefinition() {
    return $this->patternSettingDefinition;
  }

  /**
   * {@inheritdoc}
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
      $container->get('entity_type.manager')
    );
    /** @var \Drupal\Core\StringTranslation\TranslationInterface $translation */
    $translation = $container->get('string_translation');

    $plugin->setStringTranslation($translation);
    $plugin->routeMatch = $container->get('current_route_match');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldStorage(FieldStorageConfig $storage_config) {
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess($value, array $context) {
    $def = $this->getPatternSettingDefinition();
    $value = $this->settingsPreprocess($value, $context, $def);
    return $value;
  }

  /**
   * Checks if current route is a layout builder route.
   *
   * @return bool
   *   True if it is a layout builder route.
   */
  protected function isLayoutBuilderRoute() {
    if (preg_match('/^(layout_builder\.([^.]+\.)?)/', $this->routeMatch->getRouteName())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterElement($value, PatternDefinitionSetting $def, &$element) {

  }

  /**
   * {@inheritdoc}
   */
  public function settingsPreprocess(
    $value,
    array $context,
    PatternDefinitionSetting $def
  ) {
    return $value;
  }

  /**
   * Returns the bind form field.
   *
   * @param array $form
   *   The form definition array for the settings configuration form.
   * @param string $value
   *   The stored default value.
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $def
   *   The pattern definition.
   *
   * @return array
   *   The form.
   */
  protected function tokenForm(
    array $form,
    $value,
    PatternDefinitionSetting $def
  ) {
    $form[$def->getName() . "_token"] = [
      '#type' => 'textfield',
      '#title' => $this->t("Token for %label", ['%label' => $def->getLabel()]),
      '#default_value' => $this->getValue($value),
      '#attributes' => [
        'class' => [
          'js-ui-patterns-settings-show-token-link',
          'js-ui-patterns-settings__token',
        ],
      ],
      '#wrapper_attributes' => ['class' => ['js-ui-patterns-settings__token-wrapper']],
    ];
    return $form;
  }

  /**
   * Check required input fields in layout forms.
   *
   * @param array $element
   *   The element to validate.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form.
   */
  public static function validateLayout(
    array $element,
    FormStateInterface &$form_state,
    array &$form
  ) {
    $parents = $element['#parents'];
    $value = $form_state->getValue($parents);
    $parents[count($parents) - 1] = $parents[count($parents) - 1] . '_token';
    $token_value = $form_state->getValue($parents);
    if (empty($value) && empty($token_value)) {
      // Check if a variant is selected and the value
      // is provided by the variant.
      $variant = $form_state->getValue(
        [
          'layout_configuration',
          'pattern',
          'variant',
        ]
      );
      if (!empty($variant)) {
        $variant_def = $element['#pattern_definition']->getVariant($variant);
        $variant_ary = $variant_def->toArray();
        if (!empty(
        $variant_ary['settings'][$element['#pattern_setting_definition']->getName(
        )]
        )) {
          return;
        }
      }

      $form_state->setError(
        $element,
        t(
          '@name field is required.',
          ['@name' => $element['#title']]
        )
      );
    }
  }

  /**
   * Add validation and basics classes to the raw input field.
   *
   * @param array $input
   *   The input field.
   * @param \Drupal\ui_patterns_settings\Definition\PatternDefinitionSetting $def
   *   The pattern definition.
   * @param string $form_type
   *   The form type. Either layouts_display or display.
   */
  protected function handleInput(
    array &$input,
    PatternDefinitionSetting $def,
    $form_type
  ) {
    $input['#attributes']['class'][] = 'js-ui-patterns-settings__input js-ui-patterns-settings-' . $def->getName();
    $input['#wrapper_attributes']['class'][] = 'js-ui-patterns-settings__input-wrapper';
    if ($def->getRequired()) {
      $input['#title'] .= ' *';
      if ($form_type === 'layouts_display') {
        $input['#element_validate'][] = [
          PatternSettingTypeBase::class,
          'validateLayout',
        ];
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all settings types.
   * Individual settings plugins can add elements to this form by
   * overriding PatternSettingTypeBaseInterface::settingsForm().
   * Most plugins should not override this method unless they
   * need to alter the generic form elements.
   *
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(
    array $form,
    $value,
    $token_value,
    $form_type
  ) {
    $dependencies = $this->getSettingTypeDependencies();
    $def = $this->getPatternSettingDefinition();
    foreach ($dependencies as $dependency) {
      if (!$this->moduleHandler->moduleExists($dependency)) {
        $form[$def->getName(
        )] = ['#markup' => "Missing SettingType {$def->getName()} dependency {$dependency}."];
        return $form;
      }
    }

    $form = $this->settingsForm($form, $value, $def, $form_type);
    $form[$def->getName()]['#pattern_setting_definition'] = $def;
    $form[$def->getName()]['#pattern_definition'] = $this->patternDefinition;

    if (!empty($def->getWeight())) {
      $form[$def->getName()]['#weight'] = $def->getWeight();
    }

    if ($def->getAllowToken()) {
      $form = $this->tokenForm($form, $token_value, $def);
      if (isset($form[$def->getName() . '_token'])) {
        $classes = 'js-ui-patterns-settings__wrapper';
        if (!empty($token_value)) {
          $classes .= ' js-ui-patterns-settings--token-has-value';
        }
        $form[$def->getName()]['#prefix'] = '<div class="' . $classes . '">';
        $form[$def->getName() . '_token']['#suffix'] = '</div>';
        $form[$def->getName() . '_token']['#pattern_setting_definition'] = $def;
        $form[$def->getName(
        ) . '_token']['#pattern_definition'] = $this->patternDefinition;
      }
    }
    return $form;
  }

  /**
   * Set the right group before drupal #group attribute is processed.
   *
   * @param array $element
   *   The form field.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The pattern definition.
   * @param mixed $form
   *   The form.
   *
   * @return array
   *   The processed element.
   */
  public static function formGroupProcess(
    array &$element,
    FormStateInterface $form_state = NULL,
    &$form = []
  ) {
    if (isset($element['#pattern_setting_definition'])) {
      $setting_definition = $element['#pattern_setting_definition'];
      if ($setting_definition->getGroup() !== NULL) {
        $parents = $element['#parents'];
        array_pop($parents);
        $parents[] = $setting_definition->getGroup();
        $element['#group'] = implode('][', $parents);
      }
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldStorageExposableTypes() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessExposedField(FieldItemList $items) {
    foreach ($items as $item) {
      return $item->value;
    }
  }

}
