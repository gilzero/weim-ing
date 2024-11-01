<?php

namespace Drupal\Tests\moderation_dashboard\Functional;

/**
 * Tests redirection to dashboard on login.
 *
 * @group moderation_dashboard
 */
class ModerationDashboardRedirectTest extends ModerationDashboardTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.site')
      ->set('page.403', '/user/login')
      ->save();
    $this->config('moderation_dashboard.settings')
      ->set('redirect_on_login', TRUE)
      ->save();
  }

  /**
   * Tests if login is redirected when the user didn't request another page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testRedirect(): void {
    $this->drupalGet('user/login');
    $this->drupalLogin($this->user);
    $this->assertSession()->addressEquals("user/{$this->user->id()}/moderation-dashboard");
  }

  /**
   * Tests if login is not redirected when the user requested another page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function testNoRedirect(): void {
    // Anonymous user is redirected to log in screen.
    // After successful login the requested page is shown.
    $this->drupalGet('admin/content');
    $status_code = $this->getSession()->getStatusCode();
    $this->assertEquals(403, $status_code);

    $this->config('moderation_dashboard.settings')
      ->set('redirect_on_login', FALSE)
      ->save();
    $this->drupalGet('user/login');
    $this->drupalLogin($this->user);
    $this->assertSession()->addressEquals("user/{$this->user->id()}");
  }

}
