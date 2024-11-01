<?php

namespace Drupal\manage_display\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * A field formatter that displays comment 'in reply to' information.
 *
 * Formats a comment reference as a sentence including the subject and author.
 * The sentence is determined by the 'in-reply-to' template, with a
 * default of "In reply to <SUBJECT> by <AUTHOR>".
 *
 * @FieldFormatter(
 *   id = "in_reply_to",
 *   label = @Translation("In reply to"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class InReplyToFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items->referencedEntities() as $delta => $comment) {
      if ($comment->isNew()) {
        continue;
      }

      $all_options = [
        'subject' => [
          'type' => 'string',
          'settings' => ['link_to_entity' => TRUE],
        ],
        'uid' => [
          'type' => 'author',
        ],
      ];

      $elements[$delta] = ['#theme' => 'in_reply_to'];
      foreach ($all_options as $name => $options) {
        $field_items = $comment->get($name);
        if (!$field_items->isEmpty()) {
          // View a single field item to avoid the field wrappers.
          $elements[$delta][$name] = $field_items->first()->view($options);
          _manage_display_fix_comment_item($elements[$delta][$name], $name, $comment);
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type == 'comment' && $field_name == 'pid';
  }

}
