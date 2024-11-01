<?php

namespace Drupal\media_entity_download\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\EntityLink;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to download the media file.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("media_download_link")
 */
class DownloadLink extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\media\MediaInterface $media */
    $media = $this->getEntity($row);
    return Url::fromRoute('media_entity_download.download', ['media' => $media->id()])->setAbsolute($this->options['absolute'] ?? FALSE);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Download');
  }

}
