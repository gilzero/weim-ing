<?php

declare(strict_types=1);

namespace Drupal\Tests\regcode\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the regcode View is properly updated during database updates.
 *
 * @group regcode
 * @group legacy
 */
class RegcodeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $d10_specific_dump = DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-9.4.0.bare.standard.php.gz';
    $d11_specific_dump = DRUPAL_ROOT . '/core/modules/system/tests/fixtures/update/drupal-10.3.0.bare.standard.php.gz';

    // Can't use the same dump in D10 and D11.
    if (file_exists($d10_specific_dump)) {
      $core_dump = $d10_specific_dump;
    }
    else {
      $core_dump = $d11_specific_dump;
    }

    // Use core fixture and Regcode-specific fixture.
    $this->databaseDumpFiles = [
      $core_dump,
      __DIR__ . '/../../../fixtures/update/drupal-10.3.0.regcode-update-empty-view-format-3476833.php',
    ];
  }

  /**
   * Tests regcode_update_10200().
   *
   * @see regcode_update_10200()
   */
  public function testHookUpdate10200(): void {
    $key = 'display.default.display_options.empty.area.content';
    $key_format = $key . '.format';
    $key_value = $key . '.value';

    // Load the 'views.view.regcode' View and check that it holds the expected
    // pre-update values.
    $config = $this->config('views.view.regcode');
    $this->assertEquals('full_html', $config->get($key_format));
    $this->assertEquals('You have not created any registration codes.', $config->get($key_value));

    // Run updates.
    $this->runUpdates();

    // Load the 'views.view.regcode' View again. It should now hold the expected
    // post-update values.
    $config = $this->config('views.view.regcode');
    $this->assertEquals('plain_text', $config->get($key_format));
    $this->assertEquals('No registration codes have been created.', $config->get($key_value));
  }

}
