<?php

namespace Drupal\Tests\ui_patterns\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ui_patterns\Traits\TwigDebugTrait;
use Drupal\user\UserInterface;

/**
 * Test pattern preview rendering.
 *
 * @group ui_patterns
 */
class UiPatternsPreviewRenderTest extends BrowserTestBase {
  use TwigDebugTrait;

  /**
   * Default theme. See https://www.drupal.org/node/3083055.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Disable schema validation when running tests.
   *
   * @var bool
   *
   * @todo Fix this by providing actual schema validation.
   */
  protected $strictConfigSchema = FALSE; // phpcs:ignore

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_render_test',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $user = $this->drupalCreateUser($this->getAdminUserPermissions());
    if (!($user instanceof UserInterface)) {
      $this->fail('Impossible to create the tests user.');
    }

    $this->adminUser = $user;
  }

  /**
   * Test Ignored.
   *
   * Tests pattern preview suggestions.
   * Review suggestions for D10.
   */
  public function testPatternPreviewSuggestions(): void {
    $this->enableTwigDebugMode();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('ui_patterns.patterns.overview'));

    // Assert correct variant suggestions.
    $suggestions = [
      'pattern-foo--variant-default--preview.html.twig',
      'pattern-foo--variant-default.html.twig',
      'pattern-foo--preview.html.twig',
      'pattern-foo.html.twig',
      'pattern-foo-bar--variant-default--preview.html.twig',
      'pattern-foo-bar--variant-default.html.twig',
      'pattern-foo-bar--preview.html.twig',
      'pattern-foo-bar.html.twig',
    ];
    foreach ($suggestions as $suggestion) {
      $this->assertSession()->responseContains($suggestion);
    }
  }

  /**
   * Tests links for external documentation.
   */
  public function testRenderLinks(): void {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('ui_patterns.patterns.overview'));

    // Test external documentation links.
    $this->assertSession()->linkByHrefExists('https://test.com');
    $this->assertSession()->linkExists('External documentation');

    $this->assertSession()->linkByHrefExists('https://example.com?test_param=test_value');
    $this->assertSession()->linkExists('Example');

    $this->assertSession()->elementExists('css', 'a[href="https://example.com?test_param=test_value"][target="_blank"]');
  }

  /**
   * The list of admin user permissions.
   *
   * @return array
   *   The list of admin user permissions.
   */
  protected function getAdminUserPermissions(): array {
    return [
      'access patterns page',
    ];
  }

}
