<?php

namespace Drupal\type_tray\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\type_tray\Controller\TypeTrayController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure type_tray settings.
 */
class TypeTraySettingsForm extends ConfigFormBase {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator service.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typed_config_manager, EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    parent::__construct($config_factory, $typed_config_manager);
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('entity_type.manager'),
      $container->get('cache_tags.invalidator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'type_tray_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['type_tray.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('type_tray.settings');

    // Define the categories available.
    $categories = $config->get('categories') ?? [];
    $form['categories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Categories'),
      '#description' => $this->t('Enter the categories to be used, one per line. Use the format key|label, where "key" is the category machine name, and "label" its human-visible name.'),
      '#default_value' => $this->buildStringFromCategories($categories),
      '#rows' => 10,
      '#required' => TRUE,
    ];

    $form['fallback_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Fallback category'),
      '#description' => $this->t('Enter the string to be used when a content type is not categorized.'),
      '#default_value' => $config->get('fallback_label') ?? TypeTrayController::UNCATEGORIZED_LABEL,
      '#required' => TRUE,
    ];

    $formats = filter_formats();
    $options = array_combine(array_keys($formats), array_map(function ($item) {
      assert($item instanceof FilterFormat);
      return $item->label();
    }, $formats));
    $form['text_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Extended description format'),
      '#description' => $this->t('Indicate the text format to be used when writing extended descriptions for each content type.'),
      '#options' => $options,
      '#default_value' => $config->get('text_format') ?? 'plain_text',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $old_categories = $this->config('type_tray.settings')->get('categories') ?? [];
    if (empty($old_categories)) {
      return;
    }
    $new_categories = static::extractCategoriesFromString($form_state->getValue('categories', []));
    /** @var \Drupal\node\Entity\NodeType[] $types */
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $removed = array_diff_key($old_categories, $new_categories);
    $cant_remove = [];
    foreach ($types as $type) {
      $type_category = $type->getThirdPartySetting('type_tray', 'type_category');
      if (!empty($type_category) && in_array($type_category, array_keys($removed))) {
        $cant_remove[] = $type_category . '|' . $removed[$type_category];
      }
    }
    if (!empty($cant_remove)) {
      $form_state->setErrorByName('categories', $this->t('The following categories are in use and cannot be removed: %categories.', [
        '%categories' => implode(", ", $cant_remove),
      ]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('type_tray.settings');

    $categories = static::extractCategoriesFromString($form_state->getValue('categories'));
    if (!empty($categories)) {
      $config->set('categories', $categories);
    }

    $config->set('fallback_label', Xss::filter($form_state->getValue('fallback_label', TypeTrayController::UNCATEGORIZED_LABEL)));
    $config->set('text_format', $form_state->getValue('text_format', 'plain_text'));

    $config->save();

    $this->cacheTagsInvalidator->invalidateTags([
      'config:node_type_list',
    ]);

    parent::submitForm($form, $form_state);
  }

  /**
   * Generates a string representation of an array of 'allowed values'.
   *
   * This string format is suitable for edition in a textarea.
   *
   * @param array $values
   *   An array of values, where array keys are values and array values are
   *   labels.
   *
   * @return string
   *   The string representation of the $values array:
   *    - Values are separated by a carriage return.
   *    - Each value is in the format "value|label" or "value".
   */
  public static function buildStringFromCategories(array $values) {
    $lines = [];
    foreach ($values as $key => $value) {
      $lines[] = "$key|$value";
    }
    return implode("\n", $lines);
  }

  /**
   * Convert a key|value-based string into an associative array.
   *
   * @param string $string
   *   The string we want to convert, where each line is a key|value pair, or
   *   where each key|value pair is separated by commas.
   *
   * @return array
   *   An associative array representing the string passed in.
   */
  public static function extractCategoriesFromString($string) {
    $values = [];

    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $position => $text) {
      $text = Xss::filter($text);
      if (strpos($text, "|") === FALSE || strpos($text, "|") === 0) {
        // If users enter only one text value in a row (i.e. no "|"), assume
        // it's a label, and generate a key for it.
        $key = Html::cleanCssIdentifier($text);
        $value = $text;
      }
      else {
        $parts = explode("|", $text);
        $key = $parts[0];
        $value = $parts[1];
      }

      $values[$key] = $value;
    }

    return $values;
  }

}
