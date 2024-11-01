<?php

namespace Drupal\Tests\views_show_more\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\views\Entity\View;

/**
 * Test views show more.
 *
 * @group views_show_more
 */
class ViewsShowMoreTest extends WebDriverTestBase {

  use NodeCreationTrait;
  use ContentTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'views',
    'views_ui',
    'views_show_more',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->createContentType([
      'type' => 'page',
    ]);
    // Create 11 nodes.
    for ($i = 1; $i <= 11; $i++) {
      $this->createNode([
        'status' => TRUE,
        'type' => 'page',
      ]);
    }
  }

  /**
   * Assert how many nodes appear on the page.
   */
  protected function assertTotalNodes($total) {
    $this->assertEquals($total, count($this->getSession()->getPage()->findAll('css', '.node--type-page')));
  }

  /**
   * Scroll to a pixel offset.
   */
  protected function scrollTo($pixels) {
    $this->getSession()->getDriver()->executeScript("window.scrollTo(null, $pixels);");
  }

  /**
   * Create a view setup for testing.
   */
  protected function createView(string $path, array $settings = NULL) {
    View::create([
      'label' => 'WSM Test',
      'id' => $this->randomMachineName(),
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'row' => [
              'type' => 'entity:node',
              'options' => [
                'view_mode' => 'teaser',
              ],
            ],
            'pager' => [
              'type' => 'show_more',
              'options' => [
                'items_per_page' => $settings['items_per_page'] ?? 3,
                'offset' => $settings['offset'] ?? 0,
                'show_more_text' => $settings['show_more_text'] ?? 'Show more',
                'result_display_method' => $settings['result_display_method'] ?? 'append',
                'initial' => $settings['initial'] ?? 0,
                'effects' => [
                  'type' => 'fade',
                  'speed_type' => 'slow',
                  'speed' => 'slow',
                  'speed_value' => NULL,
                  'scroll_offset' => 50,
                ],
                'advance' => [
                  'header_selector' => $settings['header_selector'] ?? '',
                  'footer_selector' => $settings['footer_selector'] ?? '',
                ],
              ],
            ],
            'use_ajax' => $settings['use_ajax'] ?? TRUE,
            'header' => [
              'result' => [
                'id' => 'result',
                'table' => 'views',
                'field' => 'result',
                'relationship' => 'none',
                'group_type' => 'group',
                'admin_label' => '',
                'plugin_id' => 'result',
                'empty' => FALSE,
                'content' => 'Displaying @start - @end of @total',
              ],
            ],
          ],
        ],
        'page_1' => [
          'display_plugin' => 'page',
          'id' => 'page_1',
          'display_options' => [
            'path' => $path,
          ],
        ],
      ],
    ])->save();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test show more under different conditions.
   */
  public function testAppendOne() {
    $this->createView('append-one');
    $this->drupalGet('append-one');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);
  }

  /**
   * Test show more under different conditions.
   */
  public function testAppendAll() {
    $settings = [
      'items_per_page' => 5,
    ];

    $this->createView('append-all', $settings);
    $this->drupalGet('append-all');
    $this->assertTotalNodes(5);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(6)');
    $this->assertTotalNodes(10);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(11)');
    $this->assertTotalNodes(11);
  }

  /**
   * Test show more under different conditions.
   */
  public function testInitial() {
    $settings = [
      'initial' => 2,
    ];

    $this->createView('initially-load-two', $settings);
    $this->drupalGet('initially-load-two');
    $this->assertTotalNodes(2);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(3)');
    $this->assertTotalNodes(5);
  }

  /**
   * Test show more under different conditions.
   */
  public function testLinkText() {
    $settings = [
      'show_more_text' => 'Load more',
    ];

    $this->createView('link-text', $settings);
    $this->drupalGet('link-text');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Load more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);
  }

  /**
   * Test show more under different conditions.
   */
  public function testReplace() {
    $settings = [
      'result_display_method' => 'replace',
    ];

    $this->createView('replace', $settings);
    $this->drupalGet('replace');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(3)');
    $this->assertTotalNodes(3);
  }

  /**
   * Test show more under different conditions.
   */
  public function testReplaceWithoutAjax() {
    $settings = [
      'result_display_method' => 'replace',
      'use_ajax' => FALSE,
    ];

    $this->createView('replace-without-ajax', $settings);
    $this->drupalGet('replace-without-ajax');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertTotalNodes(3);
  }

  /**
   * Test show more under different conditions.
   */
  public function testAppendWithoutAjax() {
    $settings = [
      'use_ajax' => FALSE,
    ];

    $this->createView('append-without-ajax', $settings);
    $this->drupalGet('append-without-ajax');
    $this->assertTotalNodes(3);
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);
  }

  /**
   * Test show more under different conditions.
   */
  public function testResultSummary() {
    $settings = [
      'header_selector' => '.view-header',
    ];

    $this->createView('result-summary', $settings);
    $this->drupalGet('result-summary');
    $this->assertTotalNodes(3);
    $this->assertSession()->pageTextContains('Displaying 1 - 3 of 11');
    $this->getSession()->getPage()->clickLink('Show more');
    $this->assertSession()->waitForElement('css', '.node--type-page:nth-child(4)');
    $this->assertTotalNodes(6);
    $this->assertSession()->pageTextContains('Displaying 4 - 6 of 11');
  }

}
