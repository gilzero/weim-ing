<?php

/**
 * @file
 * Builds placeholder replacement tokens for smart_trim related data.
 */

declare(strict_types=1);

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\RendererInterface;

/**
 * Implements hook_token_info_alter().
 */
function smart_trim_token_info_alter(array &$info): void {
  // Attach smart trim tokens to their respective entity tokens.
  foreach (\Drupal::entityTypeManager()->getDefinitions() as $entity_type_id => $entity_type) {
    if (!$entity_type->entityClassImplements(ContentEntityInterface::class)) {
      continue;
    }

    // Make sure a token type exists for this entity.
    $token_type = \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    if (empty($token_type) || !isset($info['types'][$token_type])) {
      continue;
    }

    $fields = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);
    foreach ($fields as $field_name => $field) {
      assert($field instanceof FieldStorageDefinitionInterface);
      $labels = _token_field_label($entity_type_id, $field_name);
      $label = array_shift($labels);

      if ($field->getType() === 'text_with_summary') {
        $info['tokens'][$token_type][$field_name . '-smart-trim'] = [
          'name' => t('@label (Smart trim summary)', ['@label' => $label]),
          'description' => t('Smart trimmed version of the field or the summary.'),
        ];
      }
    }
  }
}

/**
 * Implements hook_tokens().
 */
function smart_trim_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  $langcode = $options['langcode'] ?? NULL;
  // Entity tokens.
  if ($type === 'entity' && !empty($data['entity_type']) && !empty($data['entity']) && !empty($data['token_type'])) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $data['entity'];
    if (!($entity instanceof ContentEntityInterface)) {
      return $replacements;
    }

    if (!isset($options['langcode'])) {
      // Set the active language in $options, so that it is passed along.
      $langcode = $options['langcode'] = $entity->language()->getId();
    }
    // Obtain the entity with the correct language.
    $entity = \Drupal::service('entity.repository')
      ->getTranslationFromContext($entity, $langcode);

    foreach ($tokens as $name => $original) {
      $field_name = str_replace('-smart-trim', '', $name);
      if (!str_contains($name, '-smart-trim')) {
        continue;
      }
      if (!$entity->hasField($field_name)) {
        continue;
      }

      // If a token view mode is set up, use its display settings. Otherwise,
      // fallback to defaults.
      $display_options = \Drupal::service('entity_display.repository')
        ->getViewDisplay($data['entity_type'], $entity->bundle(), 'token')
        ->getComponent($field_name);
      if (empty($display_options['type']) || $display_options['type'] !== 'smart_trim') {
        $display_options = [
          'type' => 'smart_trim',
          'label' => 'hidden',
          'settings' => \Drupal::service('plugin.manager.field.formatter')->getDefaultSettings('smart_trim'),
        ];
      }
      $field_output = \Drupal::entityTypeManager()
        ->getViewBuilder($data['entity_type'])
        ->viewField($entity->get($field_name), $display_options);
      $field_output['#token_options'] = $options;
      if (method_exists(RendererInterface::class, 'renderPlain')) {
        // @phpstan-ignore-next-line as it is deprecated in D10.3 and removed from D12.
        $field_output_renderer = \Drupal::service('renderer')->renderPlain($field_output);
      }
      else {
        $field_output_renderer = \Drupal::service('renderer')->renderInIsolation($field_output);
      }

      $replacements[$original] = $field_output_renderer;
    }
  }

  return $replacements;
}
