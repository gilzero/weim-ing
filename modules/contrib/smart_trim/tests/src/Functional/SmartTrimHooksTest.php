<?php

declare(strict_types=1);

namespace Drupal\Tests\smart_trim\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore womp

/**
 * Class to test Smart Trim hooks do what they advertise.
 *
 * @group smart_trim
 */
class SmartTrimHooksTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'test_page_test',
    'field',
    'filter',
    'text',
    'token',
    'token_filter',
    'smart_trim',
    'filter_test',
    'field_ui',
    'smart_trim_hooks_test',
  ];

  /**
   * A user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $user;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->user = $this->drupalCreateUser();
    $this->drupalLogin($this->user);

    $this->drupalCreateNode([
      'title' => $this->randomString(),
      'id' => 1,
      'type' => 'article',
      'body' => [
        'value' => 'I used to bull\'s-eye womp rats in my T-sixteen back home.',
        'format' => 'filter_test',
      ],
    ])->save();

  }

  /**
   * Test that Smart Trim hooks alter the "More" link correctly.
   */
  public function testSmartTrimMoreHooks(): void {
    $display_repository = \Drupal::service('entity_display.repository');
    $more = [
      'display_link' => TRUE,
      'class' => 'more-link',
      'link_trim_only' => FALSE,
      'target_blank' => FALSE,
      'text' => 'More',
      'aria_label' => 'Read more about [node:title]',
    ];
    $display_repository->getViewDisplay('node', 'article')
      ->setComponent('body', [
        'type' => 'smart_trim',
        'settings' => [
          'trim_length' => 15,
          'trim_type' => 'chars',
          'summary_handler' => 'trim',
          'more' => $more,
        ],
      ])
      ->save();

    $this->drupalGet('/node/1');

    // Assert that the "More" link text has been altered.
    $query = $this->xpath('//a[text() = "So much more!"]');
    $this->assertCount(1, $query, 'Expected 1 "So much more!" link.');

    // Assert that the "More" link url has been altered.
    $query = $this->xpath('//div[contains(@class, "more-link")]//a[contains(@href, "https://www.drupal.org")]');
    $this->assertCount(1, $query, 'Expected 1 "https://www.drupal.org" link.');
  }

}
