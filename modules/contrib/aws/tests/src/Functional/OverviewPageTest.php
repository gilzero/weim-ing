<?php

namespace Drupal\Tests\aws\Functional;

use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the AWS overview page.
 *
 * @group aws
 */
class OverviewPageTest extends FunctionalTestBase {

  /**
   * Tests that only users with the correct permission can access the page.
   */
  public function testOverviewPageAccess() {
    $url = Url::fromRoute('aws.overview');

    $this->drupalGet($url->toString());
    $this->assertSession()->statusCodeEquals(Response::HTTP_OK);

    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet($url->toString());
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);

    $this->drupalLogout();
    $this->drupalGet($url->toString());
    $this->assertSession()->statusCodeEquals(Response::HTTP_FORBIDDEN);
  }

}
