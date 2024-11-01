<?php

/**
 * @file
 * Themeable functions for Vertex AI Search results.
 */

/**
 * The search results page can be themed/customized.
 */
function template_preprocess_vertex_ai_search_results(&$variables) {
}

/**
 * Search result item.
 */
function template_preprocess_vertex_ai_search_result(&$variables) {

}

/**
 * Search results message.
 */
function template_preprocess_vertex_ai_search_results_message(&$variables) {

}

/**
 * Search no results message.
 */
function template_preprocess_vertex_ai_search_no_results_message(&$variables) {

}

/**
 * Search no keywords message.
 */
function template_preprocess_vertex_ai_search_no_keywords_message(&$variables) {

}

/**
 * Search results page search form.
 */
function template_preprocess_vertex_ai_search_search_page_form(&$variables) {
  $variables['form']['keys']['#attributes']['class'][] = 'vais-element-keys';
  $variables['form']['keys']['#theme_wrappers'] = [];
  $variables['form']['searchPage']['#attributes']['class'][] = 'vais-element-page';
  $variables['form']['searchPage']['#theme_wrappers'] = [];
  $variables['form']['submit']['#attributes']['class'][] = 'vais-element-submit';
}
