<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Functional;

use Drupal\Tests\BrowserTestBase;

// cspell:ignore unpriv

/**
 * Tests operation of the Regcode create code(s) page.
 *
 * @group Regcode
 */
class RegcodeCreateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['regcode', 'help', 'block'];

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
   * Tests module permissions / access to the Create tab.
   */
  public function testUserAccess(): void {
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
   * Tests submitting the Create form.
   */
  public function testRegcodeCreate(): void {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();
    $date = date_format(date_create(), 'Y-m-d');

    // Submit form as admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/people/regcode/create');
    $this->submitForm([
      'regcode_create_code' => 'abc',
      'regcode_create_maxuses' => 1,
      'regcode_create_length' => 3,
      'regcode_create_format' => 'alpha',
      'regcode_create_case' => 1,
      'regcode_create_begins' => $date,
      'regcode_create_expires' => $date,
      'regcode_create_number' => 1,
    ], 'Create codes');
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Created registration code (ABC)');
    $this->drupalLogout();
  }

}
