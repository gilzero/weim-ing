<?php

namespace Drupal\smart_trim;

use Drupal\Component\Utility\Xss;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class SmartTrimTwigExtension extends AbstractExtension {

  /**
   * The truncate HTML service.
   *
   * @var \Drupal\smart_trim\TruncateHTML
   */
  protected TruncateHTML $truncateHtml;

  /**
   * Constructs \Drupal\Core\Template\TwigExtension.
   *
   * @param \Drupal\smart_trim\TruncateHTML $truncate_html
   *   The truncate HTML service.
   */
  public function __construct(TruncateHTML $truncate_html) {
    $this->truncateHtml = $truncate_html;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('smart_trim_chars', $this->smartTrimChars(...), ['is_safe' => ['html']]),
      new TwigFilter('smart_trim_words', $this->smartTrimWords(...), ['is_safe' => ['html']]),
    ];
  }

  /**
   * Applies a smart trim to the specified number of characters.
   *
   * @param string|null $value
   *   The string to trim.
   * @param int $length
   *   The number of characters to trim to.
   * @param string $suffix
   *   Character to append to the end of trimmed content.
   * @param bool $strip_html
   *   If true, strip HTML before trimming.
   *
   * @return string
   *   The trimmed content.
   */
  public function smartTrimChars(?string $value, int $length, string $suffix = '', bool $strip_html = TRUE) {
    if ($strip_html) {
      $value = trim(Xss::filter($value));
    }
    return $this->truncateHtml->truncateChars($value, $length, $suffix);
  }

  /**
   * Applies a smart trim to the specified number of words.
   *
   * @param string|null $value
   *   The string to trim.
   * @param int $length
   *   The number of characters to trim to.
   * @param string $suffix
   *   Character to append to the end of trimmed content.
   * @param bool $strip_html
   *   If true, strip HTML before trimming.
   *
   * @return string
   *   The trimmed content.
   */
  public function smartTrimWords(?string $value, int $length, string $suffix = '', bool $strip_html = TRUE) {
    if ($strip_html) {
      $value = trim(Xss::filter($value));
    }
    return $this->truncateHtml->truncateWords($value, $length, $suffix);
  }

}
