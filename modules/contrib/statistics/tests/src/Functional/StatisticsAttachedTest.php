<?php

declare(strict_types=1);

namespace Drupal\Tests\statistics\Functional;

use Drupal\node\Entity\Node;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests if statistics.js is loaded when content is not printed.
 *
 * @group statistics
 */
class StatisticsAttachedTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'statistics'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests if statistics.js is loaded when content is not printed.
   */
  public function testAttached() {
    $node = Node::create([
      'type' => 'page',
      'title' => 'Page node',
      'body' => 'body text',
    ]);
    $node->save();
    $this->drupalGet('node/' . $node->id());
    $base = DRUPAL_ROOT;
    $absPath = realpath(__DIR__ . "/../../../statistics.js");
    $expected = mb_substr($absPath, mb_strlen($base));
    $this->assertSession()
      ->responseContains($expected);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalCreateContentType(['type' => 'page']);

    // Install "statistics_test_attached" and set it as the default theme.
    $theme = 'statistics_test_attached';
    \Drupal::service('theme_installer')->install([$theme]);
    $this->config('system.theme')
      ->set('default', $theme)
      ->save();
    // Installing a theme will cause the kernel terminate event to rebuild the
    // router. Simulate that here.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

}
