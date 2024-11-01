<?php

namespace Drupal\views_show_more\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\SqlBase;

/**
 * The plugin to handle show more pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "show_more",
 *   title = @Translation("Show more pager"),
 *   short_title = @Translation("Show more"),
 *   help = @Translation("Paged output, show more style"),
 *   theme = "views_show_more_pager"
 * )
 */
class ShowMore extends SqlBase {

  /**
   * Summary title overwrite.
   */
  public function summaryTitle() {
    $initial = !empty($this->options['initial']) ? $this->options['initial'] : $this->options['items_per_page'];

    $offset = '';
    if (!empty($this->options['offset'])) {
      $offset = ', skip ' . $this->options['offset'];
    }

    return $this->formatPlural(
      $initial, 'Initial @initial item, ', 'Initial @initial items, ', ['@initial' => $initial])
    . $this->formatPlural(
      $this->options['items_per_page'], 'Per click @count item', 'Per click @count items', ['@count' => $this->options['items_per_page']]
    ) . $offset;
  }

  /**
   * Options definition overwrite.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['result_display_method'] = ['default' => 'append'];
    $options['initial'] = ['default' => 0];
    $options['show_more_text'] = [
      'default' => $this->t('Show more'),
    ];
    $options['effects'] = [
      'contains' => [
        'type' => ['default' => 'none'],
        'speed_type' => ['default' => ''],
        'speed' => ['default' => ''],
        'speed_value' => ['default' => ''],
        'scroll_offset' => ['default' => '50'],
      ],
    ];
    $options['advance'] = [
      'contains' => [
        'content_selector' => ['default' => ''],
        'pager_selector' => ['default' => ''],
        'header_selector' => ['default' => ''],
        'footer_selector' => ['default' => ''],
      ],
    ];

    return $options;
  }

  /**
   * Options form overwrite.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $exclude = ['total_pages', 'expose', 'tags'];
    foreach ($exclude as $ex) {
      unset($form[$ex]['#title']);
      unset($form[$ex]['#description']);
      $form[$ex]['#attributes'] = ['class' => ['visually-hidden']];
    }

    // Result display method.
    $form['result_display_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Result display method'),
      '#description' => $this->t('<strong>Append</strong> result display method append the new content after the existing content on the page in ajax mode and in no-ajax mode replace the content by page refresh. <strong>Replace</strong> result display method replace the content with new content both in ajax and no-ajax mode. In no-ajax mode it refreshes the page.'),
      '#options' => [
        'append' => $this->t('Append'),
        'html' => $this->t('Replace'),
      ],
      '#default_value' => $this->options['result_display_method'] ?: 'append',
      '#weight' => 0,
    ];

    // Option for users to specify the text used on the 'show more' button.
    $form['show_more_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Show more pager link text'),
      '#description' => $this->t('Text for the button which used to load more items. Like "Show More".'),
      '#default_value' => $this->options['show_more_text'] ?: $this->t('Show more'),
      '#required' => TRUE,
      '#weight' => 1,
    ];

    // Initial items count.
    $form['initial'] = [
      '#type' => 'number',
      '#title' => $this->t('Initial items'),
      '#description' => $this->t('The number of items to display initially. Enter 0 for use same as items per page (show more click).'),
      '#default_value' => $this->options['initial'] ?: 0,
      '#weight' => 3,
      '#min' => 0,
    ];

    // Set item per page description and weight.
    $form['items_per_page']['#description'] = $this->t('The number of items to display per show more click.');
    $form['items_per_page']['#weight'] = 4;

    // Set offset weight.
    $form['offset']['#weight'] = 5;

    // Set pager id weight.
    $form['id']['#weight'] = 5;

    // Effects for loading adds new rows.
    $form['effects'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' => $this->t('Animation'),
      '#input' => TRUE,
      '#weight' => 7,
    ];

    $form['effects']['type'] = [
      '#type' => 'select',
      '#options' => [
        'none' => $this->t('None'),
        'fade' => $this->t('FadeIn'),
        'scroll' => $this->t('Scroll to New Content'),
        'scroll_fade' => $this->t('Scroll to New Content & FadeIn'),
      ],
      '#title' => $this->t('Animation Type'),
      '#default_value' => $this->options['effects']['type'],
    ];

    $form['effects']['speed_type'] = [
      '#type' => 'select',
      '#options' => [
        'slow' => $this->t('Slow'),
        'fast' => $this->t('Fast'),
        'custom' => $this->t('Custom'),
      ],
      '#states' => [
        'invisible' => [
          ':input[name="pager_options[effects][type]"]' => [
            ['value' => 'none'],
          ],
        ],
      ],
      '#title' => $this->t('Animation Speed'),
      '#default_value' => $this->options['effects']['speed_type'],
    ];

    $form['effects']['speed_value'] = [
      '#type' => 'number',
      '#title' => $this->t('Animation speed in ms'),
      '#states' => [
        'visible' => [
          ':input[name="pager_options[effects][speed_type]"]' => ['value' => 'custom'],
        ],
      ],
      '#min' => 0,
      '#default_value' => $this->options['effects']['speed_value'],
    ];

    $form['effects']['scroll_offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Scroll Offset'),
      '#states' => [
        'visible' => [
          ':input[name="pager_options[effects][type]"]' => [
            ['value' => 'scroll'],
            ['value' => 'scroll_fade'],
          ],
        ],
      ],
      '#min' => 0,
      '#default_value' => $this->options['effects']['scroll_offset'],
    ];

    // Advanced options, override default selectors.
    $form['advance'] = [
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' => $this->t('Advanced Options'),
      '#input' => TRUE,
      '#weight' => 9,
    ];

    // Option to specify the content_selector, which is the wrapping div for
    // views rows. This allows the JS to both find new rows on next pages and
    // know where to put them in the page.
    $form['advance']['content_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Content jquery selector'),
      '#description' => $this->t('jQuery selector for the rows wrapper, relative to the view container. Use when you override the views markup. Views Show More pager requires a wrapping element for the rows. Default is <strong><code>".view-content"</code></strong>.'),
      '#default_value' => $this->options['advance']['content_selector'],
    ];

    // Option to specify the pager_selector, which is the pager relative to the
    // view container.
    $form['advance']['pager_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pager jquery selector'),
      '#description' => $this->t('jQuery selector for the pager, relative to the view container. Use when you override the pager markup. Default is <strong><code>".pager-show-more"</code></strong>.'),
      '#default_value' => $this->options['advance']['pager_selector'],
    ];

    $form['advance']['header_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Header selector'),
      '#description' => $this->t('jQuery selector for header, relative to the view container.'),
      '#default_value' => $this->options['advance']['header_selector'],
    ];

    $form['advance']['footer_selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Footer selector'),
      '#description' => $this->t('jQuery selector for footer, relative to the view container.'),
      '#default_value' => $this->options['advance']['footer_selector'],
    ];

  }

  /**
   * Options form validate.
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    $effect_speed = $form_state->getValue([
      'pager_options',
      'effects',
      'speed_type',
    ]);
    $effect_speed_val = $form_state->getValue([
      'pager_options',
      'effects',
      'speed_value',
    ]);

    if ($effect_speed === 'custom') {
      if ($effect_speed_val === '') {
        $form_state->setErrorByName('pager_options][effects][speed_value', $this->t('Animation speed is required.'));
      }
      else {
        $form_state->setValue(['pager_options', 'effects', 'speed'], $effect_speed_val);
      }
    }
    else {
      $form_state->setValue(['pager_options', 'effects', 'speed'], $effect_speed);
    }
  }

  /**
   * Query overwrite.
   */
  public function query() {
    parent::query();

    $others_page = $this->options['items_per_page'];
    $limit = !empty($this->options['initial']) ? $this->options['initial'] : $others_page;
    $offset = !empty($this->options['offset']) ? $this->options['offset'] : 0;

    if ($this->current_page !== 0) {
      if ($this->view->display_handler->ajaxEnabled() || $this->options['result_display_method'] === 'replace') {
        $offset = $limit + (($this->current_page - 1) * $others_page) + $offset;
        $limit = $others_page;
      }
      else {
        $offset = 0;
        $limit += ($this->current_page * $others_page);
      }
    }

    $this->view->query->setLimit($limit);
    $this->view->query->setOffset($offset);
  }

  /**
   * Render overwrite.
   */
  public function render($input) {
    if ($this->getCurrentPage() >= ($this->getPagerTotal() - 1)) {
      // The page being rendered is the last page, so don't render the
      // show more option.
      return [];
    }

    $output = [
      '#theme' => $this->themeFunctions(),
      '#view' => $this->view,
      '#element' => $this->options['id'],
      '#parameters' => $input,
      '#options' => $this->options,
    ];

    if ($this->view->display_handler->ajaxEnabled()) {
      $output['#attached'] = [
        'library' => ['views_show_more/views_show_more'],
      ];
    }
    return $output;
  }

  /**
   * Render overwrite.
   */
  public function getPagerTotal() {
    if ($items_per_page = (int) $this->getItemsPerPage()) {
      if ($initial_items = (int) $this->getInitial()) {
        return 1 + ceil(($this->total_items - $initial_items) / $items_per_page);
      }

      return ceil($this->total_items / $items_per_page);
    }

    return 1;
  }

  /**
   * Execute the count query.
   */
  public function executeCountQuery(&$count_query) {
    $this->total_items = $count_query->execute()->fetchField();
    if (!empty($this->options['offset'])) {
      $this->total_items -= $this->options['offset'];
    }

    $this->updatePageInfo();
    return $this->total_items;
  }

  /**
   * Get the page initial items count.
   */
  private function getInitial() {
    $items_per_page = (int) $this->getItemsPerPage();
    return $this->options['initial'] ?? $items_per_page;
  }

}
