<?php

declare(strict_types=1);

namespace Drupal\Tests\smart_trim\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore womp MMMMMOOOORRRREEEE

/**
 * Class to test Smart Trim translations.
 *
 * @group smart_trim
 */
class SmartTrimTranslationTest extends BrowserTestBase {

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
    'smart_trim_translation_test',
    'locale',
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

    smart_trim_translation_test_add_translation('More', 'MMMMMOOOORRRREEEE', 'en');
    $this->config('locale.settings')->set('translate_english', 1)->save();
  }

  /**
   * Test that Smart Trim hooks alter the "More" link correctly.
   */
  public function testSmartTrimMoreTextTranslation(): void {
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

    // Assert that the "More" link text has been translated.
    $query = $this->xpath('//a[text() = "MMMMMOOOORRRREEEE"]');
    $this->assertCount(1, $query, 'Expected 1 "MMMMMOOOORRRREEEE" link.');
  }

}
