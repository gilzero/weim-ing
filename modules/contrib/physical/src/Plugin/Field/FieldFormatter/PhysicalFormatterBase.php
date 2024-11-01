<?php

namespace Drupal\physical\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\physical\NumberFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for physical formatters.
 */
abstract class PhysicalFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'output_unit' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    $form['output_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Output unit'),
      '#description' => $this->t('The output unit in which the given measurement should be converted and displayed to.'),
      '#default_value' => $this->getSetting('output_unit'),
      '#options' => $this->getUnitClass()::getLabels(),
      '#empty_option' => $this->t('Same as input unit'),
      '#empty_value' => '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return parent::settingsSummary() + [
      $this->t('Output unit: @output_unit', ['@output_unit' => !empty($this->getSetting('output_unit')) ? $this->getSetting('output_unit') : 'Same as input unit']),
    ];
  }

  /**
   * The number formatter.
   *
   * @var \Drupal\physical\NumberFormatterInterface
   */
  protected $numberFormatter;

  /**
   * Constructs a new PhysicalFormatterBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\physical\NumberFormatterInterface $number_formatter
   *   The number formatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, NumberFormatterInterface $number_formatter) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->numberFormatter = $number_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('physical.number_formatter')
    );
  }

  /**
   * Gets the unit class for the current field.
   *
   * @return \Drupal\physical\UnitInterface
   *   The unit class.
   */
  abstract protected function getUnitClass();

  /**
   * Gets the measurement class for the current field.
   *
   * @return \Drupal\physical\Measurement
   *   The measurementClass
   */
  abstract protected function getMeasurementClass();

}
