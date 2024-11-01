<?php

namespace Drupal\tavily\Plugin\AiAutomatorType;

use Drupal\ai_automator\Attribute\AiAutomatorType;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * The rules for a string_long field.
 */
#[AiAutomatorType(
  id: 'tavily_search_word_to_string_long',
  label: new TranslatableMarkup('Tavily Search Word to Summary'),
  field_rule: 'string_long',
  target: '',
)]
class TavilySearchWordToContextString extends TavilySearchWordToContextBase {

}
