<?php

namespace Drupal\Tests\iconify_icons\Unit;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\iconify_icons\IconsCache;

/**
 * Tests for IconsCache Service.
 *
 * @group iconify_icons
 */
class IconsCacheTest extends UnitTestCase {

  /**
   * The file handler.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The tested ban iconsCache.
   *
   * @var \Drupal\iconify_icons\IconsCache
   */
  protected $iconsCache;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->iconsCache = new IconsCache($this->fileSystem);
  }

  /**
   * Tests getIcon method.
   */
  public function testGetIcon() {
    $collection = 'collection_test';
    $icon_name = 'icon_1';
    $query_options = [
      'width' => '50',
      'height' => '50',
      'color' => 'red',
      'flip' => 'horizontal',
      'rotate' => '90',
    ];
    $ok_response = '';

    $this->fileSystem->method('realpath')->willReturn(FALSE);
    $assert = $this->iconsCache->getIcon($collection, $icon_name, $query_options);
    $this->assertEquals($ok_response, $assert);
  }

  /**
   * Tests setIcon method.
   */
  public function testSetIcon() {
    $collection = 'collection_test';
    $directory = 'public://iconify-icons/collection_test/icon_1/50/50/red/horizontal/90';
    $icon_name = 'icon_1';
    $icon = 'icon';
    $parameters = [
      'width' => '50',
      'height' => '50',
      'color' => 'red',
      'flip' => 'horizontal',
      'rotate' => '90',
    ];

    $this->fileSystem->method('prepareDirectory')->willReturn(TRUE);
    $this->fileSystem->method('saveData')->willReturn(TRUE);
    $assert = $this->iconsCache->setIcon($collection, $icon_name, $icon, $parameters);
    $this->assertTrue($assert);
  }

  /**
   * Tests checkIcon method.
   */
  public function testCheckIcon() {
    $collection = 'collection_test';
    $icon_name = 'icon_1';
    $parameters = [
      'width' => '50',
      'height' => '50',
      'color' => 'red',
      'flip' => 'horizontal',
      'rotate' => '90',
    ];

    $this->fileSystem->method('realpath')->willReturn(TRUE);
    $assert = $this->iconsCache->checkIcon($collection, $icon_name, $parameters);
    $this->assertTrue($assert);
  }

}
