<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Functional;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore unpriv

/**
 * Tests operation of the Regcode settings page.
 *
 * @group Regcode
 */
class RegcodeSettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['regcode', 'help', 'block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $adminUser;

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $unprivUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // System help block is needed to see output from hook_help().
    $this->drupalPlaceBlock('help_block', ['region' => 'help']);

    // Create our test users.
    $this->adminUser = $this->createUser([
      'administer site configuration',
      'access administration pages',
      'administer registration codes',
    ]);
    $this->unprivUser = $this->createUser();
  }

  /**
   * Tests access to module settings tab.
   */
  public function testSettingsTab(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('admin/config/people/regcode/settings');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config/people/regcode/settings');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/regcode/settings');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Configure the registration code module.');
    $this->drupalLogout();
  }

  /**
   * Tests access to code list tab.
   */
  public function testListTab(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('admin/config/people/regcode');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config/people/regcode');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/regcode');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('View and manage created registration codes.');
    $this->drupalLogout();
  }

  /**
   * Tests access to code creation tab.
   */
  public function testCreateTab(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('admin/config/people/regcode/create');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config/people/regcode/create');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/regcode/create');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Create manually or generate new registration codes.');
    $this->drupalLogout();
  }

  /**
   * Tests access to code manage tab.
   */
  public function testManageTab(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test as anonymous user.
    $this->drupalGet('admin/config/people/regcode/manage');
    $assert->statusCodeEquals(403);
    $assert->pageTextContains('Access denied');
    $assert->pageTextContains('You are not authorized to access this page.');

    // Test as authenticated but unprivileged user.
    $this->drupalLogin($this->unprivUser);
    $this->drupalGet('admin/config/people/regcode/manage');
    $assert->statusCodeEquals(403);
    $this->drupalLogout();

    // Test as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/regcode/manage');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Provides bulk management features for created registration codes.');
    $this->drupalLogout();
  }

}
