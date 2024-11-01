<?php

/**
 * @file
 * Vertex AI Search token support.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function vertex_ai_search_token_info() {

  $type = [
    'name' => t('Vertex AI Search'),
    'description' => t('Vertex AI search results information.'),
    'needs-data' => 'array',
  ];

  $tokens['vertex_ai_search_keywords'] = [
    'name' => t('Vertex AI Search Keywords'),
    'description' => t('The keywords used in a Vertex AI search.'),
  ];

  $tokens['vertex_ai_search_result_start'] = [
    'name' => t('Vertex AI Search Result Starting Number'),
    'description' => t('The number of the first result being displayed on a page.'),
  ];

  $tokens['vertex_ai_search_result_end'] = [
    'name' => t('Vertex AI Search Result Ending Number'),
    'description' => t('The number of the last result being displayed on a page.'),
  ];

  $tokens['vertex_ai_search_page'] = [
    'name' => t('Vertex AI Search Page Name'),
    'description' => t('The current search page name of a Vertex AI search.'),
  ];

  return [
    'types' => ['vertex_ai_search' => $type],
    'tokens' => [
      'vertex_ai_search' => $tokens,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function vertex_ai_search_tokens($type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  $replacements = [];

  if ($type == 'vertex_ai_search' && !empty($data['vertex_ai_search'])) {

    foreach ($tokens as $name => $original) {
      switch ($name) {

        case 'vertex_ai_search_keywords':
          $replacements[$original] = $data['vertex_ai_search']['vertex_ai_search_keywords'];
          break;

        case 'vertex_ai_search_result_start':
          $replacements[$original] = $data['vertex_ai_search']['vertex_ai_search_result_start'];
          break;

        case 'vertex_ai_search_result_end':
          $replacements[$original] = $data['vertex_ai_search']['vertex_ai_search_result_end'];
          break;

        case 'vertex_ai_search_page':
          $replacements[$original] = $data['vertex_ai_search']['vertex_ai_search_page'];
          break;

      }
    }
  }

  return $replacements;
}
