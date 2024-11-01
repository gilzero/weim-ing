<?php

namespace Drupal\Tests\openapi_ui_swagger\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that openapi_ui_swagger can be installed.
 *
 * @group openapi_ui_swagger
 */
class InstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests installing openapi_ui_swagger programmatically.
   */
  public function testInstall(): void {
    $this->assertTrue($this->container->get('module_installer')->install(['openapi_ui_swagger']));
  }

}
