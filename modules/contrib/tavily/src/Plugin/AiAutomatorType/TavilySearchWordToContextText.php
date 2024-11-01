<?php

namespace Drupal\tavily\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The rules for a text_long field.
 */
#[AiAutomatorType(
  id: 'tavily_search_word_to_text_long',
  label: new TranslatableMarkup('Tavily Search Word to Summary'),
  field_rule: 'text_long',
  target: '',
)]
class TavilySearchWordToContextText extends TavilySearchWordToContextBase {

  /**
   * {@inheritDoc}
   */
  public function rowBreaker() {
    return "<br />";
  }

}
