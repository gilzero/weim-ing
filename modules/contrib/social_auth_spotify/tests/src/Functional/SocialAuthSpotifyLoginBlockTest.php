<?php

namespace Drupal\Tests\social_auth_spotify\Functional;

use Drupal\Tests\social_auth\Functional\SocialAuthTestBase;

/**
 * Test that path to authentication route exists in Social Auth Login block.
 *
 * @group social_auth
 *
 * @ingroup social_auth_spotify
 */
class SocialAuthSpotifyLoginBlockTest extends SocialAuthTestBase {
  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['block', 'social_auth_spotify'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->provider = 'spotify';
  }

  /**
   * Test that the path is included in the login block.
   */
  public function testLinkExistsInBlock() {
    $this->checkLinkToProviderExists();
  }

}
