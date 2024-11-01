<?php

namespace Drupal\iconify_icons\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'iconify_icon' widget.
 *
 * @FieldWidget(
 *   id = "iconify_icon_link_widget",
 *   module = "iconify_icons",
 *   label = @Translation("Iconify Icon Link"),
 *   field_types = {
 *     "iconify_icon"
 *   }
 * )
 */
class IconifyIconLinkWidget extends IconifyIconWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    /** @var \Drupal\iconify_icons\Plugin\Field\FieldType\IconifyIcon $iconify_icon */
    $iconify_icon = $items[$delta];
    $settings = unserialize($iconify_icon->get('settings')->getValue() ?? '', ['allowed_classes']);

    $element['link_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#description' => $this->t('The URL to which the user should be redirected. This can be an internal URL like /node/1234, an external URL like @url, or an anchor like #main-content.', ['@url' => 'http://example.com']),
      '#size' => 100,
      '#default_value' => $settings['link_url'] ?? '',
      '#pattern' => '^(https?:\/\/[^\s]+|www\.[^\s]+|\/[^\s]+|#[a-zA-Z0-9_-]+)$',
    ];

    $element['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#description' => $this->t('It will show a text close to the icon.'),
      '#size' => 100,
      '#default_value' => $settings['link_text'] ?? '',
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $collection = $this->getSetting('collections');

    foreach ($values as $delta => &$item) {
      $item['delta'] = $delta;
      $item['selected_collection'] = $collection;
      $settings['link_url'] = $item['link_url'] ?? '';
      $settings['link_text'] = $item['link_text'] ?? '';
      $item['settings'] = serialize(array_filter($settings));
    }

    return $values;
  }

}
