<?php

declare(strict_types=1);

namespace Drupal\Tests\maxlength\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Provides kernel tests MaxLength with CKEditor5.
 *
 * @group maxlength
 */
class MaxLengthCkeditorTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'maxlength',
    'field',
    'user',
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['maxlength']);
  }

  /**
   * Tests the CKEditor5 library dependency.
   *
   * @covers maxlength_library_info_alter()
   */
  public function testCkeditor5LibraryDependency() {
    // Get the library discovery service.
    /** @var \Drupal\Core\Asset\LibraryDiscovery $libraryDiscovery */
    $library_discovery = $this->container->get('library.discovery');
    $maxlength_library = $library_discovery->getLibraryByName('maxlength', 'maxlength');
    $this->assertNotContains('ckeditor5/internal.drupal.ckeditor5', $maxlength_library['dependencies']);

    // Now enable CKEditor5 module.
    $this->container->get('module_installer')->install(['ckeditor5']);
    $library_discovery->clearCachedDefinitions();
    $maxlength_library = $library_discovery->getLibraryByName('maxlength', 'maxlength');
    $this->assertContains('ckeditor5/internal.drupal.ckeditor5', $maxlength_library['dependencies']);
  }

}
