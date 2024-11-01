<?php

namespace Drupal\unstructured\Formatters;

/**
 * Formats text.
 */
class TextFormatter implements FormatterInterface {

  /**
   * The splitter.
   *
   * @var string
   */
  public $splitter = "\n";

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
      case 'Table':
        return $this->renderTable($data['metadata']['text_as_html']) . $this->splitter;
      default:
        return $data['text'] . $this->splitter;
    }
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

}
