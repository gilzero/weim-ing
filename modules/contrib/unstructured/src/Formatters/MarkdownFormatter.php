<?php

namespace Drupal\unstructured\Formatters;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

/**
 * Formats text.
 */
class MarkdownFormatter implements FormatterInterface {

  /**
   * The splitter.
   *
   * @var string
   */
  public $splitter = "\n\n";

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
        return '*' . $this->renderLinks($data) . '*' . $this->splitter;
      case 'NarrativeText':
        return $this->renderLinks($data) . $this->splitter;
      case 'ListItem':
        return '- ' . $this->renderLinks($data) . $this->splitter;
      case 'Title':
        return $this->renderHeader($data) . $this->renderLinks($data) . $this->splitter;
      case 'Address':
        return $this->renderLinks($data) . $this->splitter;
      case 'Table':
        if (isset($data['metadata']['text_as_html'])) {
          return $this->renderTable($data['metadata']['text_as_html']);
        }
      case 'Image':
        $filePath = $this->generateImageFile($data);
        return '![Image](' . $filePath . ')' . $this->splitter;
      case 'PageBreak':
        return '---' . $this->splitter;
      case 'Header':
        return $this->renderHeader($data) . $this->renderLinks($data) . $this->splitter;
      case 'Footer':
        return '**' . $this->renderLinks($data) . '**' . $this->splitter;
      case 'UncategorizedText':
        return $this->renderLinks($data) . "\n";
      case 'Formula':
        return '*' . $this->renderLinks($data) . '*' . $this->splitter;
    }
  }

  /**
   * Render a header.
   *
   * @param array $data
   *   The metadata.
   *
   * @return string
   *   The rendered hashes for markdown.
   */
  public function renderHeader(array $data): string {
    $level = 1;
    if (isset($data['metadata']['category_depth'])) {
      $level = $data['metadata']['category_depth'] + 1;
    }
    return str_repeat('#', $level) . ' ';
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
      $data['text'] = substr_replace($data['text'], '[' . $link['text'] . '](' . $link['url'] . ')', $link['start_index'], strlen($link['text']));
    }
    return $data['text'];
  }

  /**
   * Render a table.
   *
   * @param string $data
   *   The html table.
   *
   * @return string
   *   The markdown table.
   */
  public function renderTable(string $data): string {
    $rows = explode('</tr>', $data);
    $longest = [];
    $renderRows = [];
    $markdown = '';
    foreach ($rows as $rowKey => $row) {
      $cols = str_contains($row, '</td>') ? explode('</td>', $row) : explode('</th>', $row);
      foreach ($cols as $key => $col) {
        $value = trim(strip_tags($col));
        if (empty($value)) {
          continue;
        }
        $renderRows[$rowKey][$key] = $value;
        // Get the longest string in the column.
        if (!isset($longest[$key]) || strlen($value) > $longest[$key]) {
          $longest[$key] = strlen($value);
        }
      }
    }

    foreach ($renderRows as $rowKey => $row) {
      $markdown .= '|';
      foreach ($row as $key => $col) {
        $length = $longest[$key] - strlen($col);
        $text = $col;
        if ($length > 0) {
          $text = $col . str_repeat(' ', $length);
        }
        $markdown .= ' ' . $text . ' |';
      }
      $markdown .= "\n";
    }
    return $markdown;
  }

  /**
   * Generate image file from base64.
   *
   * @param array $data
   *   The data to format.
   *
   * @return string
   *   The file path.
   */
  public function generateImageFile(array $data): string {
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
    return $file->getFileUri();
  }

}
