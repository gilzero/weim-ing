<?php

namespace Drupal\manage_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * A field formatter for entity titles.
 *
 * @FieldFormatter(
 *   id = "title",
 *   label = @Translation("Title"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class TitleFormatter extends StringFormatter {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $heading_options = [
      'span' => 'span',
      'div' => 'div',
    ];
    foreach (range(1, 5) as $level) {
      $heading_options['h' . $level] = 'H' . $level;
    }

    $form['tag'] = [
      '#title' => $this->t('Tag'),
      '#type' => 'select',
      '#description' => $this->t('Select the tag which will be wrapped around the title.'),
      '#options' => $heading_options,
      '#default_value' => $this->getSetting('tag'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'tag' => 'h2',
      'link_to_entity' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Display as @tag', ['@tag' => $this->getSetting('tag')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode = NULL) {
    $items = parent::viewElements($items, $langcode);

    foreach ($items as $delta => &$item) {
      $tag = $this->getSetting('tag');
      $item['#prefix'] = "<$tag>";
      $item['#suffix'] = "</$tag>";
    }

    return $items;
  }

}
