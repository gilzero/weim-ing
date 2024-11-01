<?php

namespace Drupal\compound_title\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'compound_title' formatter.
 *
 * @FieldFormatter(
 *   id = "compound_title",
 *   label = @Translation("Compound Title"),
 *   field_types = {
 *     "compound_title"
 *   }
 * )
 */
class CompoundTitleFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new instance of the plugin.
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
   *   Third party settings.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, $settings, $label, $view_mode, $third_party_settings, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
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
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'wrapper_tag' => 'div',
      'link_content' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements['wrapper_tag'] = [
      '#title' => $this->t('Wrapper tag'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('wrapper_tag'),
    ];

    $elements['link_content'] = [
      '#title' => $this->t('Link the title to the content.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('link_content'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];

    $wrapperTag = $this->getSetting('wrapper_tag');
    $summary[] = $this->t('Wrapper tag: @wrapper_tag', ['@wrapper_tag' => $wrapperTag]);

    $link_content = $this->getSetting('link_content');
    $summary[] = $this->t('Linked to content: @link_content', ['@link_content' => $link_content ? $this->t('Yes') : $this->t('No')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    $element = [];

    $wrapperTag = $this->getSetting('wrapper_tag');
    $link_content = $this->getSetting('link_content');
    $entity = $items->getEntity();

    foreach ($items as $delta => $item) {
      // Do not proceed, if the first line is empty.
      if (empty($item->first_line)) {
        continue;
      }

      $compound_title_render_array = [
        '#theme' => 'compound_title',
        '#wrapper_tag' => $wrapperTag ?: 'div',
        '#first_line' => $item->first_line,
        '#second_line' => $item->second_line,
      ];

      if ($link_content) {
        $rendered_title = $this->renderer->render($compound_title_render_array);
        $title_markup = Markup::create($rendered_title);
        $element[$delta] = [
          '#type' => 'link',
          '#title' => $title_markup,
          '#url' => $entity->toUrl(),
          '#options' => [
            'attributes' => [
              'title' => $item->first_line . ' ' . $item->second_line,
              'class' => ['compound-title-link'],
            ],
          ],
        ];
      }
      else {
        $element[$delta] = $compound_title_render_array;
      }
    }

    return $element;
  }

}
