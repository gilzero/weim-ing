<?php

/**
 * @file
 * Push Framework token file.
 */

/**
 * Implements hook_token_info().
 */
function push_framework_token_info(): array {
  $type = [
    'name' => t('Push object'),
    'description' => t('Tokens related to the object the framework is pushing users.'),
    'needs-data' => 'push-object',
  ];
  $tokens['label'] = [
    'name' => t('Label'),
  ];
  $tokens['content'] = [
    'name' => t('Rendered content'),
  ];
  return [
    'types' => ['push-object' => $type],
    'tokens' => ['push-object' => $tokens],
  ];

}

/**
 * Implements hook_tokens().
 */
function push_framework_tokens(string $type, array $tokens, array $data): array {
  $replacements = [];
  if ($type === 'push-object' && !empty($data['push-object'])) {
    $pushObject = $data['push-object'];
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'label':
          $replacements[$original] = $pushObject['label'];
          break;

        case 'content':
          $replacements[$original] = $pushObject['content'];
          break;
      }
    }
  }
  return $replacements;
}
