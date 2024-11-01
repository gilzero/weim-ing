<?php

namespace Drupal\unstructured\Formatters;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Formats text.
 */
class HtmlFormatter implements FormatterInterface {

  /**
   * The splitter.
   *
   * @var string
   */
  public $splitter = "<br />\n";

  /**
   * {@inheritDoc}
   */
  public function format(array $results, $split): array {
    $returnTexts = [];

    $i = 0;
    foreach ($results as $result) {
      switch ($split) {
        case 'page':
          $key = $result['metadata']['page_number'] ?? 0;
          $returnTexts[$key] = $this->formatType($result);
          break;

        case 'element':
          if (empty($returnTexts[$i])) {
            $returnTexts[$i] = '';
          }
          $returnTexts[$i] .= $this->formatType($result);
          $i++;
          break;

        default:
          if (empty($returnTexts[$i])) {
            $returnTexts[$i] = '';
          }
          $returnTexts[$i] .= $this->formatType($result);
          break;
      }
    }

    return $returnTexts;
  }

  /**
   * Format a specific type.
   *
   * @param array $data
   *   The data to format.
   *
   * @return string
   *   The formatted data.
   */
  public function formatType(array $data): string {
    switch ($data['type']) {
      case 'FigureCaption':
        return '<em>' . $this->renderLinks($data) . '</em>' . $this->splitter;
      case 'NarrativeText':
        return '<p>' . $this->renderLinks($data) . "</p>\n";
      case 'ListItem':
        return '<ul><li>' . $this->renderLinks($data) . "</li></ul>\n";
      case 'Title':
        return $this->renderHeader($this->renderLinks($data), $data) . "\n";
      case 'Address':
        return '<p>' . $this->renderLinks($data) . "</p>\n";
      case 'Table':
        if (isset($data['metadata']['text_as_html'])) {
          return $data['metadata']['text_as_html'];
        }
      case 'Image':
        $image = $this->generateImageFile($data);
        return '<img src="' . $image->createFileUrl() . '" data-entity-type="file" data-entity-uuid="' . $image->uuid() . '" alt="' . $data['metadata']['filename'] . '" />' . $this->splitter;
      case 'PageBreak':
        return "<hr />\n";
      case 'Header':
        return $this->renderHeader($this->renderLinks($data), $data) . "\n";
      case 'Footer':
        return '<strong>' . $this->renderLinks($data) . '</strong>' . $this->splitter;
      case 'UncategorizedText':
        return '<p>' . $this->renderLinks($data) . "</p>\n";
      case 'Formula':
        return '<em>' . $this->renderLinks($data) . '</em>' . $this->splitter;
    }

  }

  /**
   * Render a header.
   *
   * @param string $text
   *   The text to render.
   * @param array $data
   *   The metadata.
   *
   * @return string
   *   The rendered header.
   */
  public function renderHeader(string $text, array $data): string {
    $level = 1;
    if (isset($data['metadata']['category_depth'])) {
      $level = $data['metadata']['category_depth'] + 1;
    }
    return '<h' . $level . '>' . $text . '</h' . $level . '>';
  }

  /**
   * Render a text.
   *
   * @param string $text
   *   The text to render.
   *
   * @return string
   *   The rendered text.
   */
  public function renderLinks(array $data) {
    if (!isset($data['metadata']['links'])) {
      return $data['text'];
    }
    foreach ($data['metadata']['links'] as $link) {
      // Use the start index in the link and add the link there for the word in the text.
      $data['text'] = substr_replace($data['text'], '<a href="' . $link['url'] . '">' . $link['text'] . '</a>', $link['start_index'], strlen($link['text']));
    }
    return $data['text'];
  }

  /**
   * Generate image file from base64.
   *
   * @param array $data
   *   The data to format.
   *
   * @return \Drupal\file\Entity\File
   *   The file entity.
   */
  public function generateImageFile(array $data): File {
    // Generate a filename.
    $extension = 'jpg';
    switch ($data['metadata']['image_mime_type']) {
      case 'image/png':
        $extension = 'png';
        break;

      case 'image/gif':
        $extension = 'gif';
        break;
    }
    $filename = $data['metadata']['filename'] . '.' . microtime(TRUE) .  '.' . $extension;
    // Use the file system service to save the file.
    $fileSystem = \Drupal::service('file_system');
    $directory = 'public://ai_interpolator_unstructured';
    $fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    $file = \Drupal::service('file.repository')->writeData(base64_decode($data['metadata']['image_base64']), $directory . '/' . $filename, FileSystemInterface::EXISTS_RENAME);
    $file->set('status', File::STATUS_PERMANENT);
    $file->save();
    return $file;
  }

}
