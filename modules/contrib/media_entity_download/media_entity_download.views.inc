<?php

/**
 * @file
 * Contains Views integration for the media_entity_download module.
 */

/**
 * Implements hook_views_data().
 */
function media_entity_download_views_data(): array {
  $data = [];
  $data['media']['media_download_link'] = [
    'title' => t('Link to download media file'),
    'help' => t('Provide a link to download the media file.'),
    'click sortable' => FALSE,
    'field' => [
      'id' => 'media_download_link',
    ],
  ];
  return $data;
}
