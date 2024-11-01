<?php

namespace Drupal\string_field_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Component\Utility\Html;

/**
 * Plugin implementation of the 'plain_string_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "plain_string_formatter",
 *   label = @Translation("Plain string formatter"),
 *   field_types = {
 *     "string",
 *     "string_long",
 *   },
 *   edit = {
 *     "editor" = "form"
 *   },
 *   quickedit = {
 *     "editor" = "plain_text"
 *   }
 * )
 */
class PlainStringFormatter extends StringFormatter {

  /**
   * @var string
   */
  protected static $wrapTagEmptyValue = '_none';

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + [
      'wrap_tag' => static::$wrapTagEmptyValue,
      'wrap_class' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['wrap_tag'] = [
      '#title' => $this->t('Wrapper tag'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('wrap_tag'),
      '#empty_option' => $this->t('- None -'),
      '#empty_value' => static::$wrapTagEmptyValue,
      '#options' => $this->wrapTagOptions(),
    ];

    $element['wrap_class'] = [
      '#title' => $this->t('Classes for wrapper tag'),
      '#type' => 'textfield',
      '#maxlength' => 128,
      '#default_value' => $this->getSetting('wrap_class'),
      '#description' => $this->t('Space separated list of HTML classes.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $wrapTags = $this->wrapTagOptions();
    $wrapTag = $this->getSetting('wrap_tag');
    $hasWrapTag = $wrapTag !== static::$wrapTagEmptyValue;
    $wrapTagLabel = isset($wrapTags[$wrapTag]) ? $wrapTags[$wrapTag] : $wrapTag;

    $summary[] = $hasWrapTag ?
      $this->t('Wrapper tag: @tag', ['@tag' => $wrapTagLabel])
      : $this->t('No wrapper tag defined.');

    if ($hasWrapTag) {
      $class = $this->prepareClasses($this->getSetting('wrap_class'));
      $args = [
        '@class' => implode(' ', $class),
      ];
      $summary[] = $class ?
        $this->formatPlural(
          count($class),
          'Class: @class',
          'Classes: @class',
          $args
        )
        : $this->t('Without classes.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    $wrapTag = $this->getSetting('wrap_tag');
    if ($wrapTag === static::$wrapTagEmptyValue) {
      return $elements;
    }

    $attributes = [
      'class' => $this->prepareClasses($this->getSetting('wrap_class')),
    ];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => $wrapTag,
        '#attributes' => $attributes,
        'content' => $elements[$delta],
      ];
    }

    return $elements;
  }

  /**
   * @param string $class
   *
   * @return string[]
   */
  protected function prepareClasses($classes) {
    $prepared = [];
    foreach ($this->explode($classes) as $class) {
      $prepared[] = Html::getClass($class);
    }

    return $prepared;
  }

  /**
   * @param string $text
   *
   * @return string[]
   */
  protected function explode($text) {
    return preg_split('/[,\s]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
  }

  /**
   * @return string[]|\Drupal\Core\StringTranslation\TranslatableMarkup[]
   */
  protected function wrapTagOptions() {
    $options = ['context' => 'HTML tag'];

    return [
      // Semantic block elements.
      'h1' => $this->t('H1', [], $options),
      'h2' => $this->t('H2', [], $options),
      'h3' => $this->t('H3', [], $options),
      'h4' => $this->t('H4', [], $options),
      'h5' => $this->t('H5', [], $options),
      'h6' => $this->t('H6', [], $options),
      'p' => $this->t('P', [], $options),
      'blockquote' => $this->t('BLOCKQUOTE', [], $options),
      'pre' => $this->t('PRE', [], $options),
      'template' => $this->t('TEMPLATE', [], $options),

      // Semantic inline elements.
      'abbr' => $this->t('ABBR', [], $options),
      'address' => $this->t('ADDRESS', [], $options),
      'cite' => $this->t('CITE', [], $options),
      'code' => $this->t('CODE', [], $options),
      'del' => $this->t('DEL', [], $options),
      'em' => $this->t('EM', [], $options),
      'ins' => $this->t('INS', [], $options),
      'kbd' => $this->t('KBD', [], $options),
      'mark' => $this->t('MARK', [], $options),
      'meter' => $this->t('METER', [], $options),
      'progress' => $this->t('PROGRESS', [], $options),
      'q' => $this->t('Q', [], $options),
      's' => $this->t('S', [], $options),
      'samp' => $this->t('SAMP', [], $options),
      'small' => $this->t('SMALL', [], $options),
      'strong' => $this->t('STRONG', [], $options),
      'sub' => $this->t('SUB', [], $options),
      'sup' => $this->t('SUP', [], $options),
      'time' => $this->t('TIME', [], $options),
      'u' => $this->t('U', [], $options),
      'var' => $this->t('VAR', [], $options),

      // Semantically neutral block elements.
      'div' => $this->t('DIV', [], $options),

      // Semantically neutral inline elements.
      'span' => $this->t('SPAN', [], $options),
    ];
  }

}
