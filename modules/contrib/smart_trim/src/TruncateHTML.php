<?php

namespace Drupal\smart_trim;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;

/**
 * @file
 * Contains trim functionality.
 *
 * As noted on http://www.pjgalbraith.com/2011/11/truncating-text-html-with-php/
 * with some modifications to adhere to the Drupal Coding Standards.
 */

/**
 * This class basically truncates the HTML characters.
 */
class TruncateHTML {

  /**
   * Total characters.
   *
   * @var int
   */
  protected int $charCount = 0;

  /**
   * Total words.
   *
   * @var int
   */
  protected int $wordCount = 0;

  /**
   * Character / Word limit.
   *
   * @var int
   */
  protected int $limit;

  /**
   * Element to start on.
   *
   * @var \DOMElement
   */
  protected \DOMElement $startNode;

  /**
   * Ellipsis character.
   *
   * @var string
   */
  protected string $ellipsis;

  /**
   * Did we find the breakpoint?
   *
   * @var bool
   */
  protected bool $foundBreakpoint = FALSE;

  /**
   * Sets up object for use.
   *
   * @param string $html
   *   Text to be prepared.
   * @param int $limit
   *   Amount of text to return.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return \DOMDocument
   *   Prepared DOMDocument to work with.
   */
  protected function init(string $html, int $limit, string $ellipsis): \DOMDocument {
    $dom = Html::load($html);

    // The body tag node, our html fragment is automatically wrapped in
    // a <html><body> etc.
    $this->startNode = $dom->getElementsByTagName("body")->item(0);
    $this->limit = $limit;
    $this->ellipsis = $ellipsis;
    $this->charCount = 0;
    $this->wordCount = 0;
    $this->foundBreakpoint = FALSE;

    $this->removeHtmlComments($dom);

    return $dom;
  }

  /**
   * Truncates HTML text by characters.
   *
   * @param string $html
   *   Text to be updated.
   * @param int $limit
   *   Amount of text to allow.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return string
   *   Resulting text.
   */
  public function truncateChars(string $html, int $limit, string $ellipsis = '...'): string {
    if ($limit <= 0 || $limit >= mb_strlen(strip_tags($html))) {
      return $html;
    }
    $dom = $this->init($html, $limit, $ellipsis);
    // Pass the body node on to be processed.
    $this->domNodeTruncateChars($this->startNode);
    return Html::serialize($dom);
  }

  /**
   * Truncates HTML text by words.
   *
   * @param string $html
   *   Text to be updated.
   * @param int $limit
   *   Amount of text to allow.
   * @param string $ellipsis
   *   Characters to use at the end of the text.
   *
   * @return string
   *   Resulting text.
   */
  public function truncateWords(string $html, int $limit, string $ellipsis = '...'): string {
    if ($limit <= 0 || $limit >= $this->countWords(strip_tags($html))) {
      return $html;
    }

    $dom = $this->init($html, $limit, $ellipsis);
    // Pass the body node on to be processed.
    $this->domNodeTruncateWords($this->startNode);
    return Html::serialize($dom);
  }

  /**
   * Truncates a DOMNode by character count.
   *
   * @param \DOMNode $domNode
   *   Object to be truncated.
   */
  protected function domNodeTruncateChars(\DOMNode $domNode): void {
    foreach ($domNode->childNodes as $node) {

      if ($this->foundBreakpoint) {
        return;
      }

      if ($node->hasChildNodes()) {
        $this->domNodeTruncateChars($node);
      }
      else {
        $text = html_entity_decode($node->nodeValue, ENT_QUOTES, 'UTF-8');
        $length = mb_strlen($text);
        if (($this->charCount + $length) >= $this->limit) {
          // We have found our end point.
          $node->nodeValue = Unicode::truncate($text, $this->limit - $this->charCount, TRUE);
          $this->removeTrailingPunctuation($node);
          $this->removeProceedingNodes($node);
          $this->insertEllipsis($node);
          $this->foundBreakpoint = TRUE;
          return;
        }
        else {
          $this->charCount += $length;
        }
      }
    }
  }

  /**
   * Truncates a DOMNode by words.
   *
   * @param \DOMNode $domNode
   *   Object to be truncated.
   */
  protected function domNodeTruncateWords(\DOMNode $domNode): void {
    foreach ($domNode->childNodes as $node) {

      if ($this->foundBreakpoint) {
        return;
      }

      if ($node->hasChildNodes()) {
        $this->domNodeTruncateWords($node);
      }
      else {
        $cur_count = $this->countWords($node->nodeValue);

        if (($this->wordCount + $cur_count) >= $this->limit) {
          // We have found our end point.
          if ($cur_count > 1 && ($this->limit - $this->wordCount) < $cur_count) {
            // Note that PREG_SPLIT_OFFSET_CAPTURE and UTF-8 is interesting.
            // preg_split() works on the string as an array of bytes therefore
            // in order to use its results we need to use non unicode aware
            // functions.
            // @see https://bugs.php.net/bug.php?id=67487
            $words = preg_split("/[\n\r\t ]+/", $node->nodeValue, ($this->limit - $this->wordCount) + 1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE);
            end($words);
            $last_word = prev($words);
            $node->nodeValue = substr($node->nodeValue, 0, $last_word[1] + strlen($last_word[0]));
          }

          $this->removeTrailingPunctuation($node);
          $this->removeProceedingNodes($node);
          $this->insertEllipsis($node);
          $this->foundBreakpoint = TRUE;
          return;
        }
        else {
          $this->wordCount += $cur_count;
        }
      }
    }
  }

  /**
   * Removes certain punctuation from the end of the node value.
   *
   * @param \DOMNode $domNode
   *   Node to be altered.
   */
  protected function removeTrailingPunctuation(\DOMNode $domNode): void {
    while (preg_match('/[\.,:;\?!…]$/u', $domNode->nodeValue)) {
      $domNode->nodeValue = mb_substr($domNode->nodeValue, 0, -1);
    }
  }

  /**
   * Removes preceding sibling node.
   *
   * @param \DOMNode $domNode
   *   Node to be altered.
   */
  protected function removeProceedingNodes(\DOMNode $domNode): void {
    $nextNode = $domNode->nextSibling;

    if ($nextNode !== NULL) {
      // Run in a while loop to prevent hitting the maximum recursion limit
      // when processing DOM elements with many children at the same level.
      while ($nextNode->nextSibling !== NULL) {
        $node = $nextNode;
        $nextNode = $nextNode->nextSibling;
        $node->parentNode->removeChild($node);
      }
      $this->removeProceedingNodes($nextNode);
      $domNode->parentNode->removeChild($nextNode);
    }
    else {
      // Scan upwards till we find a sibling.
      $currentNode = $domNode->parentNode;
      while ($currentNode !== $this->startNode) {
        if ($currentNode->nextSibling !== NULL) {
          $currentNode = $currentNode->nextSibling;
          $this->removeProceedingNodes($currentNode);
          $currentNode->parentNode->removeChild($currentNode);
          break;
        }
        $currentNode = $currentNode->parentNode;
      }
    }
  }

  /**
   * Inserts the ellipsis character to the node.
   *
   * @param \DOMNode $domNode
   *   Node to be altered.
   */
  protected function insertEllipsis(\DOMNode $domNode): void {
    // HTML tags to avoid appending the ellipsis to.
    $avoid = ['a', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5'];

    if (in_array($domNode->parentNode->nodeName, $avoid) && ($domNode->parentNode->parentNode !== NULL || $domNode->parentNode->parentNode !== $this->startNode)) {
      // Append as text node to parent instead.
      $textNode = new \DOMText($this->ellipsis);

      if ($domNode->parentNode->parentNode->nextSibling) {
        $domNode->parentNode->parentNode->insertBefore($textNode, $domNode->parentNode->parentNode->nextSibling);
      }
      else {
        $domNode->parentNode->parentNode->appendChild($textNode);
      }
    }
    else {
      // This allows unicode characters like \u2026 for ellipsis.
      $this->ellipsis = Html::escape(json_decode('"' . $this->ellipsis . '"'));

      // Append to current node.
      $domNode->nodeValue = rtrim($domNode->nodeValue) . $this->ellipsis;
    }
  }

  /**
   * Gets number of words in text.
   *
   * @param string $text
   *   Text to be counted.
   *
   * @return int
   *   Results
   */
  protected function countWords(string $text): int {
    $words = preg_split("/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY);
    return count($words);
  }

  /**
   * Removes all comment elements.
   *
   * @param \DOMNode $domNode
   *   Node to be altered.
   */
  protected function removeHtmlComments(&$domNode): void {
    $nodes = $domNode->childNodes;
    for ($i = 0; $i < $nodes->length; $i++) {
      $node = $nodes->item($i);
      if ($node->nodeName == '#comment') {
        $node->parentNode->removeChild($node);
        // Since we just removed a child, decrement the counter.
        $i--;
      }
      if ($node->hasChildNodes()) {
        $this->removeHtmlComments($node);
      }
    }
  }

}
