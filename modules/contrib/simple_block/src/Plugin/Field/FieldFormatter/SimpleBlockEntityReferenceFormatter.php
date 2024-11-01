<?php

namespace Drupal\simple_block\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Utility\Token;
use Drupal\simple_block\SimpleBlockInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'simple block rendered entity' formatter.
 *
 * @FieldFormatter(
 *   id = "simple_block_rendered_entity",
 *   label = @Translation("Rendered Simple Block"),
 *   description = @Translation("Display the simple block as a rendered entity."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class SimpleBlockEntityReferenceFormatter extends EntityReferenceFormatterBase {

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a EntityReferenceSimpleBlockFormatter object.
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
   * @param \Drupal\Core\Utility\Token $token
   *   The Token service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, Token $token) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->token = $token;
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
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if ($entity instanceof SimpleBlockInterface) {
        $content = $entity->getContent();

        $elements[$delta] = [
          '#type' => 'processed_text',
          '#text' => $this->token->replace($content['value']),
          '#format' => $content['format'],
          '#contextual_links' => [
            'simple_block' => [
              'route_parameters' => ['simple_block' => $entity->id()],
            ],
          ],
          '#cache' => [
            'tags' => $entity->getCacheTags(),
          ],
        ];
      }
    }

    return $elements;
  }

}
